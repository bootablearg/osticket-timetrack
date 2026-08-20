<?php
/*********************************************************************
    timetrack.php

    Time Tracking plugin for osTicket 1.18.x

    Records the time spent on every reply and internal note, with a time
    type and a billable flag, and reports it on screen, as CSV and over
    the REST API.

    No core file is patched. Everything hangs off public plugin signals:
      threadentry.created   store the time for each thread entry
      ticket.create.before  catch the time of the first entry (API)
      object.view           inject the field into the staff forms
      apps.scp              report page in the staff panel
      ajax.scp              edit an already recorded entry
      api                   REST endpoints

    Released under the GNU General Public License version 2
**********************************************************************/
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.app.php');
require_once('class.tti18n.php');
require_once('class.timetrack.php');
require_once('class.ttcolumn.php');
require_once('config.php');

class TimeTrackPlugin extends Plugin {
    var $config_class = 'TimeTrackConfig';

    // One time log per help desk; multiple instances make no sense here
    function isMultiInstance() {
        return false;
    }

    /** Vars of the ticket being created, for the API path */
    static $pending_vars = null;

    function bootstrap() {
        $config = $this->getConfig();

        // The table belongs to the plugin and is created on first run
        TimeTrackEntry::createTable();

        if (!$config->get('enabled'))
            return;

        $self = $this;

        // --- Time of the very first entry (API / web form) ---------------
        Signal::connect('ticket.create.before', function($ignore, &$vars) {
            TimeTrackPlugin::$pending_vars = $vars;
        });

        // --- Storing ------------------------------------------------------
        Signal::connect('threadentry.created', function($entry) use ($self) {
            $self->saveEntryTime($entry);
        });

        // --- Field in the forms, time badges in the thread ----------------
        Signal::connect('object.view', function($object, &$extras) use ($self) {
            global $thisstaff;
            if (!$thisstaff || !($object instanceof Ticket))
                return;
            $self->injectUI($object);
        });

        // --- Report page ---------------------------------------------------
        // NOTE: registerStaffApp() is NOT static even though it writes to a
        // static property. Calling it statically is fatal on PHP 8 and takes
        // the whole bootstrap down with it.
        $app = new Application();
        $app->registerStaffApp(tt__('Time'), 'apps.php/timetrack/');

        Signal::connect('apps.scp', function($dispatcher) use ($self) {
            $dispatcher->append(
                url('^/timetrack/', patterns('',
                    url_get('^$', function() use ($self) {
                        return $self->reportPage();
                    }),
                    url_get('^csv$', function() use ($self) {
                        return $self->reportCsv();
                    })
                ))
            );
        });

        // --- Editing a recorded entry ---------------------------------------
        Signal::connect('ajax.scp', function($dispatcher) use ($self) {
            $dispatcher->append(
                url('^/timetrack/', patterns('',
                    url_get('^entry/(?P<id>\d+)$', function($id) use ($self) {
                        return $self->editDialog($id);
                    }),
                    url_post('^entry/(?P<id>\d+)$', function($id) use ($self) {
                        return $self->editSave($id);
                    })
                ))
            );
        });

        // --- REST API --------------------------------------------------------
        Signal::connect('api', function($dispatcher) use ($self) {
            $dispatcher->append(
                url('^/timetrack/', patterns('',
                    url_get('^ticket/(?P<id>\d+)\.(?P<format>json)$', function($id, $format) use ($self) {
                        return $self->apiTicket($id, $format);
                    }),
                    url_get('^report\.(?P<format>json|csv)$', function($format) use ($self) {
                        return $self->apiReport($format);
                    })
                ))
            );
        });
    }

    /* ===============================================================
       Storing
       =============================================================== */

    function getTypes() {
        $raw = (string) $this->getConfig()->get('types');
        $types = array();
        foreach (preg_split('/\r?\n/', $raw) as $t) {
            $t = trim($t);
            if ($t !== '')
                $types[] = $t;
        }
        return $types ?: array(tt__('Untyped'));
    }

    function canSeeReports($staff=null) {
        global $thisstaff;
        $staff = $staff ?: $thisstaff;
        if (!$staff)
            return false;
        return $this->getConfig()->get('report_admins_only')
            ? $staff->isAdmin()
            : true;
    }

    function saveEntryTime($entry) {
        if (!$entry || !$entry->getId())
            return;

        // Replies (R), notes (N) and the initial message (M) only
        if (!in_array($entry->type, array('R', 'N', 'M')))
            return;

        $config = $this->getConfig();
        $source = 'web';
        $vars = $_POST;

        // The first message posted through the API never hits $_POST
        if (!isset($vars['tt_minutes']) && self::$pending_vars) {
            $vars = self::$pending_vars;
            $source = 'api';
            self::$pending_vars = null;
        }

        $raw = null;
        foreach (array('tt_minutes', 'time_spent') as $k) {
            if (isset($vars[$k]) && trim((string) $vars[$k]) !== '') {
                $raw = $vars[$k];
                break;
            }
        }
        if ($raw === null)
            return;

        $minutes = TimeTrackEntry::parseMinutes($raw);
        if (!$minutes)   // null (empty), false (unreadable) or 0
            return;

        $minutes = TimeTrackEntry::roundUp($minutes, $config->get('rounding'));

        $types = $this->getTypes();
        $type = $vars['tt_type'] ?? $vars['time_type'] ?? $types[0];
        if (!in_array($type, $types))
            $type = $types[0];

        $billable = !empty($vars['tt_billable']) || !empty($vars['time_bill']);
        // Internal notes are not billable unless explicitly enabled
        if ($entry->type == 'N' && !$config->get('notes_billable'))
            $billable = false;

        TimeTrackEntry::record($entry, $minutes, $type, $billable, $source);
    }

    /* ===============================================================
       Interface
       =============================================================== */

    function injectUI($ticket) {
        global $cfg;

        $config   = $this->getConfig();
        $types    = $this->getTypes();
        $billable = (bool) $config->get('billable_default');
        $required = (bool) $config->get('required');
        $notes_billable = (bool) $config->get('notes_billable');

        $entries = TimeTrackEntry::forTicket($ticket->getId());
        $totals  = TimeTrackEntry::totalsForTicket($ticket->getId());

        $data = array();
        foreach ($entries as $entry_id => $r)
            $data[$entry_id] = array(
                'id'       => $r->id,
                'pretty'   => $r->getPretty(),
                'type'     => $r->getType(),
                'billable' => $r->isBillable() ? 1 : 0,
            );

        include 'templates/inject.tmpl.php';
    }

    function reportPage() {
        // header.inc.php / footer.inc.php belong to the staff panel and expect
        // these globals; since the template is included from a method they have
        // to be pulled into scope or the header dies on getAllowIframes().
        global $ost, $cfg, $thisstaff, $nav, $errors, $msg, $warn, $title, $api;

        if (!$this->canSeeReports())
            Http::response(403, tt__('Access denied'));

        $criteria = $this->criteriaFromRequest();
        $rows = TimeTrackEntry::search($criteria);
        $types = $this->getTypes();

        ob_start();
        include 'templates/report.tmpl.php';
        return ob_get_clean();
    }

    function criteriaFromRequest() {
        return array(
            'from'     => $_GET['from'] ?? date('Y-m-01'),
            'to'       => $_GET['to'] ?? date('Y-m-d'),
            'org_id'   => $_GET['org_id'] ?? '',
            'staff_id' => $_GET['staff_id'] ?? '',
            'billable' => isset($_GET['billable']) ? $_GET['billable'] : '',
            'type'     => $_GET['type'] ?? '',
        );
    }

    /* ===============================================================
       Export
       =============================================================== */

    function csvRows($criteria) {
        $rows = array();
        foreach (TimeTrackEntry::search($criteria) as $r) {
            $ticket = $r->getTicket();
            $user = $ticket ? $ticket->getOwner() : null;
            $org  = $user ? $user->getOrganization() : null;
            $rows[] = array(
                'date'         => Format::date($r->getCreated(), true, 'yyyy-MM-dd'),
                'ticket'       => $ticket ? $ticket->getNumber() : '',
                'subject'      => $ticket ? $ticket->getSubject() : '',
                'organization' => $org ? (string) $org->getName() : '',
                'user'         => $user ? (string) $user->getName() : '',
                'agent'        => (string) $r->getStaffName(),
                'type'         => $r->getType(),
                'billable'     => $r->isBillable() ? tt__('Yes') : tt__('No'),
                'minutes'      => $r->getMinutes(),
                'hours'        => number_format($r->getHours(), 2, '.', ''),
            );
        }
        return $rows;
    }

    function csvHeaders() {
        return array(tt__('Date'), tt__('Ticket'), tt__('Subject'),
            tt__('Organization'), tt__('User'), tt__('Agent'), tt__('Type'),
            tt__('Billable'), tt__('Minutes'), tt__('Hours'));
    }

    /** Semicolon and BOM: Excel outside en_US chokes on plain comma-separated UTF-8 */
    function streamCsv($rows, $filename, $totals=true) {
        Http::download($filename, 'text/csv');
        $out = fopen('php://output', 'w');
        fputs($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, $this->csvHeaders(), ';');

        $minutes = 0; $billable = 0;
        $no = tt__('No');
        foreach ($rows as $r) {
            fputcsv($out, array_values($r), ';');
            $minutes += $r['minutes'];
            if ($r['billable'] !== $no)
                $billable += $r['minutes'];
        }

        if ($totals) {
            fputcsv($out, array(), ';');
            fputcsv($out, array(tt__('Total'), '', '', '', '', '', '', '',
                $minutes, number_format(TimeTrackEntry::toHours($minutes), 2, '.', '')), ';');
            fputcsv($out, array(tt__('Billable total'), '', '', '', '', '', '', '',
                $billable, number_format(TimeTrackEntry::toHours($billable), 2, '.', '')), ';');
        }
        fclose($out);
        exit;
    }

    function reportCsv() {
        if (!$this->canSeeReports())
            Http::response(403, tt__('Access denied'));

        $criteria = $this->criteriaFromRequest();
        $this->streamCsv($this->csvRows($criteria),
            sprintf('time-%s_%s.csv', $criteria['from'], $criteria['to']));
    }

    /* ===============================================================
       REST API
       =============================================================== */

    function apiTicket($id, $format) {
        $this->requireApiKey();

        $ticket = Ticket::lookup((int) $id);
        if (!$ticket)
            Http::response(404, 'No such ticket');

        $totals = TimeTrackEntry::totalsForTicket($ticket->getId());
        $entries = array();
        foreach (TimeTrackEntry::objects()->filter(array('ticket_id' => $ticket->getId()))
                ->order_by('created') as $r) {
            $entries[] = array(
                'id'         => (int) $r->id,
                'entry_id'   => (int) $r->thread_entry_id,
                'agent'      => (string) $r->getStaffName(),
                'minutes'    => $r->getMinutes(),
                'hours'      => $r->getHours(),
                'type'       => $r->getType(),
                'billable'   => $r->isBillable(),
                'created'    => $r->getCreated(),
            );
        }

        return $this->jsonResponse(array(
            'ticket'           => $ticket->getNumber(),
            'ticket_id'        => (int) $ticket->getId(),
            'total_minutes'    => $totals['total'],
            'total_hours'      => TimeTrackEntry::toHours($totals['total']),
            'billable_minutes' => $totals['billable'],
            'billable_hours'   => TimeTrackEntry::toHours($totals['billable']),
            'by_type'          => $totals['by_type'],
            'entries'          => $entries,
        ));
    }

    function apiReport($format) {
        $this->requireApiKey();

        $criteria = array(
            'from'     => $_GET['from'] ?? date('Y-m-01'),
            'to'       => $_GET['to'] ?? date('Y-m-d'),
            'org_id'   => $_GET['org_id'] ?? '',
            'staff_id' => $_GET['staff_id'] ?? '',
            'billable' => isset($_GET['billable']) ? $_GET['billable'] : '',
            'type'     => $_GET['type'] ?? '',
        );
        $rows = $this->csvRows($criteria);

        if ($format == 'csv')
            $this->streamCsv($rows,
                sprintf('time-%s_%s.csv', $criteria['from'], $criteria['to']));

        $minutes = 0; $billable = 0;
        $no = tt__('No');
        foreach ($rows as $r) {
            $minutes += $r['minutes'];
            if ($r['billable'] !== $no)
                $billable += $r['minutes'];
        }

        return $this->jsonResponse(array(
            'from'             => $criteria['from'],
            'to'               => $criteria['to'],
            'total_minutes'    => $minutes,
            'total_hours'      => TimeTrackEntry::toHours($minutes),
            'billable_minutes' => $billable,
            'billable_hours'   => TimeTrackEntry::toHours($billable),
            'count'            => count($rows),
            'entries'          => $rows,
        ));
    }

    function requireApiKey() {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!$key || !($k = API::lookupByKey($key, osTicket::get_client_ip()))
                || !$k->isActive())
            Http::response(401, 'Valid API key required');
        return $k;
    }

    function jsonResponse($data) {
        Http::response(200, JsonDataEncoder::encode($data), 'application/json');
        exit;
    }

    /* ===============================================================
       Editing
       =============================================================== */

    function editDialog($id) {
        global $ost, $cfg, $thisstaff;
        if (!$thisstaff)
            Http::response(403, tt__('Access denied'));

        $rec = TimeTrackEntry::lookup((int) $id);
        if (!$rec)
            Http::response(404, tt__('No such time entry'));

        $types = $this->getTypes();
        $notes_billable = (bool) $this->getConfig()->get('notes_billable');
        ob_start();
        include 'templates/edit.tmpl.php';
        return ob_get_clean();
    }

    function editSave($id) {
        global $thisstaff;
        if (!$thisstaff)
            Http::response(403, tt__('Access denied'));

        $rec = TimeTrackEntry::lookup((int) $id);
        if (!$rec)
            Http::response(404, tt__('No such time entry'));

        $minutes = TimeTrackEntry::parseMinutes($_POST['tt_minutes'] ?? '');
        if ($minutes === false || $minutes === null)
            Http::response(422, tt__('Invalid time'));

        $types = $this->getTypes();
        $type = $_POST['tt_type'] ?? $types[0];

        $rec->minutes  = TimeTrackEntry::roundUp($minutes, $this->getConfig()->get('rounding'));
        $rec->type     = in_array($type, $types) ? $type : $types[0];
        $rec->billable = !empty($_POST['tt_billable']) ? 1 : 0;
        $rec->updated  = SqlFunction::NOW();
        $rec->save();

        Http::response(201, JsonDataEncoder::encode(array('success' => true)), 'application/json');
        exit;
    }
}
?>
