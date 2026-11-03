<?php
/**
 * German translation — Time Tracking plugin for osTicket
 *
 *
 * GNU GPL v2
 */
return array(
    
    // Settings
    'Track time'                => 'Zeiterfassung',
    'Adds the time field to replies and internal notes.'
                                => 'Fügt Antworten und internen Notizen ein Feld zur Zeiterfassung hinzu.',
    'Time types'                => 'Zeitarten',
    'One per line. The first one is the default.'
                                => 'Eine Zeitart pro Zeile. Die erste wird als Standard verwendet.',
    'Billable by default'       => 'Standardmäßig abrechenbar',
    'Leaves the "Billable" box ticked when the form opens.'
                                => 'Aktiviert beim Öffnen des Formulars standardmäßig die Option "Abrechenbar".',
    'Time is required'          => 'Zeitangabe erforderlich',
    'Rejects replies and notes posted without time.'
                                => 'Verhindert das Speichern von Antworten und Notizen ohne Zeitangabe.',
    'Rounding'                  => 'Rundung',
    'No rounding'               => 'Keine Rundung',
    '5 minutes'                 => '5 Minuten',
    '15 minutes'                => '15 Minuten',
    '30 minutes'                => '30 Minuten',
    '1 hour'                    => '1 Stunde',
    'Rounded up on save.'       => 'Die erfasste Zeit wird beim Speichern aufgerundet.',
    'Allow billing internal notes' => 'Abrechnung interner Notizen erlauben',
    'When off, time on internal notes is recorded but never billable.'
                                => 'Wenn deaktiviert, wird die Zeit für interne Notizen erfasst, jedoch nicht als abrechenbar markiert.',
    'Reports for admins only'   => 'Berichte nur für Administratoren',
    'When off, any agent can open the time report.'
                                => 'Wenn deaktiviert, können alle Agenten den Zeiterfassungsbericht einsehen.',
    'Configuration updated'     => 'Konfiguration aktualisiert',

    // Ticket view
    'Time'                      => 'Zeit',
    'Billable'                  => 'Abrechenbar',
    'billable'                  => 'abrechenbar',
    'Time logged'               => 'Erfasste Zeit',
    'minutes — or 1:30 / 1h30'  => 'Minuten – oder 1:30 / 1h30',
    'Enter the time spent before posting.'
                                => 'Bitte geben Sie die aufgewendete Zeit ein, bevor Sie den Beitrag speichern.',
    'That time is not readable. Try 90, 1:30 or 1h30.'
                                => 'Ungültige Zeitangabe. Verwenden Sie beispielsweise 90, 1:30 oder 1h30.',

    // Report
    'Between'                   => 'Zwischen',
    'and'                       => 'und',
    'Organization'              => 'Organisation',
    'No organization'           => 'Keine Organisation',
    'Agent'                     => 'Agent',
    'All'                       => 'Alle',
    'Type'                      => 'Zeitart',
    'Untyped'                   => 'Ohne Zeitart',
    'Billable and non-billable' => 'Abrechenbar und nicht abrechenbar',
    'Billable only'             => 'Nur abrechenbar',
    'Non-billable only'         => 'Nur nicht abrechenbar',
    'Filter'                    => 'Filtern',
    'Export CSV'                => 'CSV exportieren',
    'Date'                      => 'Datum',
    'Ticket'                    => 'Ticket',
    'Subject'                   => 'Betreff',
    'User'                      => 'Benutzer',
    'Bill.'                     => 'Abrech.',
    'Hours'                     => 'Stunden',
    'Minutes'                   => 'Minuten',
    'Total'                     => 'Gesamt',
    'Billable total'            => 'Abrechenbare Gesamtzeit',
    'By organization'           => 'Nach Organisation',
    'By type'                   => 'Nach Zeitart',
    'No time logged for those filters'
                                => 'Für die ausgewählten Filter wurden keine Zeiten erfasst.',
    '%d entry — %s (%s billable)'   
                                => '%d Eintrag – %s (%s abrechenbar)',
    '%d entries — %s (%s billable)' 
                                => '%d Einträge – %s (%s abrechenbar)',

    // Edit dialog
    'Edit time'                 => 'Zeit bearbeiten',
    'Count towards billing'     => 'Für die Abrechnung berücksichtigen',
    'Save'                      => 'Speichern',
    'Cancel'                    => 'Abbrechen',
    'Invalid time'              => 'Ungültige Zeitangabe',
    'No such time entry'        => 'Zeiteintrag nicht gefunden',

    // Misc
    'Yes'                       => 'Ja',
    'No'                        => 'Nein',
    'System'                    => 'System',
    'Access denied'             => 'Zugriff verweigert',

);
?>
