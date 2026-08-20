<?php
/*********************************************************************
    class.ttcolumn.php

    Queue column annotation: shows the time recorded on each ticket of a
    ticket listing.

    It registers itself: QueueColumnAnnotation::getAnnotations() walks
    get_declared_classes(), and the plugin is loaded during bootstrap.

    Time Tracking plugin for osTicket
    Released under the GNU General Public License version 2
**********************************************************************/
require_once('class.tti18n.php');

// The core loads class.queue.php during bootstrap, before plugins. If that
// order ever changes, skipping the class is better than a fatal that takes
// the whole help desk down.
if (class_exists('QueueColumnAnnotation')) {

class TimeTrackAnnotation extends QueueColumnAnnotation {
    static $icon = 'time';
    static $qname = '_tt_minutes';
    static $desc = /* @trans */ 'Time logged';

    // Totals are resolved with one query per page, not one per row.
    // See TimeTrackEntry::minutesForTickets().
    static $cache = null;

    static function annotate($query, $name=false) {
        // The total does not come from the core query: the table is ours.
        return $query;
    }

    static function getDescription() {
        return tt__(static::$desc);
    }

    static function preload($ticket_ids) {
        static::$cache = TimeTrackEntry::minutesForTickets($ticket_ids);
    }

    function totalsFor($ticket_id) {
        $ticket_id = (int) $ticket_id;

        if (!isset(static::$cache[$ticket_id])) {
            if (static::$cache === null)
                static::$cache = array();
            $mins = TimeTrackEntry::minutesForTicket($ticket_id);
            static::$cache[$ticket_id] = array(
                'total' => $mins,
                'billable' => TimeTrackEntry::minutesForTicket($ticket_id, true),
            );
        }
        return static::$cache[$ticket_id];
    }

    function getDecoration($row, $text) {
        if (!isset($row['ticket_id']))
            return '';

        $t = $this->totalsFor($row['ticket_id']);
        if (!$t['total'])
            return '';

        $title = sprintf('%s: %s', tt__('Time logged'),
            TimeTrackEntry::formatMinutes($t['total']));
        if ($t['billable'] && $t['billable'] != $t['total'])
            $title .= sprintf(' (%s %s)',
                TimeTrackEntry::formatMinutes($t['billable']), tt__('billable'));

        return sprintf('<span class="pull-right faded-more" data-toggle="tooltip" title="%s">'
            . '<i class="icon-%s"></i> %s</span>',
            Format::htmlchars($title), static::$icon,
            Format::htmlchars(TimeTrackEntry::formatMinutes($t['total'])));
    }

    function isVisible($row) {
        return isset($row['ticket_id'])
            && $this->totalsFor($row['ticket_id'])['total'] > 0;
    }

    function getWidth($row) {
        return $this->isVisible($row) ? 60 : 0;
    }
}

}
?>
