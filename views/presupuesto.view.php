<?php
$folio = formatFolioOT($ot['OrdenTrabajoID']);
$tipoLabel = ['Repuesto' => 'Repuesto', 'ManoObra' => 'Mano de Obra', 'Terceros' => 'Terceros'];
$tipoClase = ['Repuesto' => 'badge-success', 'ManoObra' => 'badge-warning', 'Terceros' => 'badge-danger'];
$pendiente = $presupuesto && $presupuesto['DecisionCliente'] === 'Pendiente';
$decidido = $presupuesto && $presupuesto['DecisionCliente'] !== 'Pendiente';

// Preparar mensaje y enlace de WhatsApp para el presupuesto
require_once __DIR__ . '/../includes/whatsapp_helper.php';
$waNombreTaller = obtenerNombreTaller($pdo);
$waMsg = $presupuesto ? mensajePresupuestoWhatsApp($ot, $presupuesto, (float)$total, $waNombreTaller) : '';
$waUrl = $presupuesto ? generarUrlWhatsapp($ot['ClienteTelefono'] ?? '', $waMsg) : '';

$origenBadge = [
    'Cliente'        => ['label' => '🗣️ Cliente', 'class' => 'badge-primary', 'title' => 'Solicitado explícitamente por el cliente en recepción'],
    'Diagnostico'    => ['label' => '🔍 Diagnóstico', 'class' => 'badge-warning', 'title' => 'Detectado en la inspección técnica del mecánico'],
    'Fluidos'        => ['label' => '🛢️ Fluidos', 'class' => 'badge-info', 'title' => 'Sugerido en el chequeo de lubricantes y fluidos'],
    'Historial'      => ['label' => '🕒 Historial', 'class' => 'badge-success', 'title' => 'Reutilizado del historial de visitas de este vehículo'],
    'Compatibilidad' => ['label' => '⭐ Modelo', 'class' => 'badge-info', 'title' => 'Sugerido por compatibilidad con el modelo del vehículo'],
    'Directo'        => ['label' => 'Directo', 'class' => 'badge-secondary', 'title' => 'Agregado manualmente al presupuesto'],
];

// Helper para verificar si un requerimiento ya está contemplado en el presupuesto
function verificarItemCotizado(string $texto, array $lineas, ?string $origenEsperado = null): bool {
    if (empty($texto) && empty($origenEsperado)) return false;
    $textoNorm = mb_strtolower(trim($texto));
    $palabras = array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $textoNorm)), fn($p) => mb_strlen($p) >= 4);

    foreach ($lineas as $l) {
        if ($origenEsperado && ($l['Origen'] ?? '') === $origenEsperado) {
            if (empty($texto)) return true;
        }
        $descLinea = mb_strtolower($l['Descripcion']);
        if ($textoNorm !== '' && (str_contains($descLinea, $textoNorm) || str_contains($textoNorm, $descLinea))) {
            return true;
        }
        $coincidencias = 0;
        foreach ($palabras as $p) {
            if (str_contains($descLinea, $p)) $coincidencias++;
        }
        if ($coincidencias >= 1 && (count($palabras) <= 2 || $coincidencias >= 2)) {
            return true;
        }
    }
    return false;
}

// Calcular métricas de cobertura para el Semáforo
$totalRequerimientos = 0;
$cubiertosRequerimientos = 0;
$itemsPendientes = [];

// 1. Petición en texto libre
if (!empty($ot['OperacionesTextoLibre'])) {
    $totalRequerimientos++;
    if (verificarItemCotizado($ot['OperacionesTextoLibre'], $lineas, 'Cliente')) {
        $cubiertosRequerimientos++;
    } else {
        $itemsPendientes[] = 'Nota del cliente: "' . mb_substr($ot['OperacionesTextoLibre'], 0, 35) . '..."';
    }
}

// 2. Operaciones solicitadas en recepción
foreach ($operacionesSolicitadas as $op) {
    $totalRequerimientos++;
    if (verificarItemCotizado($op['NombreOperacion'], $lineas, 'Cliente')) {
        $cubiertosRequerimientos++;
    } else {
        $itemsPendientes[] = $op['NombreOperacion'];
    }
}

// 3. Hallazgos del diagnóstico
foreach ($hallazgos as $h) {
    $totalRequerimientos++;
    if (verificarItemCotizado($h['Hallazgo'], $lineas, 'Diagnostico')) {
        $cubiertosRequerimientos++;
    } else {
        $itemsPendientes[] = $h['Area'] . ': ' . mb_substr($h['Hallazgo'], 0, 30);
    }
}

// 4. Fluidos marcados que requieren cambio
$fluidosPendientes = [];
if (!empty($estacion['MotorCambio'])) {
    $fluidosPendientes[] = 'Aceite de Motor';
    $fluidosPendientes[] = 'Filtro de Aceite';
} elseif (!empty($estacion['FiltroCambio'])) {
    $fluidosPendientes[] = 'Filtro de Aceite';
}
if (!empty($estacion['FrenosCambio'])) $fluidosPendientes[] = 'Líquido de Frenos';
if (!empty($estacion['RadiadorAnticongelante'])) $fluidosPendientes[] = 'Refrigerante';
if (!empty($estacion['CajaCambio'])) $fluidosPendientes[] = 'Aceite de Caja';

foreach ($fluidosPendientes as $flu) {
    $totalRequerimientos++;
    if (verificarItemCotizado($flu, $lineas, 'Fluidos')) {
        $cubiertosRequerimientos++;
    } else {
        $itemsPendientes[] = $flu;
    }
}

$porcentajeCobertura = $totalRequerimientos > 0 ? (int)round(($cubiertosRequerimientos / $totalRequerimientos) * 100) : 100;
?>

<style>
  /* Layout Principal Grid 2 Columnas */
  .pr-grid-container {
    display: grid;
    grid-template-columns: 370px 1fr;
    gap: 1.25rem;
    align-items: start;
    margin-bottom: 2rem;
  }
  @media (max-width: 1080px) {
    .pr-grid-container {
      grid-template-columns: 1fr;
    }
  }

  /* Columna Lateral: Índice de Requerimientos */
  .pr-sidebar-indice {
    position: sticky;
    top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .pr-card {
    background: var(--card-bg);
    border: 1px solid var(--border-dark);
    border-radius: var(--radius);
    padding: 1.15rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .pr-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-dark);
    padding-bottom: 0.65rem;
    margin-bottom: 0.85rem;
  }
  .pr-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.45rem;
  }

  /* Items del Índice */
  .pr-item-req {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 8px;
    padding: 0.65rem 0.8rem;
    margin-bottom: 0.6rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    transition: all 0.15s ease;
  }
  .pr-item-req:hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.12);
  }
  .pr-item-req.is-covered {
    border-left: 3.5px solid #10b981;
    background: rgba(16, 185, 129, 0.04);
  }
  .pr-item-req.is-pending {
    border-left: 3.5px solid #f59e0b;
  }

  .pr-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .pr-item-name {
    font-size: 0.86rem;
    font-weight: 700;
    line-height: 1.3;
  }
  .pr-item-meta {
    font-size: 0.76rem;
    color: var(--text-muted);
  }

  .pr-quote-box {
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 8px;
    padding: 0.75rem 0.9rem;
    font-size: 0.85rem;
    color: #bfdbfe;
    line-height: 1.45;
    margin-bottom: 0.75rem;
    font-style: italic;
  }

  /* Barra del Semáforo de Cobertura */
  .pr-semaforo-bar {
    background: var(--card-bg);
    border: 1px solid var(--border-dark);
    border-radius: 10px;
    padding: 0.9rem 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  }
  .pr-prog-track {
    background: #0f172a;
    border-radius: 999px;
    height: 10px;
    overflow: hidden;
    margin: 0.5rem 0;
    border: 1px solid var(--border-dark);
  }
  .pr-prog-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.4s ease;
  }
  .pr-pendientes-chips {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-top: 0.4rem;
  }
  .pr-chip-pend {
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #fbbf24;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
  }

  /* Tabla de Líneas */
  .pr-lineas-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
  .pr-lineas-table th { text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); padding: 0.6rem 0.5rem; border-bottom: 1px solid var(--border-dark); }
  .pr-lineas-table td { padding: 0.65rem 0.5rem; border-bottom: 1px dashed var(--border-dark); vertical-align: middle; }
  .pr-total-row td { border-bottom: none; font-weight: 700; padding-top: 0.85rem; }

  /* Insignia de Origen */
  .badge-origen {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
  }

  /* Pestañas de agregar */
  .pr-tabs { display: flex; gap: 0.4rem; border-bottom: 1px solid var(--border-dark); margin-bottom: 1rem; }
  .pr-tab-btn { background: none; border: none; padding: 0.55rem 0.95rem; font-size: 0.85rem; font-weight: 700; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; }
  .pr-tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }

  /* Bloques desplegables (Acordeón) */
  details.pr-collapsible summary::-webkit-details-marker,
  details.pr-collapsible summary::marker {
    display: none;
    content: "";
  }
  details.pr-collapsible summary {
    outline: none;
    cursor: pointer;
    transition: background 0.15s ease;
  }
  details.pr-collapsible summary:hover {
    background: rgba(255, 255, 255, 0.03);
  }
  details.pr-collapsible[open] summary .pr-chevron {
    transform: rotate(180deg);
  }
</style>

<!-- Barra Superior con Folio y Acciones -->
<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;" class="no-print">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Presupuesto de Servicio</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
      <strong><?= htmlspecialchars($folio) ?></strong> ·
      <code style="font-weight: 700; color: #38bdf8;"><?= htmlspecialchars($ot['Patente']) ?></code>
      <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> (<?= $ot['Anio'] ?>) — <strong style="color: #fff;"><?= htmlspecialchars($ot['ClienteNombre']) ?></strong>
      <?php if (!empty($ot['ClienteTelefono'])): ?>
        <span style="color: #34d399; font-size: 0.82rem; margin-left: 0.4rem;" title="Teléfono registrado para WhatsApp">
          <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($ot['ClienteTelefono']) ?>
        </span>
        <button type="button" class="btn btn-link" onclick="document.getElementById('modalTelCliente').style.display='flex'" style="color: #94a3b8; font-size: 0.75rem; padding: 0 0.3rem; text-decoration: underline;" title="Modificar número de teléfono">
          <i class="fa-solid fa-pen"></i> Cambiar
        </button>
      <?php else: ?>
        <span class="badge badge-warning" style="font-size: 0.72rem; margin-left: 0.4rem;">
          <i class="fa-solid fa-phone-slash"></i> Sin teléfono
        </span>
        <button type="button" class="btn btn-outline-warning" onclick="document.getElementById('modalTelCliente').style.display='flex'" style="padding: 0.15rem 0.5rem; font-size: 0.74rem; font-weight: 700; margin-left: 0.3rem;" title="Ingresar teléfono para enviar WhatsApp directo">
          <i class="fa-solid fa-plus"></i> Ingresar número
        </button>
      <?php endif; ?>
    </p>
  </div>
  <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
    <span class="badge <?= $pendiente ? 'badge-warning' : 'badge-success' ?>"><?= htmlspecialchars(otEstadoInfo($ot + ['PresupuestoID' => $presupuesto['PresupuestoID'] ?? null, 'DecisionCliente' => $presupuesto['DecisionCliente'] ?? null, 'CantidadLineasPresupuesto' => count($lineas)])['etiqueta']) ?></span>
    <?php if ($presupuesto): ?>
      <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background: #25d366; color: #ffffff; padding: 0.45rem 0.85rem; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;" title="Enviar presupuesto por WhatsApp al cliente">
        <i class="fa-brands fa-whatsapp" style="font-size: 1rem;"></i> Enviar por WhatsApp
      </a>
      <a href="comprobante_presupuesto.php?id=<?= $otId ?>" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;" target="_blank">
        <i class="fa-solid fa-print"></i> Imprimir presupuesto
      </a>
    <?php endif; ?>
    <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem;">
      <i class="fa-solid fa-arrow-left"></i> Órdenes de trabajo
    </a>
  </div>
</div>

<?php otStepper($ot, 3); ?>

<!-- ======================================================== -->
<!-- BARRA SEMÁFORO DE COBERTURA (Control de Calidad)        -->
<!-- ======================================================== -->
<div class="pr-semaforo-bar no-print">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <div style="font-size: 0.92rem; font-weight: 700; display: flex; align-items: center; gap: 0.45rem;">
      <i class="fa-solid fa-list-check" style="color: #38bdf8;"></i> Cobertura del Presupuesto:
      <span style="color: <?= $porcentajeCobertura >= 100 ? '#10b981' : ($porcentajeCobertura >= 50 ? '#f59e0b' : '#ef4444') ?>;">
        <?= $cubiertosRequerimientos ?> de <?= $totalRequerimientos ?> requerimientos contemplados (<?= $porcentajeCobertura ?>%)
      </span>
    </div>
    <?php if ($porcentajeCobertura >= 100 && $totalRequerimientos > 0): ?>
      <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> 100% Cubierto</span>
    <?php elseif (!empty($itemsPendientes)): ?>
      <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> <?= count($itemsPendientes) ?> pendientes por cotizar</span>
    <?php endif; ?>
  </div>

  <div class="pr-prog-track">
    <div class="pr-prog-fill" style="width: <?= $porcentajeCobertura ?>%; background: <?= $porcentajeCobertura >= 100 ? '#10b981' : ($porcentajeCobertura >= 50 ? '#f59e0b' : '#ef4444') ?>;"></div>
  </div>

  <?php if (!empty($itemsPendientes)): ?>
    <div class="pr-pendientes-chips">
      <span style="font-size: 0.75rem; color: var(--text-muted); align-self: center;">Pendientes:</span>
      <?php foreach (array_slice($itemsPendientes, 0, 4) as $itemP): ?>
        <span class="pr-chip-pend"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($itemP) ?></span>
      <?php endforeach; ?>
      <?php if (count($itemsPendientes) > 4): ?>
        <span class="pr-chip-pend">+<?= count($itemsPendientes) - 4 ?> más</span>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div style="font-size: 0.78rem; color: #10b981; margin-top: 0.25rem;">
      <i class="fa-solid fa-circle-check"></i> Todos los requerimientos del cliente y hallazgos del taller han sido incluidos en este presupuesto.
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($error)): ?>
  <div class="no-print" style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if (!empty($message)): ?>
  <div class="no-print" style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
  </div>
<?php endif; ?>

<?php if (!$presupuesto): ?>

  <div class="pr-card no-print" style="text-align: center; padding: 3rem 1.5rem;">
    <i class="fa-solid fa-file-invoice-dollar" style="font-size: 3rem; color: #38bdf8; margin-bottom: 1rem;"></i>
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Presupuesto no iniciado</h2>
    <p style="color: var(--text-muted); max-width: 480px; margin: 0 auto 1.5rem;">Crea el presupuesto para comenzar a estructurar los repuestos, mano de obra y vincular los requerimientos del cliente.</p>
    <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="crear_presupuesto">
      <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-weight: 700;">
        <i class="fa-solid fa-plus"></i> Crear Presupuesto
      </button>
    </form>
  </div>

<?php else: ?>

  <!-- ======================================================== -->
  <!-- GRID PRINCIPAL: ÍNDICE A LA IZQUIERDA + PRESUPUESTO      -->
  <!-- ======================================================== -->
  <div class="pr-grid-container">

    <!-- 1. COLUMNA LATERAL: ÍNDICE DE REQUERIMIENTOS Y DIAGNÓSTICO -->
    <div class="pr-sidebar-indice no-print">

      <!-- TARJETA A: LO QUE PIDIÓ EL CLIENTE EN RECEPCIÓN -->
      <div class="pr-card">
        <div class="pr-card-header">
          <div class="pr-card-title" style="color: #60a5fa;">
            <i class="fa-solid fa-comment-dots"></i> Petición del Cliente
          </div>
          <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">Recepción</span>
        </div>

        <?php if (!empty($ot['OperacionesTextoLibre'])): 
          $libreCubierto = verificarItemCotizado($ot['OperacionesTextoLibre'], $lineas, 'Cliente');
        ?>
          <div class="pr-quote-box">
            <i class="fa-solid fa-quote-left" style="font-size: 0.75rem; opacity: 0.7;"></i>
            <?= nl2br(htmlspecialchars($ot['OperacionesTextoLibre'])) ?>
          </div>
        <?php endif; ?>

        <?php if (empty($operacionesSolicitadas) && empty($ot['OperacionesTextoLibre'])): ?>
          <p style="font-size: 0.82rem; color: var(--text-muted); font-style: italic;">No se registraron requerimientos específicos al ingresar.</p>
        <?php else: ?>
          <?php foreach ($operacionesSolicitadas as $op): 
            $opCubierta = verificarItemCotizado($op['NombreOperacion'], $lineas, 'Cliente');
          ?>
            <div class="pr-item-req <?= $opCubierta ? 'is-covered' : 'is-pending' ?>">
              <div class="pr-item-header">
                <div class="pr-item-name"><?= htmlspecialchars($op['NombreOperacion']) ?></div>
                <?php if ($opCubierta): ?>
                  <span class="badge badge-success" style="font-size: 0.7rem;"><i class="fa-solid fa-check"></i> Cotizado</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size: 0.7rem;">Pendiente</span>
                <?php endif; ?>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.2rem;">
                <div class="pr-item-meta">
                  <?php if (!empty($op['PrecioBase']) && (int)$op['PrecioBase'] > 0): ?>
                    Base: <strong><?= formatCLP($op['PrecioBase']) ?></strong>
                  <?php else: ?>
                    Sin precio estándar
                  <?php endif; ?>
                </div>
                <?php if ($pendiente && !$opCubierta): ?>
                  <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="margin: 0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="cotizar_operacion_rapida">
                    <input type="hidden" name="operacion_solicitada_id" value="<?= $op['ID'] ?>">
                    <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.55rem; font-size: 0.75rem; font-weight: 700;">
                      <i class="fa-solid fa-bolt"></i> Cotizar
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- TARJETA B: DIAGNÓSTICO TÉCNICO DEL MECÁNICO -->
      <div class="pr-card">
        <div class="pr-card-header">
          <div class="pr-card-title" style="color: #a78bfa;">
            <i class="fa-solid fa-stethoscope"></i> Hallazgos del Diagnóstico
          </div>
          <a href="diagnostico.php?id=<?= $otId ?>" style="font-size: 0.75rem; color: #a78bfa; text-decoration: none;">
            <i class="fa-solid fa-pen"></i> Editar
          </a>
        </div>

        <?php if (empty($hallazgos)): ?>
          <div style="text-align: center; padding: 1rem 0; color: var(--text-muted); font-size: 0.82rem;">
            Sin hallazgos cargados.<br>
            <a href="diagnostico.php?id=<?= $otId ?>" style="color: #a78bfa; font-weight: 600;">Completar diagnóstico técnico</a>
          </div>
        <?php else: ?>
          <?php foreach ($hallazgos as $h): 
            $diagCubierto = verificarItemCotizado($h['Hallazgo'], $lineas, 'Diagnostico');
          ?>
            <div class="pr-item-req <?= $diagCubierto ? 'is-covered' : 'is-pending' ?>">
              <div class="pr-item-header">
                <div>
                  <span class="badge badge-info" style="font-size: 0.68rem; margin-bottom: 2px;"><?= htmlspecialchars($h['Area']) ?></span>
                  <div class="pr-item-name" style="font-size: 0.84rem;"><?= htmlspecialchars($h['Hallazgo']) ?></div>
                </div>
                <?php if ($diagCubierto): ?>
                  <span class="badge badge-success" style="font-size: 0.7rem;"><i class="fa-solid fa-check"></i> Incluido</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size: 0.7rem;">Pendiente</span>
                <?php endif; ?>
              </div>

              <?php if ($pendiente && !$diagCubierto): ?>
                <div style="margin-top: 0.4rem; padding-top: 0.4rem; border-top: 1px dashed rgba(255,255,255,0.06); display: flex; gap: 0.4rem; justify-content: flex-end;">
                  <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="prepararLineaDesdeHallazgo('<?= htmlspecialchars(addslashes($h['Hallazgo'])) ?>', '<?= htmlspecialchars(addslashes($h['Area'])) ?>')">
                    <i class="fa-solid fa-plus"></i> Cotizar Solución
                  </button>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- TARJETA C: ESTACIÓN DE SERVICIO Y FLUIDOS -->
      <div class="pr-card">
        <div class="pr-card-header">
          <div class="pr-card-title" style="color: #38bdf8;">
            <i class="fa-solid fa-oil-can"></i> Chequeo de Fluidos
          </div>
          <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">Inspección</span>
        </div>

        <?php if (!$estacion): ?>
          <p style="font-size: 0.82rem; color: var(--text-muted); font-style: italic;">No se registraron niveles bajo el capó.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.82rem; margin-bottom: 0.75rem;">
            <div style="display: flex; justify-content: space-between;">
              <span>🛢️ Aceite Motor:</span>
              <strong><?= !empty($estacion['MotorCambio']) ? '<span style="color:#f87171;">Requiere cambio (' . number_format($estacion['AceiteIntervaloKm'],0,',','.') . ' km)</span>' : '<span style="color:#34d399;">OK</span>' ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>🟡 Filtro de Aceite:</span>
              <strong><?= !empty($estacion['FiltroCambio']) ? '<span style="color:#f87171;">Requiere cambio</span>' : (!empty($estacion['MotorCambio']) ? '<span style="color:#fbbf24;">Se cambia con el aceite</span>' : '<span style="color:#34d399;">OK</span>') ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>🛑 Líquido Frenos:</span>
              <strong><?= !empty($estacion['FrenosCambio']) ? '<span style="color:#f87171;">Requiere purga/cambio</span>' : '<span style="color:#34d399;">' . htmlspecialchars($estacion['FrenosNivel']) . '</span>' ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>❄️ Refrigerante:</span>
              <strong><?= !empty($estacion['RadiadorAnticongelante']) ? '<span style="color:#f87171;">Requiere anticongelante</span>' : '<span style="color:#34d399;">' . htmlspecialchars($estacion['RadiadorNivel']) . '</span>' ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span>⚙️ Aceite de Caja:</span>
              <strong><?= !empty($estacion['CajaCambio']) ? '<span style="color:#f87171;">Requiere cambio</span>' : '<span style="color:#34d399;">' . htmlspecialchars($estacion['CajaNivel']) . '</span>' ?></strong>
            </div>
          </div>

          <?php if ($filtroSugerido || $aceiteSugerido): ?>
            <div style="font-size: 0.74rem; background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 6px; padding: 0.5rem; margin-bottom: 0.75rem;">
              <div style="font-weight: 700; color: #38bdf8; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.35rem;">
                <i class="fa-solid fa-clock-rotate-left"></i> Insumos sugeridos para este auto:
              </div>
              <?php if ($aceiteSugerido): ?>
                <div style="color: #cbd5e1; margin-bottom: 2px;">
                  • <strong>Aceite:</strong> <?= htmlspecialchars($aceiteSugerido['Nombre']) ?>
                </div>
              <?php endif; ?>
              <?php if ($filtroSugerido): ?>
                <div style="color: #cbd5e1;">
                  • <strong>Filtro:</strong> <?= htmlspecialchars($filtroSugerido['Nombre']) ?> 
                  <span style="color: #94a3b8; font-size: 0.7rem;">
                    (<?= $filtroSugerido['Origen'] === 'HistorialVehiculo' ? 'usado en ' . formatFolioOT($filtroSugerido['OT']) : 'catálogo modelo' ?>)
                  </span>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($estacion['Observaciones'])): ?>
            <div style="font-size: 0.75rem; color: var(--text-muted); background: rgba(255,255,255,0.02); padding: 0.4rem; border-radius: 4px; margin-bottom: 0.75rem;">
              <strong>Obs:</strong> <?= htmlspecialchars($estacion['Observaciones']) ?>
            </div>
          <?php endif; ?>

          <?php if ($pendiente && (!empty($estacion['MotorCambio']) || !empty($estacion['FrenosCambio']) || !empty($estacion['RadiadorAnticongelante']) || !empty($estacion['CajaCambio']))): ?>
            <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="cargar_paquete_fluidos">
              <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 0.8rem; font-weight: 700; padding: 0.45rem;">
                <i class="fa-solid fa-bolt"></i> Cargar Paquete de Fluidos
              </button>
            </form>
            <div style="font-size: 0.68rem; color: var(--text-muted); text-align: center; margin-top: 0.35rem;">
              Agrega servicio, aceite y filtro con 1 solo clic.
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

    </div>

    <!-- 2. COLUMNA DERECHA: TABLA DE LÍNEAS, FORMULARIOS Y TOTALES -->
    <div class="pr-main-col">

      <div class="pr-card">
        <div class="pr-card-header">
          <div class="pr-card-title">
            <i class="fa-solid fa-list-ol"></i> Detalle de Ítems del Presupuesto
          </div>
          <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
            <?= count($lineas) ?> <?= count($lineas) === 1 ? 'línea' : 'líneas' ?>
          </span>
        </div>

        <?php if (empty($lineas)): ?>
          <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
            <i class="fa-solid fa-receipt" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 0.75rem;"></i>
            <p style="font-weight: 600; margin-bottom: 0.4rem;">El presupuesto aún no tiene líneas agregadas.</p>
            <p style="font-size: 0.82rem;">Usa el panel de la izquierda para cotizar las peticiones del cliente o el formulario de abajo para agregar repuestos y mano de obra.</p>
          </div>
        <?php else: ?>
          <table class="pr-lineas-table">
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Origen</th>
                <th>Descripción</th>
                <th style="text-align: right;">Cant.</th>
                <th style="text-align: right;">Unitario</th>
                <th style="text-align: right;">Subtotal</th>
                <?php if ($pendiente): ?><th style="text-align: center;">Acción</th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lineas as $l): 
                $org = $l['Origen'] ?? 'Directo';
                $orgData = $origenBadge[$org] ?? $origenBadge['Directo'];
              ?>
                <tr>
                  <td>
                    <span class="badge <?= $tipoClase[$l['TipoLinea']] ?? 'badge-secondary' ?>" style="font-size: 0.72rem;">
                      <?= $tipoLabel[$l['TipoLinea']] ?? $l['TipoLinea'] ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge-origen <?= $orgData['class'] ?>" title="<?= $orgData['title'] ?>">
                      <?= $orgData['label'] ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-weight: 700;"><?= htmlspecialchars($l['Descripcion']) ?></div>
                    <?php if ($l['PoliticaCobro'] === 'SoloSiNoAprueba'): ?>
                      <span style="font-size: 0.72rem; color: #f59e0b;">(Condicional: solo se cobra si no aprueba reparación)</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: right;"><?= number_format($l['Cantidad'], 0) ?></td>
                  <td style="text-align: right;"><?= formatCLP($l['PrecioUnitario']) ?></td>
                  <td style="text-align: right; font-weight: 700; color: #38bdf8;"><?= formatCLP($l['Subtotal']) ?></td>
                  <?php if ($pendiente): ?>
                    <td style="text-align: center;">
                      <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="display: inline;" onsubmit="return confirm('¿Eliminar esta línea del presupuesto?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="eliminar_linea">
                        <input type="hidden" name="linea_id" value="<?= $l['PresupuestoDetalleID'] ?>">
                        <button type="submit" class="btn" style="background: none; border: none; color: #ef4444; padding: 0.2rem 0.4rem; cursor: pointer;" title="Eliminar">
                          <i class="fa-solid fa-trash-can"></i>
                        </button>
                      </form>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <tr class="pr-total-row">
                <td colspan="5" style="text-align: right; font-size: 1.05rem;">TOTAL PRESUPUESTO:</td>
                <td style="text-align: right; font-size: 1.25rem; color: #fbbf24;"><?= formatCLP($total) ?></td>
                <?php if ($pendiente): ?><td></td><?php endif; ?>
              </tr>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <!-- FORMULARIOS DE AGREGAR LÍNEAS (SOLO EN MODO PENDIENTE) -->
      <?php if ($pendiente): ?>
        <div class="pr-card no-print" style="margin-top: 1.25rem;" id="formAgregarSeccion">
          <div class="pr-tabs">
            <button type="button" class="pr-tab-btn active" onclick="switchPrTab('repuesto', this)"><i class="fa-solid fa-box"></i> Agregar Repuesto</button>
            <button type="button" class="pr-tab-btn" onclick="switchPrTab('servicio', this)"><i class="fa-solid fa-wrench"></i> Servicio / Mano de Obra</button>
            <button type="button" class="pr-tab-btn" onclick="switchPrTab('libre', this)"><i class="fa-solid fa-pen-to-square"></i> Línea Libre / Personalizada</button>
          </div>

          <!-- TAB 1: AGREGAR REPUESTO DEL INVENTARIO -->
          <div id="tab-pr-repuesto">

            <?php if (!empty($repuestosAnterioresUnicos)): ?>
              <!-- SECCIÓN 1: USADO ANTERIORMENTE EN ESTE VEHÍCULO (DESPLEGABLE) -->
              <details class="pr-collapsible" style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 8px; margin-bottom: 1rem;">
                <summary style="padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
                  <div style="font-size: 0.86rem; font-weight: 700; color: #34d399; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Repuestos usados anteriormente en este vehículo (Patente: <?= htmlspecialchars($ot['Patente']) ?>)
                    <span class="badge badge-success" style="font-size: 0.68rem;"><?= count($repuestosAnterioresUnicos) ?> <?= count($repuestosAnterioresUnicos) === 1 ? 'ítem' : 'ítems' ?></span>
                  </div>
                  <div style="font-size: 0.74rem; color: #34d399; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
                    <span>Ver historial</span>
                    <i class="fa-solid fa-chevron-down pr-chevron" style="transition: transform 0.2s ease;"></i>
                  </div>
                </summary>

                <div style="padding: 0.75rem 0.85rem 0.85rem; border-top: 1px dashed rgba(16, 185, 129, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
                  <?php foreach ($repuestosAnterioresUnicos as $rh): 
                    $precioFinal = (int)($rh['PrecioActual'] ?: $rh['PrecioUnitario']);
                    $enPresupuesto = false;
                    foreach ($lineas as $lin) {
                      if (($rh['ProductoID'] && (int)($lin['ProductoID'] ?? 0) === (int)$rh['ProductoID']) || 
                          mb_strtolower(trim($lin['Descripcion'])) === mb_strtolower(trim($rh['Descripcion']))) {
                        $enPresupuesto = true;
                        break;
                      }
                    }
                  ?>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; padding: 0.55rem 0.75rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                      <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                        <span style="font-size: 1.15rem; opacity: 0.85;">
                          <?= (str_contains(mb_strtolower($rh['Descripcion']), 'filtro') ? '🟡' : (str_contains(mb_strtolower($rh['Descripcion']), 'aceite') ? '🛢️' : '📦')) ?>
                        </span>
                        <div style="min-width: 0;">
                          <div style="font-size: 0.84rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($rh['Descripcion']) ?>
                            <?php if (!empty($rh['MarcaRepuesto'])): ?>
                              <span style="font-size: 0.72rem; color: #94a3b8; font-weight: normal;">(<?= htmlspecialchars($rh['MarcaRepuesto']) ?>)</span>
                            <?php endif; ?>
                          </div>
                          <div style="font-size: 0.72rem; color: var(--text-muted); display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                            <span>Última vez: <?= formatFolioOT($rh['OrdenTrabajoID']) ?> (<?= date('d/m/Y', strtotime($rh['FechaIngreso'])) ?>)</span>
                            <?php if ($rh['Stock'] !== null): ?>
                              <span class="badge <?= $rh['Stock'] > 0 ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.65rem;">Stock: <?= $rh['Stock'] ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>

                      <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                        <span style="font-size: 0.92rem; font-weight: 700; color: #38bdf8;">
                          <?= formatCLP($precioFinal) ?>
                        </span>
                        <?php if ($enPresupuesto): ?>
                          <span class="badge badge-secondary" style="font-size: 0.72rem; padding: 0.35rem 0.6rem;">
                            <i class="fa-solid fa-check"></i> Ya Agregado
                          </span>
                        <?php else: ?>
                          <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="margin: 0;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="reutilizar_repuesto_historial">
                            <input type="hidden" name="producto_id" value="<?= (int)($rh['ProductoID'] ?? 0) ?>">
                            <input type="hidden" name="descripcion" value="<?= htmlspecialchars($rh['Descripcion']) ?>">
                            <input type="hidden" name="precio" value="<?= $precioFinal ?>">
                            <input type="hidden" name="tipo" value="<?= htmlspecialchars($rh['TipoLinea'] ?? 'Repuesto') ?>">
                            <input type="hidden" name="origen" value="Historial">
                            <button type="submit" class="btn btn-outline-success" style="padding: 0.25rem 0.65rem; font-size: 0.76rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                              <i class="fa-solid fa-bolt"></i> Reutilizar
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </details>
            <?php endif; ?>

            <?php if (!empty($repuestosCompatiblesModelo)): ?>
              <!-- SECCIÓN 2: REPUESTOS COMPATIBLES CON EL MODELO (DESPLEGABLE) -->
              <details class="pr-collapsible" style="background: rgba(59, 130, 246, 0.04); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; margin-bottom: 1rem;">
                <summary style="padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
                  <div style="font-size: 0.86rem; font-weight: 700; color: #60a5fa; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="fa-solid fa-car-side"></i> Otros repuestos comprobados en <?= htmlspecialchars($ot['Marca'] . ' ' . $ot['Modelo']) ?>
                    <span class="badge badge-info" style="font-size: 0.68rem;"><?= count($repuestosCompatiblesModelo) ?> <?= count($repuestosCompatiblesModelo) === 1 ? 'sugerido' : 'sugeridos' ?></span>
                  </div>
                  <div style="font-size: 0.74rem; color: #60a5fa; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
                    <span>Ver matriz</span>
                    <i class="fa-solid fa-chevron-down pr-chevron" style="transition: transform 0.2s ease;"></i>
                  </div>
                </summary>

                <div style="padding: 0.75rem 0.85rem 0.85rem; border-top: 1px dashed rgba(59, 130, 246, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
                  <?php foreach ($repuestosCompatiblesModelo as $rcm): 
                    $enPresupuesto = false;
                    foreach ($lineas as $lin) {
                      if ((int)($lin['ProductoID'] ?? 0) === (int)$rcm['ProductoID']) {
                        $enPresupuesto = true;
                        break;
                      }
                    }
                  ?>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; padding: 0.55rem 0.75rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                      <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                        <span style="font-size: 1.15rem; opacity: 0.85;">
                          <?= ($rcm['TipoRepuesto'] === 'FiltroAceite' ? '🟡' : ($rcm['TipoRepuesto'] === 'Aceite' ? '🛢️' : '⚙️')) ?>
                        </span>
                        <div style="min-width: 0;">
                          <div style="font-size: 0.84rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($rcm['ProductoNombre']) ?>
                            <?php if (!empty($rcm['MarcaRepuesto'])): ?>
                              <span style="font-size: 0.72rem; color: #94a3b8; font-weight: normal;">(<?= htmlspecialchars($rcm['MarcaRepuesto']) ?>)</span>
                            <?php endif; ?>
                          </div>
                          <div style="font-size: 0.72rem; color: var(--text-muted); display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                            <span>Tipo: <?= htmlspecialchars($rcm['TipoRepuesto']) ?></span>
                            <?php if (!empty($rcm['NumeroParteOEM'])): ?>
                              <span>OEM: <?= htmlspecialchars($rcm['NumeroParteOEM']) ?></span>
                            <?php endif; ?>
                            <span class="badge <?= $rcm['Stock'] > 0 ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.65rem;">Stock: <?= $rcm['Stock'] ?></span>
                            <span style="color: #60a5fa;">Usado <?= (int)$rcm['VecesUsado'] ?> veces</span>
                          </div>
                        </div>
                      </div>

                      <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                        <span style="font-size: 0.92rem; font-weight: 700; color: #38bdf8;">
                          <?= formatCLP($rcm['PrecioVenta']) ?>
                        </span>
                        <?php if ($enPresupuesto): ?>
                          <span class="badge badge-secondary" style="font-size: 0.72rem; padding: 0.35rem 0.6rem;">
                            <i class="fa-solid fa-check"></i> Ya Agregado
                          </span>
                        <?php else: ?>
                          <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="margin: 0;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="reutilizar_repuesto_historial">
                            <input type="hidden" name="producto_id" value="<?= (int)$rcm['ProductoID'] ?>">
                            <input type="hidden" name="descripcion" value="<?= htmlspecialchars($rcm['ProductoNombre']) ?>">
                            <input type="hidden" name="precio" value="<?= (int)$rcm['PrecioVenta'] ?>">
                            <input type="hidden" name="tipo" value="Repuesto">
                            <input type="hidden" name="origen" value="Compatibilidad">
                            <button type="submit" class="btn btn-outline-primary" style="padding: 0.25rem 0.65rem; font-size: 0.76rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                              <i class="fa-solid fa-plus"></i> Agregar
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </details>
            <?php endif; ?>

            <!-- SECCIÓN 3: BUSCADOR EN TODO EL CATÁLOGO -->
            <div style="border-top: 1px dashed var(--border-dark); padding-top: 1rem; margin-top: 0.5rem;">
              <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.6rem;">
                <i class="fa-solid fa-magnifying-glass"></i> O buscar en todo el catálogo de inventario:
              </div>
              <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="agregar_repuesto">
                <input type="hidden" name="origen" id="repuesto_origen" value="Directo">
                <div style="display: grid; grid-template-columns: 1fr 110px 140px; gap: 0.75rem; align-items: flex-end;">
                  <div>
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Seleccionar repuesto:</label>
                    <select name="producto_id" class="form-control" id="selectRepuestoPresupuesto" required>
                      <option value="">-- Selecciona un repuesto --</option>
                      <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['ProductoID'] ?>">
                          <?= htmlspecialchars($p['Nombre']) ?> <?= $p['MarcaRepuesto'] ? '(' . htmlspecialchars($p['MarcaRepuesto']) . ')' : '' ?> - <?= formatCLP($p['PrecioVenta']) ?> (Stock: <?= $p['Stock'] ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Cantidad:</label>
                    <input type="number" name="cantidad" class="form-control" value="1" min="1" step="1" required>
                  </div>
                  <div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700; padding: 0.55rem;">
                      <i class="fa-solid fa-plus"></i> Agregar
                    </button>
                  </div>
                </div>
              </form>
            </div>

            <!-- SECCIÓN 4: GUÍA Y CATÁLOGOS EXTERNOS PARA MODELOS NUEVOS O BD LIMPIA -->
            <div style="margin-top: 1.25rem; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.12); border-radius: 8px; padding: 0.75rem 0.9rem;">
              <details style="cursor: pointer;">
                <summary style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; outline: none; display: flex; align-items: center; gap: 0.4rem;">
                  <i class="fa-solid fa-circle-question" style="color: #38bdf8;"></i> ¿Cómo saber qué filtro o aceite usar si es un auto nuevo o la base de datos está limpia?
                </summary>
                <div style="margin-top: 0.65rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.55;">
                  <p style="margin-bottom: 0.4rem;">
                    Cuando un vehículo ingresa por <strong>primera vez</strong> al taller, o si tienes una base de datos nueva sin historial previo, el mecánico consulta la especificación del fabricante o los catálogos oficiales rápidos:
                  </p>
                  <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 0.65rem;">
                    <a href="https://catalog.mann-filter.com" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="font-size: 0.74rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i> Catálogo Mann-Filter Online
                    </a>
                    <a href="https://www.shell.cl/conductores/aceites-y-lubricantes-para-el-motor/lubematch.html" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="font-size: 0.74rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i> Guía Shell LubeMatch
                    </a>
                    <a href="productos.php" target="_blank" class="btn btn-secondary" style="font-size: 0.74rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                      <i class="fa-solid fa-plus"></i> Crear Repuesto en Inventario
                    </a>
                  </div>
                  <p style="margin-bottom: 0; color: #34d399;">
                    <i class="fa-solid fa-brain"></i> <strong>Aprendizaje del sistema:</strong> En cuanto cotizas y apruebas el repuesto en esta OT por primera vez, el sistema lo memoriza automáticamente para esta patente (<strong><?= htmlspecialchars($ot['Patente']) ?></strong>) y para el modelo <strong><?= htmlspecialchars($ot['Marca'] . ' ' . $ot['Modelo']) ?></strong>. En todas las visitas futuras aparecerá sugerido con 1 solo clic.
                  </p>
                </div>
              </details>
            </div>

          </div>

          <!-- TAB 2: AGREGAR SERVICIO / MANO DE OBRA -->
          <div id="tab-pr-servicio" style="display: none;">

            <?php if (!empty($serviciosAnterioresUnicos)): ?>
              <!-- HISTORIAL DE SERVICIOS PREVIOS EN ESTE VEHÍCULO (DESPLEGABLE) -->
              <details class="pr-collapsible" style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.25); border-radius: 8px; margin-bottom: 1rem;">
                <summary style="padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
                  <div style="font-size: 0.86rem; font-weight: 700; color: #fbbf24; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Servicios realizados anteriormente en este vehículo (Patente: <?= htmlspecialchars($ot['Patente']) ?>)
                    <span class="badge badge-warning" style="font-size: 0.68rem;"><?= count($serviciosAnterioresUnicos) ?> <?= count($serviciosAnterioresUnicos) === 1 ? 'servicio' : 'servicios' ?></span>
                  </div>
                  <div style="font-size: 0.74rem; color: #fbbf24; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
                    <span>Ver historial de servicios</span>
                    <i class="fa-solid fa-chevron-down pr-chevron" style="transition: transform 0.2s ease;"></i>
                  </div>
                </summary>

                <div style="padding: 0.75rem 0.85rem 0.85rem; border-top: 1px dashed rgba(245, 158, 11, 0.2); display: flex; flex-direction: column; gap: 0.5rem;">
                  <?php foreach ($serviciosAnterioresUnicos as $sh): 
                    $precioFinal = (int)($sh['PrecioActual'] ?: $sh['PrecioUnitario']);
                    $enPresupuesto = false;
                    foreach ($lineas as $lin) {
                      if (mb_strtolower(trim($lin['Descripcion'])) === mb_strtolower(trim($sh['Descripcion']))) {
                        $enPresupuesto = true;
                        break;
                      }
                    }
                  ?>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; padding: 0.55rem 0.75rem; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                      <div style="display: flex; align-items: center; gap: 0.65rem; min-width: 0;">
                        <span style="font-size: 1.15rem; opacity: 0.85;">🔧</span>
                        <div style="min-width: 0;">
                          <div style="font-size: 0.84rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($sh['Descripcion']) ?>
                          </div>
                          <div style="font-size: 0.72rem; color: var(--text-muted); display: flex; gap: 0.6rem; align-items: center;">
                            <span>Última vez: <?= formatFolioOT($sh['OrdenTrabajoID']) ?> (<?= date('d/m/Y', strtotime($sh['FechaIngreso'])) ?>)</span>
                          </div>
                        </div>
                      </div>

                      <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                        <span style="font-size: 0.92rem; font-weight: 700; color: #fbbf24;">
                          <?= formatCLP($precioFinal) ?>
                        </span>
                        <?php if ($enPresupuesto): ?>
                          <span class="badge badge-secondary" style="font-size: 0.72rem; padding: 0.35rem 0.6rem;">
                            <i class="fa-solid fa-check"></i> Ya Agregado
                          </span>
                        <?php else: ?>
                          <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="margin: 0;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="reutilizar_repuesto_historial">
                            <input type="hidden" name="producto_id" value="0">
                            <input type="hidden" name="descripcion" value="<?= htmlspecialchars($sh['Descripcion']) ?>">
                            <input type="hidden" name="precio" value="<?= $precioFinal ?>">
                            <input type="hidden" name="tipo" value="ManoObra">
                            <input type="hidden" name="origen" value="Historial">
                            <button type="submit" class="btn btn-outline-warning" style="padding: 0.25rem 0.65rem; font-size: 0.76rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                              <i class="fa-solid fa-bolt"></i> Reutilizar
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </details>
            <?php endif; ?>

            <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="agregar_servicio">
              <input type="hidden" name="origen" id="servicio_origen" value="Directo">
              <div style="display: grid; grid-template-columns: 1fr 110px 140px; gap: 0.75rem; align-items: flex-end;">
                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Seleccionar servicio de mano de obra:</label>
                  <select name="servicio_id" class="form-control" id="selectServicioPresupuesto" required>
                    <option value="">-- Selecciona un servicio estándar --</option>
                    <?php foreach ($servicios as $s): ?>
                      <option value="<?= $s['OperacionID'] ?>">
                        <?= htmlspecialchars($s['Nombre']) ?> (<?= htmlspecialchars($s['Categoria'] ?? 'General') ?>) - <?= formatCLP($s['PrecioBase']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Cantidad:</label>
                  <input type="number" name="cantidad" class="form-control" value="1" min="1" step="1" required>
                </div>
                <div>
                  <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700; padding: 0.55rem;">
                    <i class="fa-solid fa-plus"></i> Agregar
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- TAB 3: AGREGAR LÍNEA PERSONALIZADA O DESDE HALLAZGO -->
          <div id="tab-pr-libre" style="display: none;">
            <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="agregar_linea">
              <div style="display: grid; grid-template-columns: 140px 1fr 130px 90px 130px; gap: 0.65rem; align-items: flex-end;">
                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Tipo:</label>
                  <select name="tipo" class="form-control" id="libre_tipo">
                    <option value="ManoObra">Mano de Obra</option>
                    <option value="Repuesto">Repuesto</option>
                    <option value="Terceros">Terceros</option>
                  </select>
                </div>
                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Descripción detallada:</label>
                  <input type="text" name="descripcion" id="libre_desc" class="form-control" placeholder="Ej: Rectificación de discos delanteros" required>
                </div>
                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Precio Unitario ($):</label>
                  <input type="number" name="precio" id="libre_precio" class="form-control" placeholder="0" min="100" required>
                </div>
                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.3rem;">Cant.:</label>
                  <input type="number" name="cantidad" class="form-control" value="1" min="1" step="1" required>
                </div>
                <div>
                  <input type="hidden" name="origen" id="libre_origen" value="Directo">
                  <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700; padding: 0.55rem;">
                    <i class="fa-solid fa-plus"></i> Guardar
                  </button>
                </div>
              </div>
            </form>
          </div>

        </div>
      <?php endif; ?>

      <!-- CAJA DE DECISIÓN DEL CLIENTE -->
      <?php if (!empty($lineas)): ?>
        <div class="pr-card no-print" style="margin-top: 1.25rem;">
          <div class="pr-card-header">
            <div class="pr-card-title">
              <i class="fa-solid fa-signature"></i> Decisión del Cliente
            </div>
            <?php if ($decidido): ?>
              <span class="badge <?= $presupuesto['DecisionCliente'] === 'Rechazado' ? 'badge-danger' : 'badge-success' ?>">
                <?= htmlspecialchars($presupuesto['DecisionCliente']) ?> el <?= date('d/m/Y H:i', strtotime($presupuesto['FechaDecision'])) ?>
              </span>
            <?php endif; ?>
          </div>

          <?php if ($pendiente): ?>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
              Una vez que compartas el presupuesto con <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong> y obtengas su respuesta, registra aquí su decisión:
            </p>

            <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="decidir">
              
              <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <button type="submit" name="decision" value="AprobadoTotal" class="btn btn-success" style="padding: 0.65rem 1.2rem; font-weight: 800;">
                  <i class="fa-solid fa-check-double"></i> Cliente Aprobó Todo (<?= formatCLP($total) ?>)
                </button>
                <button type="submit" name="decision" value="Rechazado" class="btn btn-secondary" style="padding: 0.65rem 1.2rem; font-weight: 700;" onclick="return confirm('¿Seguro que el cliente rechazó el presupuesto?');">
                  <i class="fa-solid fa-xmark"></i> Cliente Rechazó
                </button>

                <div style="margin-left: auto; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                  <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background: #25d366; color: #ffffff; padding: 0.65rem 1.15rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem;" title="Compartir cotización por WhatsApp al cliente">
                    <i class="fa-brands fa-whatsapp" style="font-size: 1.1rem;"></i> Enviar por WhatsApp
                  </a>
                  <a href="comprobante_presupuesto.php?id=<?= $otId ?>" target="_blank" class="btn btn-secondary" style="padding: 0.65rem 1.1rem;">
                    <i class="fa-solid fa-file-pdf"></i> Ver Cotización Formal (PDF)
                  </a>
                </div>
              </div>
            </form>
          <?php else: ?>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
              <div>
                <strong>Estado:</strong> Presupuesto <?= htmlspecialchars($presupuesto['DecisionCliente']) ?>.<br>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Monto aceptado: <strong style="color: #38bdf8;"><?= formatCLP($totalAprobado) ?></strong></span>
              </div>
              <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background: #25d366; color: #ffffff; padding: 0.65rem 1.15rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem;" title="Reenviar por WhatsApp">
                  <i class="fa-brands fa-whatsapp" style="font-size: 1.1rem;"></i> Reenviar por WhatsApp
                </a>
                <a href="comprobante_presupuesto.php?id=<?= $otId ?>" target="_blank" class="btn btn-secondary" style="padding: 0.65rem 1.1rem;">
                  <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <?php if ($presupuesto['DecisionCliente'] !== 'Rechazado'): ?>
                  <a href="ejecucion.php?id=<?= $otId ?>" class="btn btn-primary" style="padding: 0.65rem 1.25rem; font-weight: 800;">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Ir al Taller / Ejecución de la OT
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>

  </div>

<?php endif; ?>

<script>
  function switchPrTab(tipo, btn) {
    document.querySelectorAll('.pr-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.getElementById('tab-pr-repuesto').style.display = tipo === 'repuesto' ? 'block' : 'none';
    document.getElementById('tab-pr-servicio').style.display = tipo === 'servicio' ? 'block' : 'none';
    document.getElementById('tab-pr-libre').style.display = tipo === 'libre' ? 'block' : 'none';
  }

  // Pre-llenar formulario de cotización cuando se presiona [Cotizar Solución] desde un hallazgo
  function prepararLineaDesdeHallazgo(hallazgo, area) {
    const tabLibreBtn = document.querySelectorAll('.pr-tab-btn')[2];
    if (tabLibreBtn) switchPrTab('libre', tabLibreBtn);

    const descInput = document.getElementById('libre_desc');
    const origenInput = document.getElementById('libre_origen');
    const tipoSelect = document.getElementById('libre_tipo');
    const precioInput = document.getElementById('libre_precio');

    if (descInput) descInput.value = 'Solución ' + area + ': ' + hallazgo;
    if (origenInput) origenInput.value = 'Diagnostico';
    if (tipoSelect) tipoSelect.value = 'ManoObra';
    if (precioInput) {
      precioInput.focus();
      precioInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
</script>


<!-- Modal para ingresar o actualizar teléfono del cliente -->
<div id="modalTelCliente" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(6px); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 14px; width: 420px; padding: 1.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
      <i class="fa-brands fa-whatsapp" style="color: #25d366; font-size: 1.3rem;"></i> Teléfono del Cliente
    </h3>
    <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 1.25rem;">
      Ingresa el número de <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong> para que el botón de WhatsApp abra el chat directo de inmediato:
    </p>
    <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="actualizar_telefono_cliente">
      <div style="margin-bottom: 1.25rem;">
        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">NÚMERO DE TELÉFONO / CELULAR:</label>
        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($ot['ClienteTelefono'] ?? '') ?>" placeholder="Ej: +56 9 1234 5678 o 912345678" required autofocus style="font-size: 1.05rem; font-weight: 700;">
        <span style="font-size: 0.74rem; color: var(--text-muted); display: block; margin-top: 0.35rem;">
          💡 Puede ser con o sin +56 (ej: <code>912345678</code>). El sistema lo normaliza automáticamente al formato WhatsApp de Chile.
        </span>
      </div>
      <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalTelCliente').style.display='none'">Cancelar</button>
        <button type="submit" class="btn btn-success" style="font-weight: 700; background: #25d366; border-color: #25d366; color: #fff;">
          <i class="fa-solid fa-floppy-disk"></i> Guardar teléfono
        </button>
      </div>
    </form>
  </div>
</div>
