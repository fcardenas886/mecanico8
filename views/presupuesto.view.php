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
    'Fluidos'        => ['label' => '🛢️ Insumos', 'class' => 'badge-info', 'title' => 'Insumo o lubricante sugerido'],
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

// Calcular métricas de cobertura para el Semáforo (Solo cliente y diagnóstico real)
$totalRequerimientos = 0;
$cubiertosRequerimientos = 0;
$itemsPendientes = [];

// 1. Petición en texto libre
if (!empty($ot['OperacionesTextoLibre'])) {
    $totalRequerimientos++;
    if (verificarItemCotizado($ot['OperacionesTextoLibre'], $lineas, 'Cliente')) {
        $cubiertosRequerimientos++;
    } else {
        $itemsPendientes[] = 'Nota cliente: "' . mb_substr($ot['OperacionesTextoLibre'], 0, 32) . '..."';
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

// 3. Hallazgos del diagnóstico técnico
foreach ($hallazgos as $h) {
    $totalRequerimientos++;
    if (verificarItemCotizado($h['Hallazgo'], $lineas, 'Diagnostico')) {
        $cubiertosRequerimientos++;
    } else {
        $itemsPendientes[] = $h['Area'] . ': ' . mb_substr($h['Hallazgo'], 0, 28);
    }
}

$porcentajeCobertura = $totalRequerimientos > 0 ? (int)round(($cubiertosRequerimientos / $totalRequerimientos) * 100) : 100;
?>

<style>
  /* ======================================================== */
  /* NUEVA VERSIÓN VISUAL: PRESUPUESTO MODERNO Y ELEGANTE     */
  /* ======================================================== */

  /* Header Superior con Resumen */
  .pr-top-bar {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.85) 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.25rem;
    flex-wrap: wrap;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  }
  .pr-folio-badge {
    background: rgba(56, 189, 248, 0.15);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #38bdf8;
    font-weight: 800;
    font-size: 0.85rem;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    letter-spacing: 0.5px;
  }
  .pr-patente-pill {
    background: #0f172a;
    border: 1.5px solid #38bdf8;
    color: #f8fafc;
    font-family: monospace;
    font-weight: 800;
    font-size: 0.95rem;
    padding: 0.18rem 0.65rem;
    border-radius: 6px;
    display: inline-block;
    letter-spacing: 1px;
  }

  /* Layout Principal Grid 2 Columnas */
  .pr-grid-container {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 1.25rem;
    align-items: start;
    margin-bottom: 2rem;
  }
  @media (max-width: 1080px) {
    .pr-grid-container {
      grid-template-columns: 1fr;
    }
  }

  /* Columna Lateral Izquierda */
  .pr-sidebar-indice {
    position: sticky;
    top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  /* Tarjetas Modernas */
  .pr-card {
    background: linear-gradient(180deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.75) 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
    backdrop-filter: blur(8px);
  }

  .pr-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 0.75rem;
    margin-bottom: 0.95rem;
  }
  .pr-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    letter-spacing: 0.3px;
  }

  /* Items del Índice (Requerimientos) */
  .pr-item-req {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    padding: 0.7rem 0.85rem;
    margin-bottom: 0.6rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    transition: all 0.2s ease;
  }
  .pr-item-req:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.12);
  }
  .pr-item-req.is-covered {
    border-left: 3.5px solid #10b981;
    background: rgba(16, 185, 129, 0.04);
  }
  .pr-item-req.is-pending {
    border-left: 3.5px solid #f59e0b;
    background: rgba(245, 158, 11, 0.03);
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
    line-height: 1.35;
    color: #f1f5f9;
  }
  .pr-item-meta {
    font-size: 0.76rem;
    color: var(--text-muted);
  }

  /* Cuadro de Cita del Cliente */
  .pr-quote-box {
    background: rgba(59, 130, 246, 0.07);
    border: 1px solid rgba(59, 130, 246, 0.25);
    border-left: 3.5px solid #3b82f6;
    border-radius: 8px;
    padding: 0.75rem 0.95rem;
    font-size: 0.85rem;
    color: #bfdbfe;
    line-height: 1.5;
    margin-bottom: 0.85rem;
    font-style: italic;
  }

  /* Nota de Inspección del Mecánico */
  .pr-obs-box {
    margin-top: 0.85rem;
    padding: 0.75rem 0.95rem;
    background: rgba(56, 189, 248, 0.05);
    border: 1px solid rgba(56, 189, 248, 0.22);
    border-left: 3.5px solid #38bdf8;
    border-radius: 8px;
  }
  .pr-obs-header {
    font-size: 0.74rem;
    font-weight: 700;
    color: #38bdf8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }
  .pr-obs-body {
    font-size: 0.83rem;
    color: #e2e8f0;
    line-height: 1.45;
  }

  /* Barra Semáforo de Cobertura */
  .pr-semaforo-bar {
    background: linear-gradient(135deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.75) 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 1rem 1.35rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  }
  .pr-prog-track {
    background: #090d16;
    border-radius: 999px;
    height: 10px;
    overflow: hidden;
    margin: 0.6rem 0;
    border: 1px solid rgba(255, 255, 255, 0.06);
  }
  .pr-prog-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .pr-pendientes-chips {
    display: flex;
    gap: 0.45rem;
    flex-wrap: wrap;
    margin-top: 0.45rem;
  }
  .pr-chip-pend {
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #fbbf24;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }

  /* Tabla de Líneas Moderna */
  .pr-lineas-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.88rem;
  }
  .pr-lineas-table th {
    text-align: left;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #94a3b8;
    padding: 0.75rem 0.65rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  .pr-lineas-table td {
    padding: 0.8rem 0.65rem;
    border-bottom: 1px dashed rgba(255, 255, 255, 0.07);
    vertical-align: middle;
    transition: background 0.15s ease;
  }
  .pr-lineas-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
  }
  .pr-total-row td {
    border-bottom: none;
    font-weight: 700;
    padding-top: 1rem;
    border-top: 2px solid rgba(255, 255, 255, 0.12);
  }

  /* Input de Precio en Línea con Edición Fluida */
  .pr-price-box {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    background: rgba(15, 23, 42, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    padding: 0.15rem 0.35rem;
    transition: all 0.2s ease;
  }
  .pr-price-box:focus-within {
    border-color: #38bdf8;
    box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
  }
  .pr-price-box input {
    width: 90px;
    text-align: right;
    background: transparent;
    border: none;
    outline: none;
    color: #fff;
    font-size: 0.86rem;
    font-weight: 700;
  }

  /* Insignia de Origen */
  .badge-origen {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
  }

  /* Pestañas de Agregar Línea */
  .pr-tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 1.25rem;
  }
  .pr-tab-btn {
    background: none;
    border: none;
    padding: 0.65rem 1.1rem;
    font-size: 0.86rem;
    font-weight: 700;
    color: #94a3b8;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
  }
  .pr-tab-btn:hover {
    color: #f1f5f9;
  }
  .pr-tab-btn.active {
    color: #38bdf8;
    border-bottom-color: #38bdf8;
  }

  /* Acordeones Desplegables */
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

<!-- ======================================================== -->
<!-- 1. BARRA SUPERIOR: FOLIO, VEHÍCULO, CLIENTE Y ACCIONES   -->
<!-- ======================================================== -->
<div class="pr-top-bar no-print">
  <div>
    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
      <span class="pr-folio-badge"><?= htmlspecialchars($folio) ?></span>
      <span class="pr-patente-pill"><?= htmlspecialchars($ot['Patente']) ?></span>
      <span style="font-size: 1.1rem; font-weight: 700; color: #fff;">
        <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?>
      </span>
      <span style="color: var(--text-muted); font-size: 0.88rem;">(<?= $ot['Anio'] ?>)</span>
      <?php if (!empty($ot['KilometrajeUltimo'])): ?>
        <span class="badge badge-secondary" style="font-size: 0.72rem;"><?= number_format($ot['KilometrajeUltimo'], 0, ',', '.') ?> km</span>
      <?php endif; ?>
    </div>
    <div style="color: #cbd5e1; font-size: 0.88rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
      <i class="fa-solid fa-user" style="color: #94a3b8; font-size: 0.8rem;"></i>
      <strong style="color: #fff;"><?= htmlspecialchars($ot['ClienteNombre']) ?></strong>
      <?php if (!empty($ot['ClienteTelefono'])): ?>
        <span style="color: #34d399; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="Teléfono registrado para WhatsApp">
          <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($ot['ClienteTelefono']) ?>
        </span>
        <button type="button" class="btn btn-link" onclick="document.getElementById('modalTelCliente').style.display='flex'" style="color: #94a3b8; font-size: 0.75rem; padding: 0 0.3rem; text-decoration: underline;" title="Modificar número de teléfono">
          <i class="fa-solid fa-pen"></i> Cambiar
        </button>
      <?php else: ?>
        <span class="badge badge-warning" style="font-size: 0.72rem;">
          <i class="fa-solid fa-phone-slash"></i> Sin teléfono
        </span>
        <button type="button" class="btn btn-outline-warning" onclick="document.getElementById('modalTelCliente').style.display='flex'" style="padding: 0.15rem 0.5rem; font-size: 0.72rem; font-weight: 700;" title="Ingresar teléfono para enviar WhatsApp directo">
          <i class="fa-solid fa-plus"></i> Ingresar número
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
    <span class="badge <?= $pendiente ? 'badge-warning' : 'badge-success' ?>" style="padding: 0.45rem 0.75rem; font-size: 0.82rem; font-weight: 700;">
      <?= htmlspecialchars(otEstadoInfo($ot + ['PresupuestoID' => $presupuesto['PresupuestoID'] ?? null, 'DecisionCliente' => $presupuesto['DecisionCliente'] ?? null, 'CantidadLineasPresupuesto' => count($lineas)])['etiqueta']) ?>
    </span>
    <?php if ($presupuesto): ?>
      <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background: #25d366; color: #ffffff; padding: 0.45rem 0.9rem; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 6px;" title="Enviar presupuesto por WhatsApp al cliente">
        <i class="fa-brands fa-whatsapp" style="font-size: 1.05rem;"></i> WhatsApp
      </a>
      <a href="comprobante_presupuesto.php?id=<?= $otId ?>" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem; border-radius: 6px;" target="_blank">
        <i class="fa-solid fa-print"></i> Imprimir
      </a>
    <?php endif; ?>
    <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.85rem; border-radius: 6px;">
      <i class="fa-solid fa-arrow-left"></i> Volver a OTs
    </a>
  </div>
</div>

<?php otStepper($ot, 3); ?>

<!-- ======================================================== -->
<!-- 2. CONTROL DE CALIDAD: BARRA SEMÁFORO DE COBERTURA       -->
<!-- ======================================================== -->
<div class="pr-semaforo-bar no-print">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
    <div style="font-size: 0.92rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
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
      <span style="font-size: 0.75rem; color: var(--text-muted); align-self: center;">Requerimientos sin cotizar:</span>
      <?php foreach (array_slice($itemsPendientes, 0, 4) as $itemP): ?>
        <span class="pr-chip-pend"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($itemP) ?></span>
      <?php endforeach; ?>
      <?php if (count($itemsPendientes) > 4): ?>
        <span class="pr-chip-pend">+<?= count($itemsPendientes) - 4 ?> más</span>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div style="font-size: 0.78rem; color: #10b981; margin-top: 0.25rem;">
      <i class="fa-solid fa-circle-check"></i> Todos los requerimientos del cliente y hallazgos del diagnóstico han sido incluidos en este presupuesto.
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
  <!-- 3. GRID PRINCIPAL: ÍNDICE IZQUIERDO + DETALLE DERECHO    -->
  <!-- ======================================================== -->
  <div class="pr-grid-container">

    <!-- 1. COLUMNA LATERAL: REQUERIMIENTOS Y DIAGNÓSTICO -->
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
                  <span class="badge badge-success" style="font-size: 0.68rem;"><i class="fa-solid fa-check"></i> Cotizado</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size: 0.68rem;">Pendiente</span>
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
                    <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.55rem; font-size: 0.74rem; font-weight: 700;">
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
                  <span class="badge badge-info" style="font-size: 0.65rem; margin-bottom: 2px;"><?= htmlspecialchars($h['Area']) ?></span>
                  <div class="pr-item-name" style="font-size: 0.84rem;"><?= htmlspecialchars($h['Hallazgo']) ?></div>
                </div>
                <?php if ($diagCubierto): ?>
                  <span class="badge badge-success" style="font-size: 0.68rem;"><i class="fa-solid fa-check"></i> Incluido</span>
                <?php else: ?>
                  <span class="badge badge-warning" style="font-size: 0.68rem;">Pendiente</span>
                <?php endif; ?>
              </div>

              <?php if ($pendiente && !$diagCubierto): ?>
                <div style="margin-top: 0.35rem; padding-top: 0.35rem; border-top: 1px dashed rgba(255,255,255,0.06); display: flex; gap: 0.4rem; justify-content: flex-end;">
                  <button type="button" class="btn btn-secondary" style="padding: 0.22rem 0.5rem; font-size: 0.74rem;" onclick="prepararLineaDesdeHallazgo('<?= htmlspecialchars(addslashes($h['Hallazgo'])) ?>', '<?= htmlspecialchars(addslashes($h['Area'])) ?>')">
                    <i class="fa-solid fa-plus"></i> Cotizar Solución
                  </button>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty(trim($estacion['Observaciones'] ?? ''))): ?>
          <div class="pr-obs-box">
            <div class="pr-obs-header">
              <i class="fa-solid fa-clipboard-check"></i> Observaciones de Inspección / Diagnóstico
            </div>
            <div class="pr-obs-body">
              <?= nl2br(htmlspecialchars(trim($estacion['Observaciones']))) ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- 2. COLUMNA DERECHA: TABLA DE LÍNEAS, TOTALES Y FORMULARIOS -->
    <div class="pr-main-col">

      <!-- TARJETA: DETALLE DE ÍTEMS -->
      <div class="pr-card">
        <div class="pr-card-header">
          <div class="pr-card-title">
            <i class="fa-solid fa-list-ol"></i> Detalle de Ítems del Presupuesto
          </div>
          <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;" id="pr-total-lineas-badge">
            <?= count($lineas) ?> <?= count($lineas) === 1 ? 'línea' : 'líneas' ?>
          </span>
        </div>

        <?php if (empty($lineas)): ?>
          <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
            <i class="fa-solid fa-receipt" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 0.75rem;"></i>
            <p style="font-weight: 600; margin-bottom: 0.4rem;">El presupuesto aún no tiene líneas agregadas.</p>
            <p style="font-size: 0.82rem;">Usa el panel de la izquierda para cotizar las peticiones del cliente o los formularios de abajo para incorporar repuestos y mano de obra.</p>
          </div>
        <?php else: ?>
          <table class="pr-lineas-table">
            <thead>
              <tr>
                <th style="width: 105px;">Tipo</th>
                <th style="width: 95px;">Origen</th>
                <th>Descripción / Solución</th>
                <th style="text-align: right; width: 60px;">Cant.</th>
                <th style="text-align: right; width: 145px;">Unitario</th>
                <th style="text-align: right; width: 110px;">Subtotal</th>
                <?php if ($pendiente): ?><th style="text-align: center; width: 50px;">Acción</th><?php endif; ?>
              </tr>
            </thead>
            <tbody id="pr-lineas-tbody">
              <?php foreach ($lineas as $l): 
                $org = $l['Origen'] ?? 'Directo';
                $orgData = $origenBadge[$org] ?? $origenBadge['Directo'];
              ?>
                <tr id="pr-row-<?= $l['PresupuestoDetalleID'] ?>">
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
                    <div style="font-weight: 700; color: #f1f5f9;"><?= htmlspecialchars($l['Descripcion']) ?></div>
                    <?php if ($l['PoliticaCobro'] === 'SoloSiNoAprueba'): ?>
                      <span style="font-size: 0.72rem; color: #f59e0b;">(Condicional: solo se cobra si no aprueba reparación)</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: right; font-weight: 600;"><?= number_format($l['Cantidad'], 0) ?></td>
                  <td style="text-align: right; white-space: nowrap;">
                    <?php
                      $listaProd = ($l['TipoLinea'] === 'Repuesto' && !empty($l['ProductoID'])) ? ($preciosLista[(int)$l['ProductoID']] ?? null) : null;
                      $precioEditado = $listaProd !== null && (int)$listaProd !== (int)$l['PrecioUnitario'];
                    ?>
                    <?php if ($pendiente): ?>
                      <div class="pr-price-box">
                        <span style="color: #94a3b8; font-size: 0.8rem;">$</span>
                        <input type="number" value="<?= (int)$l['PrecioUnitario'] ?>" min="1" step="100"
                               title="Ajusta el precio directamente. Se guarda en tiempo real."
                               onchange="actualizarPrecioLinea(this, <?= $l['PresupuestoDetalleID'] ?>)">
                      </div>
                    <?php else: ?>
                      <?= formatCLP($l['PrecioUnitario']) ?>
                    <?php endif; ?>
                    <?php if ($precioEditado): ?>
                      <div style="font-size: 0.68rem; color: #f59e0b;" title="Precio editado a mano (lista <?= formatCLP($listaProd) ?>)">precio editado</div>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: right; font-weight: 700; color: #38bdf8;" class="pr-subtotal-cell">
                    <?= formatCLP($l['Subtotal']) ?>
                  </td>
                  <?php if ($pendiente): ?>
                    <td style="text-align: center;">
                      <button type="button" class="btn" style="background: none; border: none; color: #ef4444; padding: 0.25rem 0.4rem; cursor: pointer;"
                              title="Eliminar línea" onclick="eliminarLineaAjax(this, <?= $l['PresupuestoDetalleID'] ?>)">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>

              <?php if ($combosPresupuesto['descuento'] > 0): ?>
                <tr>
                  <td colspan="5" style="text-align: right; color: var(--text-muted);">Subtotal antes de descuentos:</td>
                  <td style="text-align: right; color: #94a3b8;"><?= formatCLP($subtotalSinCombos) ?></td>
                  <?php if ($pendiente): ?><td></td><?php endif; ?>
                </tr>
                <?php foreach ($combosPresupuesto['promos'] as $pm): ?>
                  <tr>
                    <td colspan="5" style="text-align: right; color: #34d399;">🏷️ Oferta en "<?= htmlspecialchars($pm['nombre']) ?>":</td>
                    <td style="text-align: right; color: #34d399; font-weight: 700;">-<?= formatCLP($pm['monto']) ?></td>
                    <?php if ($pendiente): ?><td></td><?php endif; ?>
                  </tr>
                <?php endforeach; ?>
                <?php foreach ($combosPresupuesto['combos'] as $cb): ?>
                  <tr>
                    <td colspan="5" style="text-align: right; color: #34d399;">🏷️ Descuento combo "<?= htmlspecialchars($cb['nombre']) ?>":</td>
                    <td style="text-align: right; color: #34d399; font-weight: 700;">-<?= formatCLP($cb['monto']) ?></td>
                    <?php if ($pendiente): ?><td></td><?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>

              <tr class="pr-total-row">
                <td colspan="5" style="text-align: right; font-size: 1.05rem;">TOTAL PRESUPUESTO:</td>
                <td style="text-align: right; font-size: 1.3rem; color: #fbbf24; font-weight: 800;" class="pr-total-val"><?= formatCLP($total) ?></td>
                <?php if ($pendiente): ?><td></td><?php endif; ?>
              </tr>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <?php if ($pendiente && $presupuesto): ?>
        <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="margin: 0.65rem 0 1.25rem; font-size: 0.85rem;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="toggle_combos">
          <label style="display: inline-flex; align-items: center; gap: 0.55rem; cursor: pointer; color: #cbd5e1;">
            <input type="checkbox" name="aplica_combos" value="1" <?= $aplicaCombos ? 'checked' : '' ?> onchange="this.form.submit()" style="accent-color: #38bdf8;">
            Aplicar descuentos de combos y promociones a este presupuesto
          </label>
          <div style="color: var(--text-muted); font-size: 0.74rem; margin-left: 1.6rem;">
            Si se desmarca, el presupuesto y el cobro en Caja se calcularán a precio unitario estándar.
          </div>
        </form>
      <?php endif; ?>

      <!-- FORMULARIOS DE AGREGAR LÍNEAS (SOLO EN MODO PENDIENTE) -->
      <?php if ($pendiente): ?>
        <div class="pr-card no-print" style="margin-top: 1.25rem;" id="formAgregarSeccion">
          <div class="pr-tabs">
            <button type="button" class="pr-tab-btn active" onclick="switchPrTab('repuesto', this)"><i class="fa-solid fa-box"></i> Agregar Repuesto</button>
            <button type="button" class="pr-tab-btn" onclick="switchPrTab('servicio', this)"><i class="fa-solid fa-wrench"></i> Servicio / Mano de Obra</button>
            <button type="button" class="pr-tab-btn" onclick="switchPrTab('libre', this)"><i class="fa-solid fa-pen-to-square"></i> Línea Libre / Solución</button>
          </div>

          <!-- TAB 1: AGREGAR REPUESTO DEL INVENTARIO -->
          <div id="tab-pr-repuesto">

            <?php if (!empty($repuestosAnterioresUnicos)): ?>
              <!-- SECCIÓN 1: USADO ANTERIORMENTE EN ESTE VEHÍCULO -->
              <details class="pr-collapsible" style="background: rgba(16, 185, 129, 0.04); border: 1px solid rgba(16, 185, 129, 0.22); border-radius: 8px; margin-bottom: 0.85rem;">
                <summary style="padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
                  <div style="font-size: 0.86rem; font-weight: 700; color: #34d399; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Repuestos usados anteriormente en este vehículo (<?= htmlspecialchars($ot['Patente']) ?>)
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
                          <div style="font-size: 0.84rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff;">
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
              <!-- SECCIÓN 2: REPUESTOS COMPATIBLES CON EL MODELO -->
              <details class="pr-collapsible" style="background: rgba(59, 130, 246, 0.04); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; margin-bottom: 0.85rem;">
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
                          <div style="font-size: 0.84rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff;">
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
            <div style="border-top: 1px dashed rgba(255, 255, 255, 0.08); padding-top: 0.95rem; margin-top: 0.5rem;">
              <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.6rem;">
                <i class="fa-solid fa-magnifying-glass"></i> O buscar en todo el inventario:
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

            <!-- SECCIÓN 4: GUÍA Y CATÁLOGOS EXTERNOS -->
            <div style="margin-top: 1.25rem; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; padding: 0.75rem 0.9rem;">
              <details style="cursor: pointer;">
                <summary style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; outline: none; display: flex; align-items: center; gap: 0.4rem;">
                  <i class="fa-solid fa-circle-question" style="color: #38bdf8;"></i> ¿Cómo saber la referencia o filtro exacto para un auto nuevo?
                </summary>
                <div style="margin-top: 0.65rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.55;">
                  <p style="margin-bottom: 0.4rem;">
                    Si este modelo ingresa por <strong>primera vez</strong> al taller, puedes consultar los catálogos rápidos oficiales:
                  </p>
                  <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 0.65rem;">
                    <a href="https://catalog.mann-filter.com" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="font-size: 0.74rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i> Catálogo Mann-Filter
                    </a>
                    <a href="https://www.shell.cl/conductores/aceites-y-lubricantes-para-el-motor/lubematch.html" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="font-size: 0.74rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                      <i class="fa-solid fa-arrow-up-right-from-square"></i> Guía Shell LubeMatch
                    </a>
                    <a href="productos.php" target="_blank" class="btn btn-secondary" style="font-size: 0.74rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                      <i class="fa-solid fa-plus"></i> Crear Repuesto en Inventario
                    </a>
                  </div>
                  <p style="margin-bottom: 0; color: #34d399;">
                    <i class="fa-solid fa-brain"></i> <strong>Memorización inteligente:</strong> Una vez aprobado el presupuesto, el sistema recordará este repuesto para la patente <strong><?= htmlspecialchars($ot['Patente']) ?></strong> y el modelo <strong><?= htmlspecialchars($ot['Marca'] . ' ' . $ot['Modelo']) ?></strong> para futuras visitas.
                  </p>
                </div>
              </details>
            </div>

          </div>

          <!-- TAB 2: AGREGAR SERVICIO / MANO DE OBRA -->
          <div id="tab-pr-servicio" style="display: none;">

            <?php if (!empty($serviciosAnterioresUnicos)): ?>
              <!-- HISTORIAL DE SERVICIOS PREVIOS EN ESTE VEHÍCULO -->
              <details class="pr-collapsible" style="background: rgba(245, 158, 11, 0.04); border: 1px solid rgba(245, 158, 11, 0.22); border-radius: 8px; margin-bottom: 0.85rem;">
                <summary style="padding: 0.75rem 1rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none;">
                  <div style="font-size: 0.86rem; font-weight: 700; color: #fbbf24; display: flex; align-items: center; gap: 0.45rem;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Servicios realizados anteriormente en este vehículo (<?= htmlspecialchars($ot['Patente']) ?>)
                    <span class="badge badge-warning" style="font-size: 0.68rem;"><?= count($serviciosAnterioresUnicos) ?> <?= count($serviciosAnterioresUnicos) === 1 ? 'servicio' : 'servicios' ?></span>
                  </div>
                  <div style="font-size: 0.74rem; color: #fbbf24; font-weight: 600; display: flex; align-items: center; gap: 0.35rem;">
                    <span>Ver historial</span>
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
                          <div style="font-size: 0.84rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #fff;">
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

      <!-- 4. CAJA DE DECISIÓN DEL CLIENTE -->
      <?php if (!empty($lineas)): ?>
        <div class="pr-card no-print" style="margin-top: 1.25rem;">
          <div class="pr-card-header">
            <div class="pr-card-title">
              <i class="fa-solid fa-signature"></i> Decisión del Cliente
            </div>
            <?php if ($decidido): ?>
              <span class="badge <?= $presupuesto['DecisionCliente'] === 'Rechazado' ? 'badge-danger' : 'badge-success' ?>" style="font-size: 0.8rem; padding: 0.35rem 0.7rem;">
                <?= htmlspecialchars($presupuesto['DecisionCliente']) ?> el <?= date('d/m/Y H:i', strtotime($presupuesto['FechaDecision'])) ?>
              </span>
            <?php endif; ?>
          </div>

          <?php if ($pendiente): ?>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
              Una vez presentado el presupuesto a <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong>, registra aquí su resolución:
            </p>

            <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="decidir">
              
              <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                <button type="submit" name="decision" value="AprobadoTotal" class="btn btn-success" style="padding: 0.7rem 1.35rem; font-weight: 800; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 8px;">
                  <i class="fa-solid fa-check-double"></i> Cliente Aprobó Todo (<span class="pr-total-val"><?= formatCLP($total) ?></span>)
                </button>
                <button type="submit" name="decision" value="Rechazado" class="btn btn-secondary" style="padding: 0.7rem 1.2rem; font-weight: 700; font-size: 0.9rem; border-radius: 8px;" onclick="return confirm('¿Seguro que el cliente rechazó el presupuesto?');">
                  <i class="fa-solid fa-xmark"></i> Cliente Rechazó
                </button>

                <div style="margin-left: auto; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                  <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background: #25d366; color: #ffffff; padding: 0.65rem 1.15rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 8px;" title="Compartir cotización por WhatsApp al cliente">
                    <i class="fa-brands fa-whatsapp" style="font-size: 1.1rem;"></i> Enviar por WhatsApp
                  </a>
                  <a href="comprobante_presupuesto.php?id=<?= $otId ?>" target="_blank" class="btn btn-secondary" style="padding: 0.65rem 1.1rem; border-radius: 8px;">
                    <i class="fa-solid fa-file-pdf"></i> Ver Cotización (PDF)
                  </a>
                </div>
              </div>
            </form>
          <?php else: ?>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
              <div>
                <strong style="color: #fff; font-size: 1rem;">Estado:</strong> Presupuesto <?= htmlspecialchars($presupuesto['DecisionCliente']) ?>.<br>
                <span style="font-size: 0.88rem; color: var(--text-muted);">Monto aceptado: <strong style="color: #38bdf8; font-size: 1.05rem;"><?= formatCLP($totalAprobado) ?></strong></span>
              </div>
              <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
                <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background: #25d366; color: #ffffff; padding: 0.65rem 1.15rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem; border-radius: 8px;" title="Reenviar por WhatsApp">
                  <i class="fa-brands fa-whatsapp" style="font-size: 1.1rem;"></i> Reenviar por WhatsApp
                </a>
                <a href="comprobante_presupuesto.php?id=<?= $otId ?>" target="_blank" class="btn btn-secondary" style="padding: 0.65rem 1.1rem; border-radius: 8px;">
                  <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <?php if ($presupuesto['DecisionCliente'] !== 'Rechazado'): ?>
                  <a href="ejecucion.php?id=<?= $otId ?>" class="btn btn-primary" style="padding: 0.65rem 1.35rem; font-weight: 800; border-radius: 8px;">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Ir a Ejecución / Taller
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

<!-- ======================================================== -->
<!-- 5. SCRIPTS: SCROLL RESTORATION, AJAX EDIT & TABS         -->
<!-- ======================================================== -->
<script>
  // Control de scroll: Evita que la pantalla salte arriba al enviar formularios o recargar
  const PR_SCROLL_KEY = 'pr_scroll_pos_<?= (int)$otId ?>';

  // Guardar posición actual
  function guardarPosicionScroll() {
    try {
      sessionStorage.setItem(PR_SCROLL_KEY, window.scrollY);
    } catch (e) {}
  }

  window.addEventListener('beforeunload', guardarPosicionScroll);
  document.querySelectorAll('form').forEach(f => {
    f.addEventListener('submit', guardarPosicionScroll);
  });

  // Restaurar posición inmediatamente
  (function restaurarScroll() {
    try {
      const saved = sessionStorage.getItem(PR_SCROLL_KEY);
      if (saved !== null) {
        window.scrollTo({ top: parseInt(saved, 10), behavior: 'instant' });
      }
    } catch (e) {}
  })();

  document.addEventListener('DOMContentLoaded', () => {
    try {
      const saved = sessionStorage.getItem(PR_SCROLL_KEY);
      if (saved !== null) {
        window.scrollTo({ top: parseInt(saved, 10), behavior: 'instant' });
        setTimeout(() => {
          window.scrollTo({ top: parseInt(saved, 10), behavior: 'instant' });
          sessionStorage.removeItem(PR_SCROLL_KEY);
        }, 50);
      }
    } catch (e) {}
  });

  // Pestañas del formulario de agregar líneas
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

  // Actualización de precio en tiempo real vía AJAX (sin recarga ni salto de página)
  async function actualizarPrecioLinea(input, lineaId) {
    const precio = parseInt(input.value, 10);
    if (isNaN(precio) || precio <= 0) return;

    const box = input.closest('.pr-price-box');
    const prevBorder = box.style.borderColor;
    box.style.borderColor = '#38bdf8';

    try {
      const formData = new FormData();
      formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
      formData.append('action', 'editar_precio');
      formData.append('linea_id', lineaId);
      formData.append('precio', precio);
      formData.append('ajax', '1');

      const res = await fetch('presupuesto.php?id=<?= $otId ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (data.ok) {
        // Actualizar subtotal de la fila
        const row = document.getElementById('pr-row-' + lineaId);
        if (row) {
          const subCell = row.querySelector('.pr-subtotal-cell');
          if (subCell) subCell.textContent = data.subtotal_fmt;
        }
        // Actualizar total general en pantalla
        document.querySelectorAll('.pr-total-val').forEach(el => {
          el.textContent = data.total_fmt;
        });

        box.style.borderColor = '#10b981';
        setTimeout(() => { box.style.borderColor = prevBorder || ''; }, 1500);
      } else {
        box.style.borderColor = '#ef4444';
        alert(data.error || 'No se pudo actualizar el precio.');
      }
    } catch (e) {
      box.style.borderColor = '#ef4444';
    }
  }

  // Eliminación de línea vía AJAX (fades out suavemente sin recarga ni salto)
  async function eliminarLineaAjax(btn, lineaId) {
    if (!confirm('¿Eliminar esta línea del presupuesto?')) return;

    const row = document.getElementById('pr-row-' + lineaId);
    if (row) {
      row.style.opacity = '0.35';
      row.style.pointerEvents = 'none';
    }

    try {
      const formData = new FormData();
      formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
      formData.append('action', 'eliminar_linea');
      formData.append('linea_id', lineaId);
      formData.append('ajax', '1');

      const res = await fetch('presupuesto.php?id=<?= $otId ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (data.ok) {
        if (row) row.remove();
        document.querySelectorAll('.pr-total-val').forEach(el => {
          el.textContent = data.total_fmt;
        });
        const badge = document.getElementById('pr-total-lineas-badge');
        if (badge) {
          badge.textContent = data.count + (data.count === 1 ? ' línea' : ' líneas');
        }
        if (data.count === 0) {
          guardarPosicionScroll();
          window.location.reload();
        }
      } else {
        if (row) {
          row.style.opacity = '1';
          row.style.pointerEvents = 'auto';
        }
        alert(data.error || 'No se pudo eliminar la línea.');
      }
    } catch (e) {
      if (row) {
        row.style.opacity = '1';
        row.style.pointerEvents = 'auto';
      }
      alert('Error de conexión al eliminar la línea.');
    }
  }
</script>

<!-- Modal para ingresar o actualizar teléfono del cliente -->
<div id="modalTelCliente" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(6px); z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 14px; width: 420px; padding: 1.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem; color: #fff;">
      <i class="fa-brands fa-whatsapp" style="color: #25d366; font-size: 1.3rem;"></i> Teléfono del Cliente
    </h3>
    <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 1.25rem;">
      Ingresa el número de <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong> para que el botón de WhatsApp abra el chat directo:
    </p>
    <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="actualizar_telefono_cliente">
      <div style="margin-bottom: 1.25rem;">
        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">NÚMERO DE TELÉFONO / CELULAR:</label>
        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($ot['ClienteTelefono'] ?? '') ?>" placeholder="Ej: +56 9 1234 5678 o 912345678" required autofocus style="font-size: 1.05rem; font-weight: 700;">
        <span style="font-size: 0.74rem; color: var(--text-muted); display: block; margin-top: 0.35rem;">
          💡 Puede ser con o sin +56 (ej: <code>912345678</code>). Se normaliza automáticamente para WhatsApp.
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
