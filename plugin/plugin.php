<?php
/*********************************************************************
    plugin.php

    Registration data for the Time Tracking plugin.

    Time Tracking plugin for osTicket
    Released under the GNU General Public License version 2
**********************************************************************/
return array(
    'id'          => 'osticket:timetrack', # notrans
    'version'     => '1.0.0',
    'ost_version' => '1.18',
    'name'        => /* trans */ 'Time Tracking',
    'author'      => 'Bootable Computación',
    'description' => /* trans */ 'Logs time spent per reply and internal note,
    with time types and a billable flag. Reports on screen, as CSV and over
    the REST API. Stores its data in its own table: no core table is altered.',
    'url'         => 'https://github.com/bootablearg/osticket-timetrack',
    'plugin'      => 'timetrack.php:TimeTrackPlugin',
);
?>
