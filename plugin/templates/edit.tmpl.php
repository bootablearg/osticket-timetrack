<?php
/**
 * Dialog to edit an already recorded time entry.
 * Vars: $rec $types $notes_billable
 *
 * Time Tracking plugin for osTicket — GNU GPL v2
 */
if (!$thisstaff) die('Access Denied');
$action = ROOT_PATH . 'scp/ajax.php/timetrack/entry/' . $rec->id;
?>
<h3 class="drag-handle"><?php echo tt__('Edit time'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<div style="display:block; margin:5px;">
<form method="post" name="timetrack" id="timetrack-edit" action="<?php echo $action; ?>">
    <?php csrf_token(); ?>
    <table width="100%">
        <tbody>
            <tr>
                <td width="120"><label><strong><?php echo tt__('Time'); ?>:</strong></label></td>
                <td>
                    <input type="text" name="tt_minutes" size="8" autocomplete="off"
                        value="<?php echo (int) $rec->getMinutes(); ?>">
                    <span class="faded"><?php echo tt__('minutes — or 1:30 / 1h30'); ?></span>
                </td>
            </tr>
            <tr>
                <td width="120"><label><strong><?php echo tt__('Type'); ?>:</strong></label></td>
                <td>
                    <select name="tt_type">
                        <?php foreach ($types as $t) {
                            printf('<option value="%s" %s>%s</option>',
                                Format::htmlchars($t),
                                ($rec->getType() === $t) ? 'selected="selected"' : '',
                                Format::htmlchars($t));
                        } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td width="120"><label><strong><?php echo tt__('Billable'); ?>:</strong></label></td>
                <td>
                    <label><input type="checkbox" name="tt_billable" value="1"
                        <?php echo $rec->isBillable() ? 'checked="checked"' : ''; ?>>
                        <?php echo tt__('Count towards billing'); ?></label>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" name="cancel" class="close" value="<?php echo tt__('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo tt__('Save'); ?>">
        </span>
    </p>
</form>
</div>
<div class="clear"></div>
