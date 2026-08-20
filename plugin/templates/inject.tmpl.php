<?php
/**
 * Injects the time field into the reply and internal note forms, and shows
 * what has already been logged on the thread.
 *
 * Emitted from the object.view signal, which fires before the DOM exists,
 * so everything runs inside $(function(){...}).
 *
 * Vars: $ticket $types $billable $required $notes_billable $data $totals
 *
 * Time Tracking plugin for osTicket — GNU GPL v2
 */
$tt_json = JsonDataEncoder::encode(array(
    'entries'  => $data,
    'total'    => TimeTrackEntry::formatMinutes($totals['total']),
    'billable' => TimeTrackEntry::formatMinutes($totals['billable']),
    'has'      => (bool) $totals['total'],
    'required' => $required,
    'notesBillable' => $notes_billable,
    'labels'   => array(
        'time'      => tt__('Time'),
        'billable'  => tt__('Billable'),
        'total'     => tt__('Time logged'),
        'ofWhich'   => tt__('billable'),
        'required'  => tt__('Enter the time spent before posting.'),
        'invalid'   => tt__('That time is not readable. Try 90, 1:30 or 1h30.'),
        'hint'      => tt__('minutes — or 1:30 / 1h30'),
    ),
));

$options = '';
foreach ($types as $t)
    $options .= sprintf('<option value="%s">%s</option>',
        Format::htmlchars($t), Format::htmlchars($t));
?>
<script type="text/javascript">
$(function() {
    var TT = <?php echo $tt_json; ?>;
    var OPTS = <?php echo JsonDataEncoder::encode($options); ?>;
    var CHECKED = <?php echo $billable ? 'true' : 'false'; ?>;

    // --- Form row, using the same markup as the rest of the form ---
    function fieldRow(kind) {
        var billable = (kind === 'note' && !TT.notesBillable)
            ? ''
            : '<label style="margin-left:12px">'
                + '<input type="checkbox" name="tt_billable" value="1"'
                + (CHECKED ? ' checked="checked"' : '') + '> '
                + TT.labels.billable + '</label>';

        return $('<tr>').append(
            $('<td>').attr('width', '120').css('vertical-align', 'top').html(
                '<label><strong>' + TT.labels.time + ':</strong>'
                + (TT.required ? '<span class="error">&nbsp;*</span>' : '')
                + '</label>'),
            $('<td>').html(
                '<input type="text" name="tt_minutes" size="6" autocomplete="off"'
                + ' placeholder="90">'
                + ' <select name="tt_type" style="margin-left:4px">' + OPTS + '</select>'
                + billable
                + '<div class="faded" style="padding-left:0.15em">' + TT.labels.hint + '</div>'
                + '<div class="error tt-error"></div>')
        );
    }

    $.each({reply: '#reply', note: '#note'}, function(kind, sel) {
        var $form = $(sel);
        if (!$form.length || $form.find('input[name=tt_minutes]').length)
            return;

        var $table = $form.children('table').first();
        var $body = $table.children('tbody').last();
        ($body.length ? $body : $table).append(fieldRow(kind));

        // Validate on submit: the core knows nothing about this field
        $form.on('submit', function(e) {
            var $in = $form.find('input[name=tt_minutes]'),
                v = $.trim($in.val()),
                $err = $form.find('.tt-error');

            $err.text('');
            if (!v) {
                if (!TT.required) return true;
                $err.text(TT.labels.required);
                $in.focus();
                e.preventDefault();
                return false;
            }
            if (!/^(\d+([.,]\d+)?\s*(m(in(ute)?s?)?|h)?|\d+:[0-5]\d|\d+\s*h\s*\d*\s*m?)$/i.test(v)) {
                $err.text(TT.labels.invalid);
                $in.focus();
                e.preventDefault();
                return false;
            }
            return true;
        });
    });

    // --- What is already logged, next to each thread entry author ---
    $.each(TT.entries, function(entryId, rec) {
        var $anchor = $('a[name="entry-' + entryId + '"]');
        if (!$anchor.length) return;

        $anchor.closest('div').find('.tt-badge').remove();
        $('<span>')
            .addClass('label label-bare tt-badge')
            .css('margin-left', '6px')
            .attr('title', rec.type + (rec.billable ? ' — ' + TT.labels.billable : ''))
            .attr('data-toggle', 'tooltip')
            .html('<i class="icon-time"></i> ' + rec.pretty
                + (rec.billable ? ' <i class="icon-dollar"></i>' : ''))
            .insertAfter($anchor);
    });

    // --- Total, as one more row of the ticket summary ---
    if (TT.has) {
        var $info = $('table.ticket_info').first(),
            $row = $('<tr>').append(
                $('<th>').text(TT.labels.total + ':'),
                $('<td>').html('<b>' + TT.total + '</b>'
                    + (TT.billable !== TT.total
                        ? ' <span class="faded">(' + TT.billable + ' ' + TT.labels.ofWhich + ')</span>'
                        : ''))
            );
        $info.children('tbody').last().append($row);
    }
});
</script>
