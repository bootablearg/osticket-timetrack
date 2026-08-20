<?php
/*********************************************************************
    config.php

    Settings for the Time Tracking plugin.

    Time Tracking plugin for osTicket
    Released under the GNU General Public License version 2
**********************************************************************/
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.forms.php');
require_once('class.tti18n.php');

class TimeTrackConfig extends PluginConfig {

    function getOptions() {
        return array(
            'enabled' => new BooleanField(array(
                'id' => 'enabled',
                'label' => tt__('Track time'),
                'default' => true,
                'configuration' => array(
                    'desc' => tt__('Adds the time field to replies and internal notes.'),
                ),
            )),
            'types' => new TextareaField(array(
                'id' => 'types',
                'label' => tt__('Time types'),
                'default' => "Remote\nOn-site\nTravel\nWorkshop",
                'hint' => tt__('One per line. The first one is the default.'),
                'configuration' => array(
                    'html' => false,
                    'rows' => 6,
                    'cols' => 30,
                ),
            )),
            'billable_default' => new BooleanField(array(
                'id' => 'billable_default',
                'label' => tt__('Billable by default'),
                'default' => true,
                'configuration' => array(
                    'desc' => tt__('Leaves the "Billable" box ticked when the form opens.'),
                ),
            )),
            'required' => new BooleanField(array(
                'id' => 'required',
                'label' => tt__('Time is required'),
                'default' => false,
                'configuration' => array(
                    'desc' => tt__('Rejects replies and notes posted without time.'),
                ),
            )),
            'rounding' => new ChoiceField(array(
                'id' => 'rounding',
                'label' => tt__('Rounding'),
                'default' => 0,
                'choices' => array(
                    0  => tt__('No rounding'),
                    5  => tt__('5 minutes'),
                    15 => tt__('15 minutes'),
                    30 => tt__('30 minutes'),
                    60 => tt__('1 hour'),
                ),
                'hint' => tt__('Rounded up on save.'),
            )),
            'notes_billable' => new BooleanField(array(
                'id' => 'notes_billable',
                'label' => tt__('Allow billing internal notes'),
                'default' => false,
                'configuration' => array(
                    'desc' => tt__('When off, time on internal notes is recorded but never billable.'),
                ),
            )),
            'report_admins_only' => new BooleanField(array(
                'id' => 'report_admins_only',
                'label' => tt__('Reports for admins only'),
                'default' => true,
                'configuration' => array(
                    'desc' => tt__('When off, any agent can open the time report.'),
                ),
            )),
        );
    }

    function pre_save(&$config, &$errors) {
        global $msg;
        if (!$errors)
            $msg = tt__('Configuration updated');
        return true;
    }
}
?>
