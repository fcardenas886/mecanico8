<?php
// Piezas de interfaz compartidas por las pantallas del Taller: pasos del proceso,
// estados en lenguaje simple y "qué sigue" para cada orden.

const TALLER_PASOS = [
    1 => ['Recepción', 'fa-right-to-bracket'],
    2 => ['Diagnóstico', 'fa-stethoscope'],
    3 => ['Presupuesto', 'fa-file-invoice-dollar'],
    4 => ['Reparación y cobro', 'fa-screwdriver-wrench'],
    5 => ['Entrega', 'fa-key'],
];

/**
 * Traduce el estado técnico de una OT a lenguaje de taller.
 * Acepta la fila de ordenestrabajo; si trae datos del presupuesto
 * (PresupuestoID, DecisionCliente, CantidadLineasPresupuesto) afina el mensaje.
 *
 * Devuelve: paso (1-5 = paso en curso, 6 = todo terminado), etiqueta, clase de badge,
 * grupo de filtro, rechazado, y la acción principal (texto + url + clase).
 */
function otEstadoInfo(array $ot): array {
    $id = (int)$ot['OrdenTrabajoID'];
    $estado = $ot['Estado'];
    $cobrada = !empty($ot['VentaID']) || !empty($ot['ManoObraCobrada']);
    $tienePresupuesto = !empty($ot['PresupuestoID']) && (int)($ot['CantidadLineasPresupuesto'] ?? 0) > 0;
    $pendiente = $tienePresupuesto && ($ot['DecisionCliente'] ?? '') === 'Pendiente';

    $info = [
        'paso' => 2, 'etiqueta' => $estado, 'badge' => 'badge-success', 'grupo' => 'diagnosticar',
        'rechazado' => false, 'ayuda' => '',
        'accion' => ['Continuar', "diagnostico.php?id=$id", 'btn-primary'],
    ];

    switch (true) {
        case $estado === 'Ingresado':
            $info['etiqueta'] = 'Recibido';
            $info['ayuda'] = 'Falta revisar el vehículo';
            $info['accion'] = ['Diagnosticar', "diagnostico.php?id=$id", 'btn-primary'];
            break;
        case $estado === 'En diagnóstico':
            $info['etiqueta'] = 'En diagnóstico';
            $info['badge'] = 'badge-warning';
            $info['ayuda'] = 'El mecánico está revisando';
            $info['accion'] = ['Seguir diagnóstico', "diagnostico.php?id=$id", 'btn-primary'];
            break;
        case in_array($estado, ['Diagnosticado', 'Diagnóstico no aplica'], true):
            $info['paso'] = 3;
            if ($pendiente) {
                $info['etiqueta'] = 'Esperando al cliente';
                $info['badge'] = 'badge-warning';
                $info['grupo'] = 'esperando';
                $info['ayuda'] = 'Presupuesto enviado: falta su respuesta';
                $info['accion'] = ['Registrar respuesta', "presupuesto.php?id=$id", 'btn-warning'];
            } else {
                $info['etiqueta'] = 'Diagnóstico listo';
                $info['grupo'] = 'presupuestar';
                $info['ayuda'] = 'Falta armar el presupuesto';
                $info['accion'] = ['Hacer presupuesto', "presupuesto.php?id=$id", 'btn-primary'];
            }
            break;
        case $estado === 'Presupuesto aprobado':
            $info['paso'] = 4;
            $info['etiqueta'] = 'Aprobado, por reparar';
            $info['grupo'] = 'reparacion';
            $info['ayuda'] = 'El cliente aceptó el presupuesto';
            $info['accion'] = ['Empezar reparación', "ejecucion.php?id=$id", 'btn-primary'];
            if ($cobrada) {
                $info['etiqueta'] = 'Cobrada, falta entregar';
                $info['ayuda'] = 'Ya se cobró en caja: marca la orden como lista';
                $info['accion'] = ['Marcar lista para retirar', "ejecucion.php?id=$id", 'btn-primary'];
            }
            break;
        case $estado === 'En reparación':
            $info['paso'] = 4;
            $info['etiqueta'] = 'En reparación';
            $info['badge'] = 'badge-warning';
            $info['grupo'] = 'reparacion';
            $info['ayuda'] = 'Pendiente cobrar y terminar';
            $info['accion'] = ['Cobrar y terminar', "ejecucion.php?id=$id", 'btn-primary'];
            if ($cobrada) {
                $info['etiqueta'] = 'Cobrada, falta entregar';
                $info['ayuda'] = 'Ya se cobró en caja: marca la orden como lista';
                $info['accion'] = ['Marcar lista para retirar', "ejecucion.php?id=$id", 'btn-primary'];
            }
            break;
        case $estado === 'Presupuesto rechazado':
            $info['paso'] = 4;
            $info['etiqueta'] = 'Presupuesto rechazado';
            $info['badge'] = 'badge-danger';
            $info['grupo'] = 'reparacion';
            $info['rechazado'] = true;
            $info['ayuda'] = 'El cliente no aceptó: devolver el vehículo';
            $info['accion'] = ['Cerrar y devolver', "ejecucion.php?id=$id", 'btn-secondary'];
            break;
        case $estado === 'Listo para entregar':
            $info['paso'] = 5;
            $info['etiqueta'] = 'Listo para retirar';
            $info['badge'] = 'badge-warning';
            $info['grupo'] = 'retirar';
            $info['ayuda'] = 'Avisar al cliente y entregar';
            $info['accion'] = ['Entregar al cliente', "ejecucion.php?id=$id", 'btn-primary'];
            break;
        case $estado === 'Entregado':
            $info['paso'] = 6;
            $info['etiqueta'] = 'Entregado';
            $info['grupo'] = 'entregadas';
            $info['ayuda'] = 'Orden terminada';
            $info['accion'] = ['Ver detalle', "ejecucion.php?id=$id", 'btn-secondary'];
            break;
    }
    return $info;
}

function tallerEstiloUI(): void {
    static $emitido = false;
    if ($emitido) return;
    $emitido = true;
    ?>
<style>
  .ts-bar { display: flex; align-items: flex-start; margin: 0 0 1.25rem; padding: 0.9rem 1rem; background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); overflow-x: auto; }
  .ts-step { flex: 1 1 0; min-width: 96px; display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; text-decoration: none; color: var(--text-muted); }
  .ts-step:not(:last-child)::after { content: ''; position: absolute; top: 15px; left: calc(50% + 18px); right: calc(-50% + 18px); height: 2px; background: var(--border-dark); }
  .ts-step.done:not(:last-child)::after { background: var(--success); }
  .ts-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; border: 2px solid var(--border-dark); background: var(--bg-dark, transparent); position: relative; z-index: 1; }
  .ts-step.done .ts-dot { background: var(--success); border-color: var(--success); color: #fff; }
  .ts-step.current .ts-dot { background: #f59e0b; border-color: #f59e0b; color: #111; box-shadow: 0 0 0 4px rgba(245,158,11,0.2); }
  .ts-step.rejected .ts-dot { background: var(--danger); border-color: var(--danger); color: #fff; }
  .ts-label { font-size: 0.78rem; font-weight: 600; margin-top: 0.4rem; line-height: 1.2; }
  .ts-step.current .ts-label { color: #fbbf24; }
  .ts-step.done .ts-label { color: var(--text); }
  .ts-step.viewing .ts-label { text-decoration: underline; text-underline-offset: 3px; }
  a.ts-step:hover .ts-dot { border-color: var(--primary); }
  .ts-help { display: flex; gap: 0.6rem; align-items: flex-start; background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.3); color: #bfdbfe; padding: 0.7rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.88rem; line-height: 1.45; }
  .ts-help i { margin-top: 0.15rem; color: #60a5fa; }
  .ts-mini { display: flex; gap: 4px; margin-bottom: 4px; }
  .ts-mini span { width: 20px; height: 6px; border-radius: 3px; background: var(--border-dark); }
  .ts-mini span.done { background: var(--success); }
  .ts-mini span.current { background: #f59e0b; }
  .ts-mini span.rejected { background: var(--danger); }
</style>
    <?php
}

/**
 * Barra de los 5 pasos. $ot = fila de la OT (o null si aún no existe, en Recepción).
 * $viendo = paso de la pantalla actual (subrayado).
 */
function otStepper(?array $ot, int $viendo): void {
    tallerEstiloUI();
    $info = $ot ? otEstadoInfo($ot) : ['paso' => 1, 'rechazado' => false];
    $paso = $info['paso'];
    $id = $ot ? (int)$ot['OrdenTrabajoID'] : 0;
    $enlaces = [
        1 => "comprobante_ot.php?id=$id",
        2 => "diagnostico.php?id=$id",
        3 => "presupuesto.php?id=$id",
        4 => "ejecucion.php?id=$id",
        5 => "ejecucion.php?id=$id",
    ];
    echo '<nav class="ts-bar no-print" aria-label="Pasos de la orden de trabajo">';
    foreach (TALLER_PASOS as $n => [$nombre, $icono]) {
        $clases = [];
        $hecho = $n < $paso;
        if ($hecho) $clases[] = 'done';
        if ($n === $paso) $clases[] = 'current';
        if ($info['rechazado'] && $n === 3) { $clases = ['rejected']; $hecho = false; }
        if ($n === $viendo) $clases[] = 'viewing';
        // Solo se puede ir a pasos ya alcanzados, y nunca a pasos 4-5 si la OT aún no está aprobada.
        $alcanzado = $ot && $n <= min($paso, 5) && ($n < 4 || $paso >= 4);
        $tag = $alcanzado && $n !== $viendo ? 'a' : 'div';
        $href = $tag === 'a' ? ' href="' . htmlspecialchars($enlaces[$n]) . '"' : '';
        $marca = ($hecho) ? '<i class="fa-solid fa-check"></i>' : (in_array('rejected', $clases, true) ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid ' . $icono . '"></i>');
        echo "<$tag class=\"ts-step " . implode(' ', $clases) . "\"$href><span class=\"ts-dot\">$marca</span><span class=\"ts-label\">" . htmlspecialchars($nombre) . "</span></$tag>";
    }
    echo '</nav>';
}

/** Cuadro breve "qué hago aquí" para las pantallas del proceso. */
function tallerAyuda(string $texto): void {
    tallerEstiloUI();
    echo '<div class="ts-help no-print"><i class="fa-solid fa-circle-info"></i><div>' . $texto . '</div></div>';
}

/** Barrita de progreso compacta para el listado. */
function otMiniProgreso(array $info): string {
    $html = '<div class="ts-mini">';
    for ($n = 1; $n <= 5; $n++) {
        $c = '';
        if ($n < $info['paso']) $c = 'done';
        elseif ($n === $info['paso']) $c = 'current';
        if ($info['rechazado'] && $n === 3) $c = 'rejected';
        $html .= '<span class="' . $c . '"></span>';
    }
    return $html . '</div>';
}

/**
 * Resumen del taller para la pantalla de inicio: cuántas órdenes hay en cada situación.
 * Devuelve ['por_grupo' => [...], 'monto_esperando' => int, 'total_activas' => int].
 */
function tallerResumen(PDO $pdo): array {
    $filas = $pdo->query("
        SELECT ot.OrdenTrabajoID, ot.Estado, ot.VentaID, ot.ManoObraCobrada, p.PresupuestoID, p.DecisionCliente,
               (SELECT COUNT(*) FROM presupuestodetalle pd WHERE pd.PresupuestoID = p.PresupuestoID) AS CantidadLineasPresupuesto,
               (SELECT COALESCE(SUM(Subtotal), 0) FROM presupuestodetalle pd WHERE pd.PresupuestoID = p.PresupuestoID) AS TotalPresupuesto
        FROM ordenestrabajo ot
        LEFT JOIN presupuestos p ON p.PresupuestoID = (SELECT MAX(p2.PresupuestoID) FROM presupuestos p2 WHERE p2.OrdenTrabajoID = ot.OrdenTrabajoID)
        WHERE ot.Estado <> 'Entregado'
    ")->fetchAll();
    $res = ['por_grupo' => ['diagnosticar' => 0, 'presupuestar' => 0, 'esperando' => 0, 'reparacion' => 0, 'retirar' => 0], 'monto_esperando' => 0, 'total_activas' => 0];
    foreach ($filas as $f) {
        $inf = otEstadoInfo($f);
        if (isset($res['por_grupo'][$inf['grupo']])) $res['por_grupo'][$inf['grupo']]++;
        if ($inf['grupo'] === 'esperando') $res['monto_esperando'] += (int)$f['TotalPresupuesto'];
        $res['total_activas']++;
    }
    return $res;
}
