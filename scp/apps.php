<?php
/*********************************************************************
    apps.php

    Entry point for applications registered by plugins.

    osTicket builds the "Applications" tab pointing at this file
    (include/class.nav.php:143) but NEVER ships it: as soon as a plugin calls
    Application::registerStaffApp() the tab shows up and 404s.

    On top of that, scp/apps/dispatcher.php refuses direct requests
    (die('Access denied') when SCRIPT_NAME is the dispatcher itself), so the
    only way in is to include it from here. Hence apps are served as
    /scp/apps.php/<app>/ and not /scp/apps/dispatcher.php/<app>/.

    Time Tracking plugin for osTicket — GNU GPL v2
**********************************************************************/

// With path info (/scp/apps.php/timetrack/) the apps dispatcher takes over
if (trim((string) @$_SERVER['PATH_INFO'], '/') !== '') {
    require('apps/dispatcher.php');
    return;
}

// No path info: index of whatever is registered
require('staff.inc.php');

$apps = Application::getStaffApps() ?: array();

if (count($apps) == 1) {
    session_write_close();
    header('Location: ' . $apps[0]['href']);
    exit;
}

$nav->setTabActive('apps');
require(STAFFINC_DIR . 'header.inc.php');
?>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Applications'); ?></h2>
            </div>
        </div>
    </div>
</div>
<table class="list" style="width:100%">
    <thead>
        <tr><th><?php echo __('Application'); ?></th></tr>
    </thead>
    <tbody>
<?php if (!$apps) { ?>
        <tr><td><?php echo __('No applications registered'); ?></td></tr>
<?php } else {
        foreach ($apps as $app) { ?>
        <tr>
            <td><a href="<?php echo Format::htmlchars($app['href']); ?>"><?php
                echo Format::htmlchars($app['desc']); ?></a></td>
        </tr>
<?php   }
      } ?>
    </tbody>
</table>
<?php
include(STAFFINC_DIR . 'footer.inc.php');
?>
