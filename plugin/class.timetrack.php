<?php
/*********************************************************************
    class.timetrack.php

    Model and queries for time entries.

    The table belongs to the plugin: no core table is altered, so no
    osTicket upgrade can wipe this data.

    Time Tracking plugin for osTicket
    Released under the GNU General Public License version 2
**********************************************************************/
require_once('class.tti18n.php');

define('TIMETRACK_TABLE', TABLE_PREFIX . 'timetrack');

class TimeTrackEntry extends VerySimpleModel {
    static $meta = array(
        'table' => TIMETRACK_TABLE,
        'pk' => array('id'),
        'ordering' => array('-created'),
        'joins' => array(
            'ticket' => array(
                'constraint' => array('ticket_id' => 'Ticket.ticket_id'),
                'null' => true,
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
                'null' => true,
            ),
            'entry' => array(
                'constraint' => array('thread_entry_id' => 'ThreadEntry.id'),
                'null' => true,
            ),
        ),
    );

    /* ---------------------------------------------------------------
       Schema
       --------------------------------------------------------------- */

    static function tableExists() {
        $sql = 'SHOW TABLES LIKE ' . db_input(TIMETRACK_TABLE);
        return ($res = db_query($sql, false)) && db_num_rows($res);
    }

    static function createTable() {
        if (self::tableExists())
            return true;

        $sql = 'CREATE TABLE `' . TIMETRACK_TABLE . '` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `thread_entry_id` int(11) unsigned NOT NULL DEFAULT 0,
            `ticket_id` int(11) unsigned NOT NULL DEFAULT 0,
            `staff_id` int(11) unsigned NOT NULL DEFAULT 0,
            `minutes` int(11) NOT NULL DEFAULT 0,
            `type` varchar(40) NOT NULL DEFAULT "",
            `billable` tinyint(1) unsigned NOT NULL DEFAULT 0,
            `source` varchar(16) NOT NULL DEFAULT "web",
            `invoice` varchar(40) NOT NULL DEFAULT "",
            `created` datetime NOT NULL,
            `updated` datetime NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `thread_entry_id` (`thread_entry_id`),
            KEY `ticket_id` (`ticket_id`),
            KEY `staff_id` (`staff_id`),
            KEY `created` (`created`),
            KEY `billable` (`billable`)
        ) DEFAULT CHARSET=utf8mb4';

        return db_query($sql, false);
    }

    static function dropTable() {
        return db_query('DROP TABLE IF EXISTS `' . TIMETRACK_TABLE . '`', false);
    }

    /* ---------------------------------------------------------------
       Parsing and formatting
       --------------------------------------------------------------- */

    /**
     * Takes whatever the agent typed and returns whole minutes:
     *   90      -> 90      "45m"   -> 45
     *   1:30    -> 90      "2h"    -> 120
     *   1,5h    -> 90      "1h30"  -> 90
     * Returns null when the field is empty, false when it is unreadable.
     */
    static function parseMinutes($value) {
        $v = trim(mb_strtolower((string) $value));
        if ($v === '')
            return null;

        $v = str_replace(',', '.', $v);

        // 1:30 / 01:05
        if (preg_match('/^(\d+):([0-5]?\d)$/', $v, $m))
            return ((int) $m[1]) * 60 + (int) $m[2];

        // 1h30 / 1h30m / 2h
        if (preg_match('/^(\d+(?:\.\d+)?)\s*h(?:\s*(\d+)\s*m?)?$/', $v, $m)) {
            $mins = (float) $m[1] * 60;
            if (isset($m[2]) && $m[2] !== '')
                $mins += (int) $m[2];
            return (int) round($mins);
        }

        // 45m / 45 min
        if (preg_match('/^(\d+(?:\.\d+)?)\s*m(?:in(?:utos?)?)?$/', $v, $m))
            return (int) round((float) $m[1]);

        // bare number means minutes
        if (preg_match('/^\d+(?:\.\d+)?$/', $v))
            return (int) round((float) $v);

        return false; // something was typed, but it is not a duration
    }

    static function roundUp($minutes, $step) {
        $step = (int) $step;
        if ($step < 2 || $minutes <= 0)
            return (int) $minutes;
        return (int) (ceil($minutes / $step) * $step);
    }

    static function formatMinutes($minutes) {
        $minutes = (int) $minutes;
        $sign = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h && $m)  return sprintf('%s%dh %02dm', $sign, $h, $m);
        if ($h)        return sprintf('%s%dh', $sign, $h);
        return sprintf('%s%dm', $sign, $m);
    }

    /** Decimal hours, which is how time gets invoiced */
    static function toHours($minutes) {
        return round(((int) $minutes) / 60, 2);
    }

    /* ---------------------------------------------------------------
       Recording and querying
       --------------------------------------------------------------- */

    static function record($entry, $minutes, $type, $billable, $source='web') {
        if (!$entry || !($minutes = (int) $minutes))
            return false;

        $ticket_id = 0;
        if (($thread = $entry->getThread()) && $thread->getObjectType() == 'T')
            $ticket_id = $thread->getObjectId();

        $rec = static::lookupByEntry($entry->getId()) ?: new static(array(
            'thread_entry_id' => $entry->getId(),
            'created' => SqlFunction::NOW(),
        ));

        $rec->ticket_id = $ticket_id;
        $rec->staff_id  = (int) $entry->staff_id;
        $rec->minutes   = $minutes;
        $rec->type      = (string) $type;
        $rec->billable  = $billable ? 1 : 0;
        $rec->source    = $source;
        $rec->updated   = SqlFunction::NOW();

        return $rec->save() ? $rec : false;
    }

    static function lookupByEntry($entry_id) {
        return static::objects()
            ->filter(array('thread_entry_id' => (int) $entry_id))
            ->first();
    }

    /** array(entry_id => record), used to decorate a ticket thread */
    static function forTicket($ticket_id) {
        $out = array();
        foreach (static::objects()->filter(array('ticket_id' => (int) $ticket_id)) as $r)
            $out[$r->thread_entry_id] = $r;
        return $out;
    }

    /** array('total' => min, 'billable' => min, 'by_type' => array(tipo => min)) */
    static function totalsForTicket($ticket_id) {
        $totals = array('total' => 0, 'billable' => 0, 'by_type' => array());
        foreach (static::objects()->filter(array('ticket_id' => (int) $ticket_id)) as $r) {
            $totals['total'] += $r->minutes;
            if ($r->billable)
                $totals['billable'] += $r->minutes;
            $t = $r->type ?: tt__('Untyped');
            $totals['by_type'][$t] = ($totals['by_type'][$t] ?? 0) + $r->minutes;
        }
        return $totals;
    }

    static function minutesForTicket($ticket_id, $billable_only=false) {
        // No aggregate() on purpose: QuerySet::one() throws DoesNotExist
        // when there are no rows, and a ticket with no time is the norm.
        $total = 0;
        $q = static::objects()->filter(array('ticket_id' => (int) $ticket_id));
        if ($billable_only)
            $q = $q->filter(array('billable' => 1));
        foreach ($q as $r)
            $total += $r->minutes;
        return $total;
    }

    /**
     * Per-ticket totals for a batch of ids in a single query. Used by the
     * queue column: without it, one query per row.
     */
    static function minutesForTickets(array $ticket_ids) {
        $out = array();
        $ids = array_filter(array_map('intval', $ticket_ids));
        if (!$ids)
            return $out;

        $sql = 'SELECT ticket_id, SUM(minutes) AS total, '
             . 'SUM(CASE WHEN billable = 1 THEN minutes ELSE 0 END) AS billable '
             . 'FROM `' . TIMETRACK_TABLE . '` '
             . 'WHERE ticket_id IN (' . implode(',', $ids) . ') '
             . 'GROUP BY ticket_id';

        if (($res = db_query($sql, false))) {
            while ($row = db_fetch_array($res))
                $out[(int) $row['ticket_id']] = array(
                    'total' => (int) $row['total'],
                    'billable' => (int) $row['billable'],
                );
        }
        return $out;
    }

    /**
     * Report query. $criteria accepts:
     *   from, to (Y-m-d), org_id, staff_id, billable (0|1|null), ticket_id
     */
    static function search($criteria=array()) {
        $q = static::objects()
            ->select_related('ticket', 'staff', 'ticket__user', 'ticket__user__org', 'ticket__dept')
            ->order_by('created');

        if (!empty($criteria['from']))
            $q = $q->filter(array('created__gte' => $criteria['from'] . ' 00:00:00'));
        if (!empty($criteria['to']))
            $q = $q->filter(array('created__lte' => $criteria['to'] . ' 23:59:59'));
        if (!empty($criteria['org_id']))
            $q = $q->filter(array('ticket__user__org_id' => (int) $criteria['org_id']));
        if (!empty($criteria['staff_id']))
            $q = $q->filter(array('staff_id' => (int) $criteria['staff_id']));
        if (!empty($criteria['ticket_id']))
            $q = $q->filter(array('ticket_id' => (int) $criteria['ticket_id']));
        if (isset($criteria['billable']) && $criteria['billable'] !== '')
            $q = $q->filter(array('billable' => (int) $criteria['billable']));
        if (!empty($criteria['type']))
            $q = $q->filter(array('type' => $criteria['type']));

        return $q;
    }

    /* ---------------------------------------------------------------
       Accessors
       --------------------------------------------------------------- */

    /**
     * created is a SqlFunction until the row is read back from the database.
     * Within the same request the ORM hands back the cached object, so every
     * read of the date has to go through here.
     */
    function getCreated() {
        return is_object($this->created)
            ? date('Y-m-d H:i:s')
            : $this->created;
    }

    function getMinutes()  { return (int) $this->minutes; }
    function getHours()    { return self::toHours($this->minutes); }
    function getPretty()   { return self::formatMinutes($this->minutes); }
    function getType()     { return $this->type; }
    function isBillable()  { return (bool) $this->billable; }

    function getTicket() {
        return $this->ticket_id ? Ticket::lookup($this->ticket_id) : null;
    }

    function getStaffName() {
        return $this->staff ? (string) $this->staff->getName() : tt__('System');
    }
}
?>
