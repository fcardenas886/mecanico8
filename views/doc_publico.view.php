<?php
$folio = function_exists('formatFolioOT') ? formatFolioOT($ot['OrdenTrabajoID']) : 'OT-' . $ot['OrdenTrabajoID'];
$fechaDoc = !empty($presupuesto['FechaCreacion']) ? date('d/m/Y H:i', strtotime($presupuesto['FechaCreacion'])) : date('d/m/Y H:i', strtotime($ot['FechaIngreso']));
$tituloDoc = match($tipoDoc) {
    'presupuesto' => 'PRESUPUESTO DE SERVICIO TÉCNICO',
    'ingreso' => 'ORDEN DE INGRESO Y CUSTODIA',
    'entrega' => 'COMPROBANTE DE ENTREGA Y SERVICIO',
    default => 'DOCUMENTO OFICIAL DE SERVICIO'
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($tituloDoc) ?> — <?= htmlspecialchars($folio) ?> — <?= htmlspecialchars($nombreTaller) ?></title>
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <style>
    :root {
      --bg: #0b1329;
      --card-bg: #ffffff;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
      --primary: #2563eb;
      --success: #16a34a;
      --warning: #d97706;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg);
      color: var(--text-dark);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      min-height: 100vh;
      padding-bottom: 3rem;
    }

    /* Barra Superior de Control (No imprimible) */
    .action-bar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid #334155;
      padding: 0.75rem 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }
    .token-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(56, 189, 248, 0.15);
      border: 1px solid rgba(56, 189, 248, 0.35);
      color: #38bdf8;
      font-size: 0.8rem;
      font-weight: 700;
      padding: 0.35rem 0.75rem;
      border-radius: 9999px;
      font-family: monospace;
      letter-spacing: 0.05em;
    }
    .action-btns {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.85rem;
      font-weight: 700;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
    }
    .btn-wa {
      background: #25d366;
      color: #ffffff;
    }
    .btn-wa:hover {
      background: #20ba5a;
    }
    .btn-print {
      background: #3b82f6;
      color: #ffffff;
    }
    .btn-print:hover {
      background: #2563eb;
    }

    /* Hoja del Documento */
    .doc-container {
      max-width: 860px;
      margin: 1.5rem auto;
      background: var(--card-bg);
      border-radius: 12px;
      padding: 2.5rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    }

    /* Membrete */
    .doc-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 1.5rem;
      margin-bottom: 1.5rem;
      gap: 1.5rem;
    }
    .doc-taller h1 {
      font-size: 1.5rem;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 0.2rem;
    }
    .doc-taller p {
      font-size: 0.82rem;
      color: var(--text-muted);
      line-height: 1.4;
    }
    .doc-meta {
      text-align: right;
    }
    .doc-type {
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--primary);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .doc-folio {
      font-size: 1.6rem;
      font-weight: 900;
      color: #0f172a;
      line-height: 1.2;
    }
    .doc-date {
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-top: 0.2rem;
    }

    /* Cuadrícula de Datos: Cliente y Auto */
    .grid-info {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 1.25rem;
      margin-bottom: 1.5rem;
    }
    .info-col h3 {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      letter-spacing: 0.05em;
      margin-bottom: 0.5rem;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 0.25rem;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.86rem;
      margin-bottom: 0.35rem;
    }
    .info-lbl {
      color: var(--text-muted);
    }
    .info-val {
      font-weight: 600;
      color: #0f172a;
      text-align: right;
    }
    .plate-badge {
      display: inline-block;
      background: #fef08a;
      border: 1px solid #ca8a04;
      color: #713f12;
      font-family: monospace;
      font-weight: 800;
      padding: 0.1rem 0.4rem;
      border-radius: 4px;
    }

    /* Tablas de Presupuesto */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1.5rem;
    }
    .items-table th {
      background: #f1f5f9;
      color: #334155;
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      text-align: left;
      padding: 0.65rem 0.85rem;
      border-bottom: 2px solid var(--border-color);
    }
    .items-table td {
      padding: 0.65rem 0.85rem;
      border-bottom: 1px solid var(--border-color);
      font-size: 0.86rem;
    }
    .items-table th.num, .items-table td.num {
      text-align: right;
    }
    .category-row td {
      background: #f8fafc;
      font-weight: 700;
      font-size: 0.82rem;
      color: #1e293b;
      padding: 0.5rem 0.85rem;
    }

    /* Caja de Totales */
    .totals-wrap {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-top: 1.5rem;
      padding-top: 1.25rem;
      border-top: 2px solid var(--border-color);
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .totals-terms {
      flex: 1;
      min-width: 250px;
      font-size: 0.8rem;
      color: var(--text-muted);
      line-height: 1.5;
    }
    .totals-box {
      width: 280px;
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 1rem;
    }
    .total-line {
      display: flex;
      justify-content: space-between;
      font-size: 0.88rem;
      margin-bottom: 0.4rem;
    }
    .total-grand {
      display: flex;
      justify-content: space-between;
      font-size: 1.25rem;
      font-weight: 800;
      color: #0f172a;
      border-top: 2px solid #cbd5e1;
      padding-top: 0.65rem;
      margin-top: 0.5rem;
    }

    /* Checklist de Ingreso */
    .checklist-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 0.65rem;
      margin-bottom: 1.5rem;
    }
    .chk-item {
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      padding: 0.5rem 0.75rem;
      font-size: 0.82rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .chk-ok { color: var(--success); font-weight: 700; }
    .chk-no { color: var(--warning); font-weight: 700; }

    /* Pie y Verificación */
    .doc-footer {
      margin-top: 2.5rem;
      border-top: 1px solid var(--border-color);
      padding-top: 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.75rem;
      color: var(--text-muted);
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .security-stamp {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      color: var(--success);
      font-weight: 600;
    }

    /* Impresión limpia */
    @media print {
      body {
        background: #ffffff !important;
        padding: 0 !important;
      }
      .action-bar, .no-print {
        display: none !important;
      }
      .doc-container {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
      }
      .grid-info, .totals-box, .items-table th {
        background: #f8fafc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }

    @media (max-width: 640px) {
      .doc-container {
        padding: 1.25rem;
        margin: 0.5rem;
      }
      .grid-info {
        grid-template-columns: 1fr;
      }
      .doc-header {
        flex-direction: column;
        gap: 0.75rem;
      }
      .doc-meta {
        text-align: left;
      }
    }
  </style>
</head>
<body>

  <!-- Barra de Acciones y Token -->
  <header class="action-bar no-print">
    <div style="display: flex; align-items: center; gap: 0.75rem;">
      <span class="token-badge">
        <i class="fa-solid fa-shield-halved"></i> TOKEN: <?= htmlspecialchars($token) ?>
      </span>
      <span style="color: #94a3b8; font-size: 0.8rem; display: none; @media(min-width:768px){display:inline;}">
        Documento Verificado
      </span>
    </div>
    <div class="action-btns">
      <?php if (!empty($urlWaRespuesta)): ?>
        <a href="<?= htmlspecialchars($urlWaRespuesta) ?>" target="_blank" class="btn btn-wa" title="Responder por WhatsApp al taller">
          <i class="fa-brands fa-whatsapp"></i> Aprobar por WhatsApp
        </a>
      <?php endif; ?>
      <button onclick="window.print()" class="btn btn-print" title="Descargar o imprimir este documento">
        <i class="fa-solid fa-file-pdf"></i> Imprimir / Guardar PDF
      </button>
    </div>
  </header>

  <!-- Contenedor Principal del Documento -->
  <main class="doc-container">

    <!-- Encabezado y Membrete -->
    <div class="doc-header">
      <div class="doc-taller">
        <h1><?= htmlspecialchars($nombreTaller) ?></h1>
        <p><?= htmlspecialchars($giroTaller) ?></p>
        <p>RUT: <?= htmlspecialchars($rutTaller) ?> <?= !empty($dirTaller) ? ' · ' . htmlspecialchars($dirTaller) : '' ?></p>
        <?php if (!empty($telTaller)): ?>
          <p><i class="fa-solid fa-phone" style="font-size:0.75rem;"></i> Teléfono: <?= htmlspecialchars($telTaller) ?></p>
        <?php endif; ?>
      </div>
      <div class="doc-meta">
        <div class="doc-type"><?= htmlspecialchars($tituloDoc) ?></div>
        <div class="doc-folio"><?= htmlspecialchars($folio) ?></div>
        <div class="doc-date"><i class="fa-regular fa-calendar"></i> Fecha: <?= htmlspecialchars($fechaDoc) ?></div>
      </div>
    </div>

    <!-- Información del Cliente y del Vehículo -->
    <div class="grid-info">
      <div class="info-col">
        <h3>Datos del Cliente</h3>
        <div class="info-row">
          <span class="info-lbl">Nombre:</span>
          <span class="info-val"><?= htmlspecialchars($ot['ClienteNombre'] ?? 'Cliente General') ?></span>
        </div>
        <?php if (!empty($ot['RutCuerpo'])): ?>
          <div class="info-row">
            <span class="info-lbl">RUT:</span>
            <span class="info-val"><?= htmlspecialchars($ot['RutCuerpo'] . '-' . $ot['RutDv']) ?></span>
          </div>
        <?php endif; ?>
        <?php if (!empty($ot['ClienteTelefono'])): ?>
          <div class="info-row">
            <span class="info-lbl">Teléfono:</span>
            <span class="info-val"><?= htmlspecialchars($ot['ClienteTelefono']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <div class="info-col">
        <h3>Datos del Vehículo</h3>
        <div class="info-row">
          <span class="info-lbl">Patente:</span>
          <span class="info-val"><span class="plate-badge"><?= htmlspecialchars($ot['Patente'] ?? '') ?></span></span>
        </div>
        <div class="info-row">
          <span class="info-lbl">Vehículo:</span>
          <span class="info-val"><?= htmlspecialchars(($ot['Marca'] ?? '') . ' ' . ($ot['Modelo'] ?? '') . ' ' . ($ot['Anio'] ?? '')) ?></span>
        </div>
        <div class="info-row">
          <span class="info-lbl">Kilometraje:</span>
          <span class="info-val"><?= !empty($ot['KilometrajeUltimo']) ? number_format((int)$ot['KilometrajeUltimo'], 0, ',', '.') . ' km' : (!empty($ot['KilometrajeIngreso']) ? number_format((int)$ot['KilometrajeIngreso'], 0, ',', '.') . ' km' : 'N/R') ?></span>
        </div>
        <?php if (!empty($ot['Color'])): ?>
          <div class="info-row">
            <span class="info-lbl">Color:</span>
            <span class="info-val"><?= htmlspecialchars($ot['Color']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- CASO 1: PRESUPUESTO                       -->
    <!-- ========================================== -->
    <?php if ($tipoDoc === 'presupuesto'): ?>
      
      <table class="items-table">
        <thead>
          <tr>
            <th>Descripción del Trabajo / Repuesto</th>
            <th class="num" style="width: 80px;">Cant.</th>
            <th class="num" style="width: 120px;">Precio Unit.</th>
            <th class="num" style="width: 130px;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <!-- Mano de obra -->
          <?php if (!empty($lineasManoObra)): ?>
            <tr class="category-row">
              <td colspan="4"><i class="fa-solid fa-wrench"></i> Mano de Obra y Servicios Técnicos</td>
            </tr>
            <?php foreach ($lineasManoObra as $l): ?>
              <tr>
                <td><?= htmlspecialchars($l['Descripcion']) ?></td>
                <td class="num"><?= (float)$l['Cantidad'] ?></td>
                <td class="num"><?= formatCLP($l['PrecioUnitario']) ?></td>
                <td class="num" style="font-weight: 700;"><?= formatCLP($l['Subtotal']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- Repuestos -->
          <?php if (!empty($lineasRepuesto)): ?>
            <tr class="category-row">
              <td colspan="4"><i class="fa-solid fa-box"></i> Repuestos e Insumos Automotrices</td>
            </tr>
            <?php foreach ($lineasRepuesto as $l): ?>
              <tr>
                <td><?= htmlspecialchars($l['Descripcion']) ?></td>
                <td class="num"><?= (float)$l['Cantidad'] ?></td>
                <td class="num"><?= formatCLP($l['PrecioUnitario']) ?></td>
                <td class="num" style="font-weight: 700;"><?= formatCLP($l['Subtotal']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- Trabajos de Terceros -->
          <?php if (!empty($lineasTerceros)): ?>
            <tr class="category-row">
              <td colspan="4"><i class="fa-solid fa-handshake"></i> Servicios Externos / Especializados</td>
            </tr>
            <?php foreach ($lineasTerceros as $l): ?>
              <tr>
                <td><?= htmlspecialchars($l['Descripcion']) ?></td>
                <td class="num"><?= (float)$l['Cantidad'] ?></td>
                <td class="num"><?= formatCLP($l['PrecioUnitario']) ?></td>
                <td class="num" style="font-weight: 700;"><?= formatCLP($l['Subtotal']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Totales y Condiciones -->
      <div class="totals-wrap">
        <div class="totals-terms">
          <?php if (!empty($presupuesto['TiempoEntrega'])): ?>
            <p><strong><i class="fa-regular fa-clock"></i> Tiempo estimado de entrega:</strong> <?= htmlspecialchars($presupuesto['TiempoEntrega']) ?> (a contar de recepción de repuestos y aprobación formal).</p>
          <?php endif; ?>
          <p style="margin-top: 0.5rem;"><strong>Condiciones:</strong> Cotización válida por 10 días hábiles. Los repuestos e insumos están sujetos a disponibilidad de proveedores al momento de la confirmación formal del servicio.</p>
        </div>

        <div class="totals-box">
          <div class="total-line">
            <span class="info-lbl">Total Presupuestado:</span>
            <span style="font-weight: 700;"><?= formatCLP($totalPresupuesto) ?></span>
          </div>
          <div class="total-grand">
            <span>TOTAL:</span>
            <span style="color: var(--primary);"><?= formatCLP($totalPresupuesto) ?></span>
          </div>
        </div>
      </div>

    <!-- ========================================== -->
    <!-- CASO 2: COMPROBANTE DE INGRESO / CUSTODIA  -->
    <!-- ========================================== -->
    <?php elseif ($tipoDoc === 'ingreso'): ?>
      
      <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; color: #1e293b;">
          <i class="fa-solid fa-clipboard-check"></i> Motivo de Ingreso y Condiciones Declaradas
        </h3>
        <p style="background: #f8fafc; border: 1px solid var(--border-color); padding: 0.85rem; border-radius: 8px; font-size: 0.88rem; color: #334155; line-height: 1.5;">
          <?= !empty($ot['MotivoIngreso']) ? htmlspecialchars($ot['MotivoIngreso']) : 'Revisión técnica general y mantenimiento.' ?>
        </p>
      </div>

      <?php if (!empty($checklist)): ?>
        <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem; color: #1e293b;">
          <i class="fa-solid fa-list-check"></i> Inspección Visual de Recepción
        </h3>
        <div class="checklist-grid">
          <?php foreach ($checklist as $chk): 
            $esOk = in_array(mb_strtolower($chk['Valor']), ['si', 'bueno', 'correcto', 'ok', '1'], true);
          ?>
            <div class="chk-item">
              <span><?= htmlspecialchars($chk['Nombre']) ?></span>
              <span class="<?= $esOk ? 'chk-ok' : 'chk-no' ?>">
                <?= $esOk ? '<i class="fa-solid fa-check"></i> Sí' : '<i class="fa-solid fa-xmark"></i> ' . htmlspecialchars($chk['Valor']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($observacionesEstacion)): ?>
        <div style="margin-bottom: 1.5rem;">
          <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; color: #1e293b;">
            <i class="fa-solid fa-eye"></i> Observaciones de Estación de Servicio
          </h3>
          <p style="background: #f8fafc; border: 1px solid var(--border-color); padding: 0.85rem; border-radius: 8px; font-size: 0.85rem; color: #475569;">
            <?= nl2br(htmlspecialchars($observacionesEstacion)) ?>
          </p>
        </div>
      <?php endif; ?>

      <div style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; border-top: 1px solid var(--border-color); padding-top: 1rem;">
        <strong>Constancia de Custodia:</strong> Se deja constancia de la recepción del vehículo en las condiciones detalladas en este informe. El taller cuenta con resguardo de seguridad y protocolos técnicos para la inspección y diagnóstico preliminar.
      </div>

    <!-- ========================================== -->
    <!-- CASO 3: ENTREGA Y LIQUIDACIÓN             -->
    <!-- ========================================== -->
    <?php elseif ($tipoDoc === 'entrega'): ?>
      
      <div style="background: rgba(22, 163, 74, 0.08); border: 1px solid rgba(22, 163, 74, 0.25); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
        <i class="fa-solid fa-circle-check" style="font-size: 2rem; color: var(--success);"></i>
        <div>
          <h3 style="font-size: 1.1rem; font-weight: 800; color: #15803d; margin-bottom: 0.2rem;">Vehículo Listo y Entregado</h3>
          <p style="font-size: 0.85rem; color: #334155;">Los trabajos autorizados han sido completados e inspeccionados bajo estándares de calidad.</p>
        </div>
      </div>

      <?php if (!empty($venta)): ?>
        <div class="totals-box" style="width: 100%; margin-bottom: 1.5rem;">
          <h3 style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Comprobante de Pago</h3>
          <div class="total-line">
            <span class="info-lbl">Estado de Pago:</span>
            <span style="color: var(--success); font-weight: 700;">Cancelado / Total Pagado</span>
          </div>
          <div class="total-line">
            <span class="info-lbl">Monto Total Cancelado:</span>
            <span style="font-weight: 800; font-size: 1.1rem; color: #0f172a;"><?= formatCLP($venta['MontoTotal']) ?></span>
          </div>
          <?php if (!empty($venta['MetodoPago'])): ?>
            <div class="total-line">
              <span class="info-lbl">Medio de Pago:</span>
              <span style="font-weight: 600;"><?= htmlspecialchars($venta['MetodoPago']) ?></span>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <!-- Pie de Seguridad y Validación -->
    <footer class="doc-footer">
      <div class="security-stamp">
        <i class="fa-solid fa-lock"></i>
        <span>Documento emitido electrónicamente con token de seguridad <strong><?= htmlspecialchars($token) ?></strong></span>
      </div>
      <div>
        <span>Consulta pública oficial · <?= htmlspecialchars($nombreTaller) ?></span>
      </div>
    </footer>

  </main>

</body>
</html>
