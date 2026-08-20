<?php
/**
 * Time report — staff panel.
 * Vars: $criteria $rows $types $thisstaff $nav
 *
 * Time Tracking plugin for osTicket — GNU GPL v2
 */
if (!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->isAdmin())
    die('Access Denied');

$base = ROOT_PATH . 'scp/apps.php/timetrack/';

// Single pass: rows and totals are built together
$list = array();
$total = 0; $billable = 0; $by_type = array(); $by_org = array();
foreach ($rows as $r) {
    $ticket = $r->getTicket();
    $user = $ticket ? $ticket->getOwner() : null;
    $org  = $user ? $user->getOrganization() : null;

    $list[] = array('rec' => $r, 'ticket' => $ticket, 'user' => $user, 'org' => $org);

    $total += $r->getMinutes();
    if ($r->isBillable())
        $billable += $r->getMinutes();

    $t = $r->getType() ?: tt__('Untyped');
    $by_type[$t] = ($by_type[$t] ?? 0) + $r->getMinutes();

    $o = $org ? (string) $org->getName() : tt__('No organization');
    $by_org[$o] = ($by_org[$o] ?? 0) + $r->getMinutes();
}
arsort($by_org);

require_once STAFFINC_DIR . 'header.inc.php';
?>
<div style="margin-bottom:15px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo tt__('Time logged'); ?></h2>
            </div>
            <div class="pull-right flush-right">
                <!-- no-pjax is mandatory: without it pjax hijacks the click
                     (scp/js/scp.js:1188), fetches the CSV over XHR and injects
                     it into the page instead of downloading it. -->
                <a class="green button action-button no-pjax"
                   href="<?php echo $base . 'csv?' . Http::build_query($criteria); ?>">
                   <i class="icon-download-alt"></i> <?php echo tt__('Export CSV'); ?></a>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>

<form action="<?php echo $base; ?>" method="get" style="margin-bottom:15px">
    <table class="ticket_info" cellspacing="0" cellpadding="0" width="100%" border="0">
        <tbody>
            <tr>
                <th width="60"><?php echo tt__('Between'); ?>:</th>
                <td width="290">
                    <input class="tt-dp" size="12" name="from" autocomplete="off"
                        value="<?php echo Format::htmlchars($criteria['from']); ?>">
                    &nbsp;<span class="faded"><?php echo tt__('and'); ?></span>&nbsp;
                    <input class="tt-dp" size="12" name="to" autocomplete="off"
                        value="<?php echo Format::htmlchars($criteria['to']); ?>">
                </td>
                <th width="90"><?php echo tt__('Organization'); ?>:</th>
                <td>
                    <select name="org_id">
                        <option value="">&mdash; <?php echo tt__('All'); ?> &mdash;</option>
                        <?php
                        foreach (Organization::objects()->order_by('name') as $o) {
                            printf('<option value="%d" %s>%s</option>', $o->getId(),
                                ($criteria['org_id'] == $o->getId()) ? 'selected="selected"' : '',
                                Format::htmlchars($o->getName()));
                        } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php echo tt__('Agent'); ?>:</th>
                <td>
                    <select name="staff_id">
                        <option value="">&mdash; <?php echo tt__('All'); ?> &mdash;</option>
                        <?php
                        foreach (Staff::objects()->order_by('firstname') as $st) {
                            printf('<option value="%d" %s>%s</option>', $st->getId(),
                                ($criteria['staff_id'] == $st->getId()) ? 'selected="selected"' : '',
                                Format::htmlchars($st->getName()));
                        } ?>
                    </select>
                </td>
                <th><?php echo tt__('Type'); ?>:</th>
                <td>
                    <select name="type">
                        <option value="">&mdash; <?php echo tt__('All'); ?> &mdash;</option>
                        <?php
                        foreach ($types as $t) {
                            printf('<option value="%s" %s>%s</option>', Format::htmlchars($t),
                                ($criteria['type'] === $t) ? 'selected="selected"' : '',
                                Format::htmlchars($t));
                        } ?>
                    </select>
                    &nbsp;
                    <select name="billable">
                        <option value="" <?php echo ($criteria['billable'] === '') ? 'selected="selected"' : ''; ?>><?php echo tt__('Billable and non-billable'); ?></option>
                        <option value="1" <?php echo ($criteria['billable'] === '1') ? 'selected="selected"' : ''; ?>><?php echo tt__('Billable only'); ?></option>
                        <option value="0" <?php echo ($criteria['billable'] === '0') ? 'selected="selected"' : ''; ?>><?php echo tt__('Non-billable only'); ?></option>
                    </select>
                    &nbsp;&nbsp;
                    <input type="submit" class="button" value="<?php echo tt__('Filter'); ?>">
                </td>
            </tr>
        </tbody>
    </table>
</form>

<script type="text/javascript">
// The core datepicker is set up with buttonImage './images/cal.png', a
// relative path that under PATH_INFO (/scp/apps.php/timetrack/) points at a
// file that does not exist and leaves two broken icons. Same widget, no button.
$(function() {
    $('.tt-dp').datepicker({ dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true });
});
</script>

<div class="pull-right" style="margin-top:5px;">
    <strong><?php echo sprintf(tt_n('%d entry — %s (%s billable)',
            '%d entries — %s (%s billable)', count($list)),
        count($list),
        TimeTrackEntry::formatMinutes($total),
        TimeTrackEntry::formatMinutes($billable)); ?></strong>
</div>
<div class="clear"></div>

<table class="list" id="timetrack-list" style="width:100%">
    <thead>
        <tr>
            <th width="90"><?php echo tt__('Date'); ?></th>
            <th width="80"><?php echo tt__('Ticket'); ?></th>
            <th><?php echo tt__('Subject'); ?></th>
            <th width="160"><?php echo tt__('Organization'); ?></th>
            <th width="130"><?php echo tt__('Agent'); ?></th>
            <th width="90"><?php echo tt__('Type'); ?></th>
            <th width="70" style="text-align:center"><?php echo tt__('Bill.'); ?></th>
            <th width="80" style="text-align:right"><?php echo tt__('Time'); ?></th>
            <th width="60" style="text-align:right"><?php echo tt__('Hours'); ?></th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($list as $row) {
        $r = $row['rec']; $ticket = $row['ticket']; ?>
        <tr>
            <td><?php echo Format::date($r->getCreated(), true, 'yyyy-MM-dd'); ?></td>
            <td><?php
                if ($ticket)
                    printf('<a href="%stickets.php?id=%d">%s</a>', ROOT_PATH . 'scp/',
                        $ticket->getId(), Format::htmlchars($ticket->getNumber()));
                else
                    echo '&mdash;'; ?></td>
            <td><?php echo $ticket ? Format::htmlchars($ticket->getSubject()) : '&mdash;'; ?></td>
            <td><?php echo $row['org'] ? Format::htmlchars($row['org']->getName()) : '&mdash;'; ?></td>
            <td><?php echo Format::htmlchars($r->getStaffName()); ?></td>
            <td><?php echo Format::htmlchars($r->getType()); ?></td>
            <td style="text-align:center"><?php
                echo $r->isBillable()
                    ? '<i class="icon-ok" title="' . tt__('Billable') . '"></i>'
                    : '&mdash;'; ?></td>
            <td style="text-align:right"><?php echo $r->getPretty(); ?></td>
            <td style="text-align:right"><?php echo number_format($r->getHours(), 2); ?></td>
        </tr>
<?php } ?>
    </tbody>
    <tfoot>
<?php if (!$list) { ?>
        <tr><td colspan="9"><?php echo tt__('No time logged for those filters'); ?></td></tr>
<?php } else { ?>
        <tr>
            <td colspan="7" style="text-align:right"><b><?php echo tt__('Total'); ?></b></td>
            <td style="text-align:right"><b><?php echo TimeTrackEntry::formatMinutes($total); ?></b></td>
            <td style="text-align:right"><b><?php echo number_format(TimeTrackEntry::toHours($total), 2); ?></b></td>
        </tr>
        <tr>
            <td colspan="7" style="text-align:right"><?php echo tt__('Billable'); ?></td>
            <td style="text-align:right"><?php echo TimeTrackEntry::formatMinutes($billable); ?></td>
            <td style="text-align:right"><?php echo number_format(TimeTrackEntry::toHours($billable), 2); ?></td>
        </tr>
<?php } ?>
    </tfoot>
</table>

<?php if ($list) { ?>
<div class="clear"></div>
<div style="margin-top:25px; display:flex; gap:40px; flex-wrap:wrap">
    <div style="min-width:280px">
        <h3><?php echo tt__('By organization'); ?></h3>
        <table class="list" style="width:100%">
            <thead><tr><th><?php echo tt__('Organization'); ?></th>
                <th width="90" style="text-align:right"><?php echo tt__('Time'); ?></th>
                <th width="60" style="text-align:right"><?php echo tt__('Hours'); ?></th></tr></thead>
            <tbody>
<?php foreach ($by_org as $name => $min) { ?>
                <tr><td><?php echo Format::htmlchars($name); ?></td>
                    <td style="text-align:right"><?php echo TimeTrackEntry::formatMinutes($min); ?></td>
                    <td style="text-align:right"><?php echo number_format(TimeTrackEntry::toHours($min), 2); ?></td></tr>
<?php } ?>
            </tbody>
        </table>
    </div>
    <div style="min-width:280px">
        <h3><?php echo tt__('By type'); ?></h3>
        <table class="list" style="width:100%">
            <thead><tr><th><?php echo tt__('Type'); ?></th>
                <th width="90" style="text-align:right"><?php echo tt__('Time'); ?></th>
                <th width="60" style="text-align:right"><?php echo tt__('Hours'); ?></th></tr></thead>
            <tbody>
<?php foreach ($by_type as $name => $min) { ?>
                <tr><td><?php echo Format::htmlchars($name); ?></td>
                    <td style="text-align:right"><?php echo TimeTrackEntry::formatMinutes($min); ?></td>
                    <td style="text-align:right"><?php echo number_format(TimeTrackEntry::toHours($min), 2); ?></td></tr>
<?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } ?>

<?php
require_once STAFFINC_DIR . 'footer.inc.php';
?>
