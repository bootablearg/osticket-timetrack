<?php
/**
 * Spanish (Argentina) translation — Time Tracking plugin for osTicket
 *
 * To add a language: copy this file, rename it to your locale (pt_BR.php,
 * fr.php, de.php ...) and translate the values on the right. Nothing to
 * compile — the plugin picks it up from the agent's language setting.
 *
 * GNU GPL v2
 */
return array(
    // Settings
    'Track time'                => 'Registrar tiempos',
    'Adds the time field to replies and internal notes.'
                                => 'Agrega el campo de tiempo a las respuestas y notas internas.',
    'Time types'                => 'Tipos de tiempo',
    'One per line. The first one is the default.'
                                => 'Uno por línea. El primero es el que viene por defecto.',
    'Billable by default'       => 'Facturable por defecto',
    'Leaves the "Billable" box ticked when the form opens.'
                                => 'Deja tildada la casilla "Facturable" al abrir el formulario.',
    'Time is required'          => 'Tiempo obligatorio',
    'Rejects replies and notes posted without time.'
                                => 'Rechaza respuestas y notas sin tiempo cargado.',
    'Rounding'                  => 'Redondeo',
    'No rounding'               => 'Sin redondeo',
    '5 minutes'                 => '5 minutos',
    '15 minutes'                => '15 minutos',
    '30 minutes'                => '30 minutos',
    '1 hour'                    => '1 hora',
    'Rounded up on save.'       => 'Se redondea hacia arriba al guardar.',
    'Allow billing internal notes' => 'Permitir facturar notas internas',
    'When off, time on internal notes is recorded but never billable.'
                                => 'Si está apagado, el tiempo de las notas internas se registra pero nunca se factura.',
    'Reports for admins only'   => 'Reportes solo para administradores',
    'When off, any agent can open the time report.'
                                => 'Si está apagado, cualquier agente puede abrir el reporte de tiempos.',
    'Configuration updated'     => 'Configuración actualizada',

    // Ticket view
    'Time'                      => 'Tiempo',
    'Billable'                  => 'Facturable',
    'billable'                  => 'facturable',
    'Time logged'               => 'Tiempo registrado',
    'minutes — or 1:30 / 1h30'  => 'minutos — o 1:30 / 1h30',
    'Enter the time spent before posting.'
                                => 'Cargá el tiempo dedicado antes de publicar.',
    'That time is not readable. Try 90, 1:30 or 1h30.'
                                => 'No entiendo ese tiempo. Usá 90, 1:30 o 1h30.',

    // Report
    'Between'                   => 'Entre',
    'and'                       => 'y',
    'Organization'              => 'Organización',
    'No organization'           => 'Sin organización',
    'Agent'                     => 'Agente',
    'All'                       => 'Todos',
    'Type'                      => 'Tipo',
    'Untyped'                   => 'Sin tipo',
    'Billable and non-billable' => 'Facturable y no',
    'Billable only'             => 'Solo facturable',
    'Non-billable only'         => 'Solo no facturable',
    'Filter'                    => 'Filtrar',
    'Export CSV'                => 'Exportar CSV',
    'Date'                      => 'Fecha',
    'Ticket'                    => 'Ticket',
    'Subject'                   => 'Asunto',
    'User'                      => 'Usuario',
    'Bill.'                     => 'Fact.',
    'Hours'                     => 'Horas',
    'Minutes'                   => 'Minutos',
    'Total'                     => 'Total',
    'Billable total'            => 'Total facturable',
    'By organization'           => 'Por organización',
    'By type'                   => 'Por tipo',
    'No time logged for those filters'
                                => 'No hay tiempos registrados con esos filtros',
    '%d entry — %s (%s billable)'   => '%d registro — %s (%s facturable)',
    '%d entries — %s (%s billable)' => '%d registros — %s (%s facturable)',

    // Edit dialog
    'Edit time'                 => 'Editar tiempo',
    'Count towards billing'     => 'Computar en la facturación',
    'Save'                      => 'Guardar',
    'Cancel'                    => 'Cancelar',
    'Invalid time'              => 'Tiempo inválido',
    'No such time entry'        => 'No existe ese registro',

    // Misc
    'Yes'                       => 'Sí',
    'No'                        => 'No',
    'System'                    => 'Sistema',
    'Access denied'             => 'Acceso denegado',
);
?>
