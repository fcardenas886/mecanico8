<?php
require_once __DIR__ . '/../includes/whatsapp_helper.php';

// Configuración de las 6 columnas del flujo del taller
$kanbanCols = [
    'ingresado' => [
        'id'     => 'ingresado',
        'titulo' => 'Por Diagnosticar',
        'badge'  => 'badge-primary',
        'color'  => '#3b82f6',
        'icono'  => 'fa-inbox',
        'desc'   => 'Recibidos, esperando revisión',
        'items'  => []
    ],
    'diagnostico' => [
        'id'     => 'diagnostico',
        'titulo' => 'En Diagnóstico',
        'badge'  => 'badge-info',
        'color'  => '#8b5cf6',
        'icono'  => 'fa-stethoscope',
        'desc'   => 'Inspección técnica y presupuesto',
        'items'  => []
    ],
    'esperando' => [
        'id'     => 'esperando',
        'titulo' => 'Esperando Cliente',
        'badge'  => 'badge-warning',
        'color'  => '#f59e0b',
        'icono'  => 'fa-clock',
        'desc'   => 'Presupuesto por aprobar',
        'items'  => []
    ],
    'reparacion' => [
        'id'     => 'reparacion',
        'titulo' => 'En Reparación',
        'badge'  => 'badge-primary',
        'color'  => '#06b6d4',
        'icono'  => 'fa-screwdriver-wrench',
        'desc'   => 'Trabajo mecánico en box / foso',
        'items'  => []
    ],
    'listo' => [
        'id'     => 'listo',
        'titulo' => 'Listo para Retiro',
        'badge'  => 'badge-success',
        'color'  => '#10b981',
        'icono'  => 'fa-car-side',
        'desc'   => 'Control de calidad terminado',
        'items'  => []
    ],
    'entregado' => [
        'id'     => 'entregado',
        'titulo' => 'Entregados',
        'badge'  => 'badge-secondary',
        'color'  => '#64748b',
        'icono'  => 'fa-circle-check',
        'desc'   => 'Despachados al cliente',
        'items'  => []
    ],
];

// Distribuir cada orden en su columna correspondiente
foreach ($ordenes as $o) {
    $inf = otEstadoInfo($o);
    $estado = $o['Estado'];
    $grupo = $inf['grupo'];

    if ($grupo === 'entregadas' || $estado === 'Entregado') {
        $colKey = 'entregado';
    } elseif ($grupo === 'retirar' || $estado === 'Listo para entregar') {
        $colKey = 'listo';
    } elseif ($grupo === 'reparacion' || in_array($estado, ['Presupuesto aprobado', 'En reparación'], true)) {
        $colKey = 'reparacion';
    } elseif ($grupo === 'esperando' || ($o['DecisionCliente'] ?? '') === 'Pendiente' || $estado === 'Presupuesto rechazado') {
        $colKey = 'esperando';
    } elseif ($estado === 'En diagnóstico' || $grupo === 'presupuestar' || in_array($estado, ['Diagnosticado', 'Diagnóstico no aplica'], true)) {
        $colKey = 'diagnostico';
    } else {
        $colKey = 'ingresado';
    }

    $kanbanCols[$colKey]['items'][] = [$o, $inf];
}

// Helper para calcular permanencia en taller
function calcTiempoTaller(string $fecha): array {
    $ingreso = new DateTime($fecha);
    $ahora = new DateTime();
    $diff = $ahora->diff($ingreso);

    if ($diff->days >= 3) {
        return ['texto' => $diff->days . 'd ' . $diff->h . 'h', 'alerta' => true];
    } elseif ($diff->days > 0) {
        return ['texto' => $diff->days . ($diff->days == 1 ? ' día' : ' días'), 'alerta' => false];
    } elseif ($diff->h > 0) {
        return ['texto' => $diff->h . 'h ' . $diff->i . 'm', 'alerta' => false];
    }
    return ['texto' => max(1, $diff->i) . ' min', 'alerta' => false];
}
?>

<style>
  /* Contenedor del Tablero Kanban */
  .kanban-board-wrapper {
    margin-top: 1rem;
    position: relative;
  }
  .kanban-board {
    display: flex;
    gap: 1.15rem;
    overflow-x: auto;
    padding-bottom: 1.5rem;
    align-items: flex-start;
    scrollbar-width: thin;
    scrollbar-color: var(--border-dark) transparent;
  }
  .kanban-board::-webkit-scrollbar {
    height: 8px;
  }
  .kanban-board::-webkit-scrollbar-thumb {
    background: var(--border-dark);
    border-radius: 4px;
  }

  /* Columna individual */
  .kanban-column {
    flex: 0 0 310px;
    min-width: 300px;
    max-width: 325px;
    background: var(--card-bg);
    border: 1px solid var(--border-dark);
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
  }
  .kanban-column:hover {
    border-color: rgba(255,255,255,0.18);
  }

  /* Encabezado de la Columna */
  .kanban-col-header {
    padding: 0.75rem 0.9rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-dark);
    border-top: 3.5px solid transparent;
    border-top-left-radius: 9px;
    border-top-right-radius: 9px;
    background: rgba(255,255,255,0.02);
  }
  .kanban-col-title {
    font-size: 0.92rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.45rem;
  }
  .kanban-col-count {
    background: rgba(255,255,255,0.12);
    color: #ffffff;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
  }
  .kanban-col-sub {
    font-size: 0.75rem;
    color: var(--text-muted);
    padding: 0.35rem 0.9rem 0.45rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
  }

  /* Zona de Soltar (Cards Container) */
  .kanban-col-cards {
    padding: 0.75rem 0.65rem;
    min-height: 480px;
    max-height: calc(100vh - 280px);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    transition: background 0.2s ease, border-color 0.2s ease;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.1) transparent;
  }
  .kanban-col-cards::-webkit-scrollbar {
    width: 5px;
  }
  .kanban-col-cards::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 3px;
  }
  .kanban-col-cards.drag-over {
    background: rgba(59, 130, 246, 0.08) !important;
    outline: 2px dashed #38bdf8 !important;
    outline-offset: -3px;
    border-radius: 6px;
  }

  .kanban-col-empty {
    text-align: center;
    color: var(--text-muted);
    font-size: 0.82rem;
    padding: 2.5rem 1rem;
    font-style: italic;
  }

  /* Tarjeta Kanban */
  .kanban-card {
    background: #0f172a;
    border: 1px solid var(--border-dark);
    border-radius: 8px;
    padding: 0.8rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    cursor: grab;
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease, border-color 0.15s ease;
    position: relative;
    user-select: none;
  }
  .kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.35);
    border-color: #60a5fa;
  }
  .kanban-card:active {
    cursor: grabbing;
  }
  .kanban-card.dragging {
    opacity: 0.35;
    transform: scale(0.96) rotate(1deg);
  }
  .kanban-card.saving {
    opacity: 0.5;
    pointer-events: none;
  }

  /* Elementos internos de la Tarjeta */
  .kanban-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.45rem;
  }
  .kanban-card-folio {
    font-size: 0.78rem;
    font-weight: 800;
    color: #93c5fd;
    letter-spacing: 0.04em;
  }
  .kanban-card-timer {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: rgba(255,255,255,0.06);
    padding: 1px 6px;
    border-radius: 4px;
  }
  .kanban-card-timer.timer-alert {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
  }

  .kanban-card-vehiculo {
    margin-bottom: 0.45rem;
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  .kanban-patente {
    font-family: monospace, -apple-system;
    font-weight: 900;
    font-size: 0.95rem;
    letter-spacing: 0.08em;
    background: #0284c7;
    color: #ffffff;
    padding: 2px 7px;
    border-radius: 4px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.3);
    text-decoration: none;
    display: inline-block;
  }
  .kanban-patente:hover {
    background: #0369a1;
  }
  .kanban-car-desc {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text);
  }

  .kanban-card-client {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .kanban-card-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.78rem;
    padding: 0.4rem 0.5rem;
    background: rgba(255,255,255,0.03);
    border-radius: 5px;
    margin-bottom: 0.5rem;
    border: 1px solid rgba(255,255,255,0.05);
  }
  .kanban-meta-item {
    display: flex;
    align-items: center;
    gap: 0.3rem;
  }

  /* Selector de mecánico en la tarjeta */
  .kanban-card-mecanico {
    margin-bottom: 0.6rem;
  }
  .select-mecanico-inline {
    width: 100%;
    background: #1e293b;
    border: 1px solid var(--border-dark);
    color: var(--text);
    padding: 0.3rem 0.5rem;
    font-size: 0.78rem;
    border-radius: 6px;
    outline: none;
    cursor: pointer;
  }
  .select-mecanico-inline:focus {
    border-color: #38bdf8;
  }

  /* Acciones de la Tarjeta */
  .kanban-card-actions {
    display: flex;
    gap: 0.35rem;
    align-items: center;
    border-top: 1px solid rgba(255,255,255,0.06);
    padding-top: 0.55rem;
  }
  .kanban-card-actions .btn {
    font-size: 0.78rem;
    font-weight: 700;
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
  }
  .kanban-card-actions .btn-main {
    flex: 1;
    text-align: center;
    white-space: nowrap;
  }

  /* Menú desplegable para mover en móviles/táctil */
  .dropdown-quick-move {
    position: relative;
    display: inline-block;
  }
  .quick-move-menu {
    display: none;
    position: absolute;
    bottom: 100%;
    right: 0;
    margin-bottom: 5px;
    background: #1e293b;
    border: 1px solid var(--border-dark);
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    z-index: 100;
    min-width: 180px;
    padding: 0.35rem 0;
  }
  .dropdown-quick-move:hover .quick-move-menu,
  .quick-move-menu.show {
    display: block;
  }
  .quick-move-header {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--text-muted);
    padding: 0.3rem 0.8rem;
    letter-spacing: 0.05em;
  }
  .quick-move-btn {
    display: block;
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    color: var(--text);
    padding: 0.35rem 0.8rem;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  .quick-move-btn:hover {
    background: #334155;
    color: #38bdf8;
  }

  /* Botón WhatsApp con pulso cuando está listo */
  .pulse-highlight {
    animation: pulseBorder 1.5s infinite;
  }
  @keyframes pulseBorder {
    0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7); }
    70% { box-shadow: 0 0 0 8px rgba(37, 211, 102, 0); }
    100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
  }

  /* Barra de Toasts Flotantes */
  .kanban-toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    pointer-events: none;
  }
  .kanban-toast {
    pointer-events: auto;
    min-width: 280px;
    max-width: 380px;
    background: #1e293b;
    border-left: 4px solid #3b82f6;
    border: 1px solid #334155;
    color: #ffffff;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transform: translateY(20px);
    opacity: 0;
    transition: all 0.25s ease;
    font-size: 0.88rem;
  }
  .kanban-toast.show {
    transform: translateY(0);
    opacity: 1;
  }
  .kanban-toast.success {
    border-left: 4px solid #10b981;
  }
  .kanban-toast.error {
    border-left: 4px solid #ef4444;
  }
  .kanban-toast .toast-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0 0.3rem;
  }
</style>

<!-- Barra de Filtros del Tablero Kanban -->
<div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem; background: var(--card-bg); padding: 0.75rem 1rem; border: 1px solid var(--border-dark); border-radius: 8px;">
  <div style="flex: 1; min-width: 240px; position: relative;">
    <input type="text" id="kanbanSearchLive" class="form-control" placeholder="Buscar patente, cliente, auto o N° OT..." style="padding-left: 2.2rem;">
    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
  </div>
  <div style="display: flex; align-items: center; gap: 0.5rem;">
    <label for="kanbanFiltroMecanico" style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin: 0; white-space: nowrap;">
      <i class="fa-solid fa-user-gear"></i> Mecánico:
    </label>
    <select id="kanbanFiltroMecanico" class="form-control" style="width: auto; min-width: 170px; font-size: 0.85rem; padding: 0.4rem 0.7rem;">
      <option value="">Todos los mecánicos</option>
      <option value="sin_asignar">-- Sin asignar --</option>
      <?php foreach ($mecanicos as $m): ?>
        <option value="<?= $m['UsuarioID'] ?>"><?= htmlspecialchars($m['Nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- Estructura del Tablero Kanban -->
<div class="kanban-board-wrapper">
  <div class="kanban-board">
    <?php foreach ($kanbanCols as $colId => $col): ?>
      <div class="kanban-column" id="kanban-col-<?= $colId ?>" data-col="<?= $colId ?>">
        
        <!-- Cabecera de Columna -->
        <div class="kanban-col-header" style="border-top-color: <?= $col['color'] ?>;">
          <div class="kanban-col-title" style="color: <?= $col['color'] ?>;">
            <i class="fa-solid <?= $col['icono'] ?>"></i>
            <?= htmlspecialchars($col['titulo']) ?>
          </div>
          <span class="kanban-col-count"><?= count($col['items']) ?></span>
        </div>
        <div class="kanban-col-sub"><?= htmlspecialchars($col['desc']) ?></div>

        <!-- Zona de Tarjetas -->
        <div class="kanban-col-cards" data-col="<?= $colId ?>">
          <?php if (empty($col['items'])): ?>
            <div class="kanban-col-empty">Sin vehículos en esta etapa</div>
          <?php else: ?>
            <div class="kanban-col-empty" style="display: none;">Sin vehículos en esta etapa</div>
          <?php endif; ?>

          <?php foreach ($col['items'] as [$ot, $inf]):
            $id = (int)$ot['OrdenTrabajoID'];
            $tiempo = calcTiempoTaller($ot['FechaIngreso']);
            [$txtAccion, $urlAccion, $claseAccion] = $inf['accion'];
            $tienePresupuesto = !empty($ot['PresupuestoID']) && (int)($ot['CantidadLineasPresupuesto'] ?? 0) > 0;
            $montoPresupuesto = (float)($ot['TotalPresupuesto'] ?? 0);
          ?>
            <div class="kanban-card"
                 id="kanban-card-<?= $id ?>"
                 draggable="true"
                 data-ot-id="<?= $id ?>"
                 data-col="<?= $colId ?>"
                 data-patente="<?= htmlspecialchars(strtolower($ot['Patente'])) ?>"
                 data-cliente="<?= htmlspecialchars(strtolower($ot['ClienteNombre'])) ?>"
                 data-folio="<?= htmlspecialchars(strtolower(formatFolioOT($id))) ?>"
                 data-vehiculo="<?= htmlspecialchars(strtolower($ot['Marca'] . ' ' . $ot['Modelo'])) ?>"
                 data-mecanico-id="<?= $ot['MecanicoID'] ?? '' ?>">

              <!-- Top: Folio y Contador de Tiempo -->
              <div class="kanban-card-top">
                <span class="kanban-card-folio"><?= formatFolioOT($id) ?></span>
                <span class="kanban-card-timer <?= $tiempo['alerta'] ? 'timer-alert' : '' ?>" title="Ingresado el <?= date('d/m/Y H:i', strtotime($ot['FechaIngreso'])) ?>">
                  <i class="fa-regular fa-clock"></i> <?= $tiempo['texto'] ?>
                </span>
              </div>

              <!-- Patente y Vehículo -->
              <div class="kanban-card-vehiculo">
                <a href="ficha_vehiculo.php?id=<?= $ot['VehiculoID'] ?>" class="kanban-patente" title="Ficha del vehículo">
                  <?= htmlspecialchars($ot['Patente']) ?>
                </a>
                <span class="kanban-car-desc">
                  <?= htmlspecialchars($ot['Marca'] . ' ' . $ot['Modelo']) ?>
                  <?php if (!empty($ot['Anio'])): ?>
                    <span style="color: var(--text-muted); font-size: 0.75rem;">(<?= $ot['Anio'] ?>)</span>
                  <?php endif; ?>
                </span>
              </div>

              <!-- Cliente -->
              <div class="kanban-card-client" title="<?= htmlspecialchars($ot['ClienteNombre']) ?>">
                <i class="fa-regular fa-user" style="font-size: 0.75rem;"></i>
                <span><?= htmlspecialchars($ot['ClienteNombre']) ?></span>
              </div>

              <!-- Meta: Presupuesto y Situación -->
              <div class="kanban-card-meta">
                <div class="kanban-meta-item">
                  <span style="color: var(--text-muted);">Monto:</span>
                  <?php if ($tienePresupuesto && $montoPresupuesto > 0): ?>
                    <strong style="color: #fbbf24;"><?= formatCLP($montoPresupuesto) ?></strong>
                  <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.72rem;">Sin cotizar</span>
                  <?php endif; ?>
                </div>
                <span class="badge <?= $inf['badge'] ?>" style="font-size: 0.7rem; padding: 0.15rem 0.4rem;">
                  <?= htmlspecialchars($inf['etiqueta']) ?>
                </span>
              </div>

              <!-- Asignación Rápida de Mecánico -->
              <div class="kanban-card-mecanico">
                <select class="select-mecanico-inline" onchange="asignarMecanicoKanban(<?= $id ?>, this.value)" title="Asignar mecánico a cargo">
                  <option value="">-- Sin mecánico --</option>
                  <?php foreach ($mecanicos as $m): ?>
                    <option value="<?= $m['UsuarioID'] ?>" <?= ((int)($ot['MecanicoID'] ?? 0) === (int)$m['UsuarioID']) ? 'selected' : '' ?>>
                      👨‍🔧 <?= htmlspecialchars($m['Nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Acciones de la Tarjeta -->
              <div class="kanban-card-actions">
                <!-- Acción Principal del Flujo -->
                <a href="<?= htmlspecialchars($urlAccion) ?>" class="btn btn-main <?= $claseAccion ?>" style="<?= $claseAccion === 'btn-warning' ? 'background:#f59e0b; color:#000;' : '' ?>">
                  <?= htmlspecialchars($txtAccion) ?>
                </a>

                <!-- WhatsApp Integrado a 1 Clic -->
                <?php
                $urlWA = null;
                $titleWA = 'Enviar mensaje por WhatsApp';
                if (!empty($ot['ClienteTelefono'])) {
                    if ($colId === 'esperando') {
                        $msgWA = mensajePresupuestoWhatsApp($ot, ['TiempoEntrega' => ''], $montoPresupuesto, obtenerNombreTaller());
                        $urlWA = generarUrlWhatsapp($ot['ClienteTelefono'], $msgWA);
                        $titleWA = 'Enviar presupuesto por WhatsApp';
                    } elseif ($colId === 'listo') {
                        $msgWA = mensajeAutoListoWhatsApp($ot, 0, obtenerNombreTaller());
                        $urlWA = generarUrlWhatsapp($ot['ClienteTelefono'], $msgWA);
                        $titleWA = 'Avisar auto listo por WhatsApp';
                    } else {
                        $urlWA = generarUrlWhatsapp($ot['ClienteTelefono'], "Hola " . $ot['ClienteNombre'] . " 👋, te contactamos de " . obtenerNombreTaller() . " sobre tu vehículo " . $ot['Patente'] . ".");
                    }
                }
                ?>
                <?php if ($urlWA): ?>
                  <a href="<?= $urlWA ?>" target="_blank" class="btn btn-wa-auto <?= $colId === 'listo' ? 'pulse-highlight' : '' ?>" style="background: #25d366; color: #fff;" title="<?= $titleWA ?>">
                    <i class="fa-brands fa-whatsapp"></i>
                  </a>
                <?php endif; ?>

                <!-- Sticker de Parabrisas -->
                <a href="sticker_aceite.php?ot=<?= $id ?>" target="_blank" class="btn btn-secondary" title="Imprimir Sticker Cambio de Aceite">
                  <i class="fa-solid fa-tag"></i>
                </a>

                <!-- Menú rápido para dispositivos táctiles o sin drag -->
                <div class="dropdown-quick-move">
                  <button type="button" class="btn btn-secondary" title="Mover a otra columna">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                  </button>
                  <div class="quick-move-menu">
                    <div class="quick-move-header">Mover vehículo a:</div>
                    <?php foreach ($kanbanCols as $targetColId => $targetCol): if ($targetColId !== $colId): ?>
                      <button type="button" class="quick-move-btn" onclick="moverColumnaKanban(<?= $id ?>, '<?= $targetColId ?>')">
                        <i class="fa-solid <?= $targetCol['icono'] ?>" style="color: <?= $targetCol['color'] ?>; width: 16px;"></i> <?= htmlspecialchars($targetCol['titulo']) ?>
                      </button>
                    <?php endif; endforeach; ?>
                  </div>
                </div>

              </div>

            </div>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endforeach; ?>
  </div>
</div>
