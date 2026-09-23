<?php
$folio = formatFolioOT($ot['OrdenTrabajoID']);
$fechaEmision = date('d/m/Y', strtotime($presupuesto['FechaCreacion'] ?? 'now'));
$validezFecha = date('d/m/Y', strtotime(($presupuesto['FechaCreacion'] ?? 'now') . ' +15 days'));

// Preparar mensaje de WhatsApp usando helper centralizado
require_once __DIR__ . '/../includes/whatsapp_helper.php';
$msgWhatsapp = mensajePresupuestoWhatsApp($ot, $presupuesto, (float)$totalPresupuesto, $nombreEmpresa);
$waUrl = generarUrlWhatsapp($ot['ClienteTelefono'] ?? '', $msgWhatsapp);
?>
<style>
  .doc-sheet {
    background: #ffffff;
    color: #111827;
    max-width: 820px;
    margin: 0 auto 2rem auto;
    padding: 2.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  }

  .doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #2563eb;
    padding-bottom: 1.25rem;
    margin-bottom: 1.5rem;
  }

  .doc-company h2 {
    margin: 0 0 0.25rem 0;
    font-size: 1.35rem;
    color: #1e3a8a;
    font-weight: 800;
  }
  .doc-company p {
    margin: 0;
    font-size: 0.82rem;
    color: #4b5563;
    line-height: 1.35;
  }

  .doc-title-box {
    text-align: right;
  }
  .doc-title-box h1 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: #111827;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .doc-title-box .folio {
    font-size: 1.15rem;
    font-weight: 700;
    color: #2563eb;
    margin-top: 0.2rem;
  }

  .doc-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .doc-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 0.85rem 1rem;
    font-size: 0.85rem;
  }
  .doc-box-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.3rem;
  }

  .doc-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
    font-size: 0.85rem;
  }
  .doc-table th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.72rem;
    padding: 0.6rem 0.5rem;
    border-bottom: 2px solid #cbd5e1;
    text-align: left;
  }
  .doc-table td {
    padding: 0.55rem 0.5rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
  }
  .doc-table tr.cat-row td {
    background: #f8fafc;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #2563eb;
    border-top: 1px solid #cbd5e1;
    border-bottom: 1px solid #cbd5e1;
  }

  .doc-totals {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 1.5rem;
  }
  .doc-totals-table {
    width: 280px;
    font-size: 0.88rem;
  }
  .doc-totals-table td {
    padding: 0.35rem 0.5rem;
  }
  .doc-totals-table tr.grand-total td {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1e3a8a;
    border-top: 2px solid #1e3a8a;
    padding-top: 0.5rem;
  }

  .doc-signatures {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-top: 3rem;
    page-break-inside: avoid;
  }
  .doc-sign-line {
    border-top: 1px solid #94a3b8;
    text-align: center;
    padding-top: 0.5rem;
    font-size: 0.8rem;
    color: #475569;
  }

  @media print {
    body * { visibility: hidden !important; }
    .doc-sheet, .doc-sheet * { visibility: visible !important; }
    .doc-sheet {
      position: absolute !important;
      left: 0 !important;
      top: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      box-shadow: none !important;
      padding: 1rem !important;
      margin: 0 !important;
    }
    .no-print { display: none !important; }
  }
</style>

<!-- Barra de Herramientas Superior (Oculta en Impresión) -->
<div class="no-print" style="max-width: 820px; margin: 0 auto 1.25rem auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
  <div style="display: flex; gap: 0.5rem; align-items: center;">
    <a href="presupuesto.php?id=<?= $otId ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
      <i class="fa-solid fa-arrow-left"></i> Volver al Presupuesto
    </a>
    <span class="badge badge-success" style="font-size: 0.85rem;"><?= htmlspecialchars($presupuesto['DecisionCliente']) ?></span>
  </div>

  <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
    <?php if (!empty($waUrl)): ?>
      <a href="<?= $waUrl ?>" target="_blank" class="btn" style="background: #16a34a; color: #ffffff; font-size: 0.85rem;" title="Compartir cotización por WhatsApp">
        <i class="fa-brands fa-whatsapp"></i> Enviar por WhatsApp
      </a>
    <?php endif; ?>
    <button onclick="window.print()" class="btn btn-primary" style="font-size: 0.85rem;">
      <i class="fa-solid fa-print"></i> Imprimir / Guardar en PDF
    </button>
  </div>
</div>

<!-- Hoja de Presupuesto Formal (A4/Carta) -->
<div class="doc-sheet">

  <!-- Encabezado de la Empresa y Título -->
  <div class="doc-header">
    <div class="doc-company">
      <h2><?= htmlspecialchars($nombreEmpresa) ?></h2>
      <p>
        <strong>RUT:</strong> <?= htmlspecialchars($rutEmpresa) ?><br>
        <strong>Dirección:</strong> <?= htmlspecialchars($direccionEmpresa) ?><br>
        <strong>Teléfono:</strong> <?= htmlspecialchars($telefonoEmpresa) ?> • <strong>Email:</strong> <?= htmlspecialchars($emailEmpresa) ?>
      </p>
    </div>

    <div class="doc-title-box">
      <h1>Presupuesto</h1>
      <div class="folio"><?= htmlspecialchars($folio) ?></div>
      <p style="margin: 0.35rem 0 0 0; font-size: 0.78rem; color: #64748b;">
        <strong>Emisión:</strong> <?= $fechaEmision ?><br>
        <strong>Válido hasta:</strong> <?= $validezFecha ?>
      </p>
    </div>
  </div>

  <!-- Cajas de Información: Cliente y Vehículo -->
  <div class="doc-grid">
    <div class="doc-box">
      <div class="doc-box-title">Datos del Cliente</div>
      <div style="font-size: 0.95rem; font-weight: 700; color: #111827; margin-bottom: 0.2rem;">
        <?= htmlspecialchars($ot['ClienteNombre']) ?>
      </div>
      <?php if (!empty($ot['RutCuerpo'])): ?>
        <div><strong>RUT:</strong> <?= number_format($ot['RutCuerpo'], 0, ',', '.') ?>-<?= $ot['RutDv'] ?></div>
      <?php endif; ?>
      <?php if (!empty($ot['ClienteTelefono'])): ?>
        <div><strong>Teléfono:</strong> <?= htmlspecialchars($ot['ClienteTelefono']) ?></div>
      <?php endif; ?>
      <?php if (!empty($ot['ClienteEmail'])): ?>
        <div><strong>Email:</strong> <?= htmlspecialchars($ot['ClienteEmail']) ?></div>
      <?php endif; ?>
    </div>

    <div class="doc-box">
      <div class="doc-box-title">Datos del Vehículo</div>
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.2rem;">
        <span style="font-size: 0.95rem; font-weight: 700; color: #111827;">
          <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> <?= $ot['Anio'] ? '(' . $ot['Anio'] . ')' : '' ?>
        </span>
        <span style="font-size: 1.05rem; font-weight: 800; color: #1e3a8a; border: 1px solid #93c5fd; background: #eff6ff; padding: 1px 6px; border-radius: 4px;">
          <?= htmlspecialchars($ot['Patente']) ?>
        </span>
      </div>
      <?php if ($ot['KilometrajeIngreso']): ?>
        <div><strong>Kilometraje ingreso:</strong> <?= number_format($ot['KilometrajeIngreso'], 0, ',', '.') ?> km</div>
      <?php endif; ?>
      <?php if (!empty($ot['Color'])): ?>
        <div><strong>Color:</strong> <?= htmlspecialchars($ot['Color']) ?></div>
      <?php endif; ?>
      <?php if (!empty($ot['VIN'])): ?>
        <div><strong>VIN / Chasis:</strong> <?= htmlspecialchars($ot['VIN']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Detalle de Repuestos y Mano de Obra -->
  <table class="doc-table">
    <thead>
      <tr>
        <th style="width: 50%;">Descripción del Ítem / Trabajo</th>
        <th style="width: 15%; text-align: center;">Cant.</th>
        <th style="width: 17%; text-align: right;">Precio Unit.</th>
        <th style="width: 18%; text-align: right;">Subtotal</th>
      </tr>
    </thead>
    <tbody>

      <!-- 1. Repuestos -->
      <?php if (!empty($lineasRepuesto)): ?>
        <tr class="cat-row">
          <td colspan="4"><i class="fa-solid fa-box"></i> 1. Repuestos e Insumos Automotrices</td>
        </tr>
        <?php foreach ($lineasRepuesto as $lr): ?>
          <tr>
            <td>
              <?= htmlspecialchars($lr['Descripcion']) ?>
              <?php if (!$lr['Aprobado'] && $presupuesto['DecisionCliente'] !== 'Pendiente'): ?>
                <span style="color: #dc2626; font-size: 0.72rem; font-weight: bold;">(No autorizado)</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;"><?= rtrim(rtrim(number_format((float)$lr['Cantidad'], 3, ',', '.'), '0'), ',') ?></td>
            <td style="text-align: right;">$<?= number_format($lr['PrecioUnitario'], 0, ',', '.') ?></td>
            <td style="text-align: right; font-weight: 600;">$<?= number_format($lr['Subtotal'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- 2. Mano de Obra -->
      <?php if (!empty($lineasManoObra)): ?>
        <tr class="cat-row">
          <td colspan="4"><i class="fa-solid fa-wrench"></i> 2. Servicios de Taller y Mano de Obra</td>
        </tr>
        <?php foreach ($lineasManoObra as $lm): ?>
          <tr>
            <td>
              <?= htmlspecialchars($lm['Descripcion']) ?>
              <?php $lmCond = $lm['PoliticaCobro'] === 'SoloSiNoAprueba'; ?>
              <?php if ($lmCond && !($presupuesto['DecisionCliente'] === 'Rechazado' && $lm['Aprobado'])): ?>
                <span style="color: #059669; font-size: 0.72rem; font-weight: bold;">(Sin costo si aprueba la reparación)</span>
              <?php endif; ?>
              <?php if (!$lmCond && !$lm['Aprobado'] && $presupuesto['DecisionCliente'] !== 'Pendiente'): ?>
                <span style="color: #dc2626; font-size: 0.72rem; font-weight: bold;">(No autorizado)</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;"><?= rtrim(rtrim(number_format((float)$lm['Cantidad'], 3, ',', '.'), '0'), ',') ?></td>
            <td style="text-align: right;">$<?= number_format($lm['PrecioUnitario'], 0, ',', '.') ?></td>
            <td style="text-align: right; font-weight: 600;"><?= ($lmCond && !($presupuesto['DecisionCliente'] === 'Rechazado' && $lm['Aprobado'])) ? '<span style="text-decoration: line-through; color:#94a3b8;">$' . number_format($lm['Subtotal'], 0, ',', '.') . '</span>' : '$' . number_format($lm['Subtotal'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- 3. Terceros -->
      <?php if (!empty($lineasTerceros)): ?>
        <tr class="cat-row">
          <td colspan="4"><i class="fa-solid fa-gear"></i> 3. Trabajos Externos / Tornería</td>
        </tr>
        <?php foreach ($lineasTerceros as $lt): ?>
          <tr>
            <td>
              <?= htmlspecialchars($lt['Descripcion']) ?>
              <?php if (!$lt['Aprobado'] && $presupuesto['DecisionCliente'] !== 'Pendiente'): ?>
                <span style="color: #dc2626; font-size: 0.72rem; font-weight: bold;">(No autorizado)</span>
              <?php endif; ?>
            </td>
            <td style="text-align: center;"><?= rtrim(rtrim(number_format((float)$lt['Cantidad'], 3, ',', '.'), '0'), ',') ?></td>
            <td style="text-align: right;">$<?= number_format($lt['PrecioUnitario'], 0, ',', '.') ?></td>
            <td style="text-align: right; font-weight: 600;">$<?= number_format($lt['Subtotal'], 0, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>

    </tbody>
  </table>

  <!-- Totales y Condiciones -->
  <div class="doc-totals">
    <table class="doc-totals-table">
      <tr>
        <td style="color: #64748b;">Subtotal Presupuesto:</td>
        <td style="text-align: right; font-weight: 600;">$<?= number_format($totalPresupuesto, 0, ',', '.') ?></td>
      </tr>
      <?php if ($presupuesto['DecisionCliente'] !== 'Pendiente' && (int)$totalAprobado !== (int)$totalPresupuesto): ?>
        <tr>
          <td style="color: #059669; font-weight: 700;">Total a pagar:</td>
          <td style="text-align: right; font-weight: 800; color: #059669;">$<?= number_format($totalAprobado, 0, ',', '.') ?></td>
        </tr>
      <?php else: ?>
        <tr class="grand-total">
          <td>Total a Pagar:</td>
          <td style="text-align: right;">$<?= number_format($totalPresupuesto, 0, ',', '.') ?></td>
        </tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- Plazos y Notas -->
  <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.85rem 1rem; font-size: 0.8rem; color: #475569; margin-bottom: 2rem;">
    <?php if (!empty($presupuesto['TiempoEntrega'])): ?>
      <div style="margin-bottom: 0.4rem;">
        <strong>Tiempo estimado de entrega:</strong> <?= htmlspecialchars($presupuesto['TiempoEntrega']) ?> (a contar de la recepción de repuestos y aprobación formal).
      </div>
    <?php endif; ?>
    <div>
      <strong>Condiciones Generales:</strong> Presupuesto válido por 15 días corridos. Precios incluyen IVA. Garantía técnica de 3 meses o 5.000 kilómetros en mano de obra realizada. Repuestos retirados quedarán a disposición del cliente al momento de la entrega.
    </div>
  </div>

  <!-- Firmas de Aceptación -->
  <div class="doc-signatures">
    <div class="doc-sign-line">
      <strong>Asesor Técnico / Taller</strong><br>
      <?= htmlspecialchars($ot['UsuarioNombre']) ?>
    </div>
    <div class="doc-sign-line">
      <strong>Firma de Aprobación del Cliente</strong><br>
      <?= htmlspecialchars($ot['ClienteNombre']) ?>
    </div>
  </div>

</div>
