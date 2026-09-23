<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sticker Parabrisas - <?= htmlspecialchars($ot['Patente']) ?> - <?= htmlspecialchars($nombreEmpresa) ?></title>
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      background: #1e293b;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, monospace;
      color: #000000;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 1.5rem 1rem;
      min-height: 100vh;
    }

    /* Barra de acciones en pantalla */
    .screen-actions {
      display: flex;
      gap: 0.75rem;
      margin-bottom: 1.25rem;
      flex-wrap: wrap;
      justify-content: center;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.6rem 1.1rem;
      font-size: 0.88rem;
      font-weight: 700;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .btn-primary { background: #2563eb; color: #ffffff; }
    .btn-primary:hover { background: #1d4ed8; }
    .btn-success { background: #16a34a; color: #ffffff; }
    .btn-success:hover { background: #15803d; }
    .btn-secondary { background: #475569; color: #ffffff; }
    .btn-secondary:hover { background: #334155; }

    /* Contenedor del sticker térmico (optimizado para ancho 72mm-80mm) */
    .sticker-card {
      background: #ffffff;
      width: 76mm;
      max-width: 100%;
      padding: 5mm 4mm;
      border: 2px dashed #334155;
      border-radius: 6px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.35);
      text-align: center;
    }

    .sticker-header {
      border-bottom: 1.5px solid #000000;
      padding-bottom: 3mm;
      margin-bottom: 3mm;
    }
    .sticker-title {
      font-size: 13pt;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      line-height: 1.15;
    }
    .sticker-subtitle {
      font-size: 7.5pt;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 1mm;
      color: #1f2937;
    }
    .sticker-contact {
      font-size: 7.5pt;
      margin-top: 0.8mm;
      font-weight: 600;
    }

    /* Caja destacada de la Patente */
    .sticker-patente-box {
      border: 2.5px solid #000000;
      background: #f8fafc;
      border-radius: 4px;
      padding: 1.5mm 0;
      margin: 2mm 0;
    }
    .sticker-patente-label {
      font-size: 6.5pt;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: #374151;
    }
    .sticker-patente {
      font-size: 18pt;
      font-weight: 900;
      letter-spacing: 0.08em;
      line-height: 1.1;
      font-family: monospace, -apple-system;
    }
    .sticker-auto {
      font-size: 8pt;
      font-weight: 700;
      text-transform: uppercase;
      margin-bottom: 2mm;
    }

    /* Datos del servicio actual */
    .sticker-row {
      display: flex;
      justify-content: space-between;
      font-size: 8pt;
      padding: 1mm 0;
      border-bottom: 1px dotted #9ca3af;
    }
    .sticker-row-label {
      font-weight: 600;
      color: #374151;
    }
    .sticker-row-val {
      font-weight: 800;
    }

    /* Caja de Próximo Servicio */
    .sticker-next-box {
      background: #000000;
      color: #ffffff;
      border-radius: 4px;
      padding: 2.5mm 2mm;
      margin: 3mm 0 2mm 0;
    }
    .sticker-next-header {
      font-size: 7.5pt;
      font-weight: 900;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 1mm;
    }
    .sticker-next-km {
      font-size: 16pt;
      font-weight: 900;
      letter-spacing: 0.04em;
      line-height: 1.1;
      font-family: monospace, -apple-system;
    }
    .sticker-next-date {
      font-size: 8.5pt;
      font-weight: 800;
      margin-top: 1mm;
      letter-spacing: 0.02em;
    }

    .sticker-notes {
      font-size: 7pt;
      font-weight: 700;
      background: #f1f5f9;
      padding: 1.5mm;
      border-radius: 3px;
      margin-top: 1.5mm;
      line-height: 1.25;
      text-align: left;
    }

    .sticker-footer {
      border-top: 1px solid #000000;
      margin-top: 2.5mm;
      padding-top: 1.5mm;
      font-size: 6.5pt;
      font-weight: 600;
      color: #4b5563;
      text-transform: uppercase;
    }

    /* Reglas de Impresión Térmica */
    @media print {
      body {
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      .screen-actions {
        display: none !important;
      }
      .sticker-card {
        border: 1px dashed #000000 !important;
        box-shadow: none !important;
        width: 100% !important;
        max-width: 80mm !important;
        margin: 0 auto !important;
        page-break-inside: avoid;
      }
      @page {
        size: 80mm auto;
        margin: 2mm;
      }
    }
  </style>
</head>
<body>

  <!-- Barra de acciones en pantalla -->
  <div class="screen-actions">
    <button onclick="window.print()" class="btn btn-primary">
      <i class="fa-solid fa-print"></i> Imprimir Etiqueta
    </button>
    <?php
    require_once __DIR__ . '/../includes/whatsapp_helper.php';
    $msgWA = "Hola " . ($ot['ClienteNombre'] ?? '') . " 👋, te dejamos el comprobante de tu cambio de aceite realizado en " . $nombreEmpresa . ":\n";
    $msgWA .= "🚗 Patente: " . $ot['Patente'] . "\n";
    $msgWA .= "📟 Km Actual: " . number_format($kmRealizado, 0, ',', '.') . " km\n";
    if ($kmProximo) {
        $msgWA .= "🔔 PRÓXIMO CAMBIO SUGERIDO: " . number_format($kmProximo, 0, ',', '.') . " km o fecha " . $fechaProxima . "\n";
    }
    $msgWA .= "🛢️ Lubricante: " . $lubricanteUtilizado . "\n";
    $msgWA .= "¡Gracias por preferir nuestro taller!";
    $waUrl = generarUrlWhatsapp($ot['ClienteTelefono'] ?? '', $msgWA);
    ?>
    <?php if ($waUrl): ?>
      <a href="<?= $waUrl ?>" target="_blank" class="btn btn-success">
        <i class="fa-brands fa-whatsapp"></i> Enviar Recordatorio
      </a>
    <?php endif; ?>
    <button onclick="window.close()" class="btn btn-secondary">
      <i class="fa-solid fa-xmark"></i> Cerrar
    </button>
  </div>

  <!-- Contenedor del Sticker -->
  <div class="sticker-card">
    <div class="sticker-header">
      <div class="sticker-title"><?= htmlspecialchars($nombreEmpresa) ?></div>
      <div class="sticker-subtitle">Control de Servicio y Lubricación</div>
      <?php if ($telefonoEmpresa): ?>
        <div class="sticker-contact">Fono: <?= htmlspecialchars($telefonoEmpresa) ?></div>
      <?php endif; ?>
    </div>

    <div class="sticker-patente-box">
      <div class="sticker-patente-label">Patente Vehículo</div>
      <div class="sticker-patente"><?= htmlspecialchars($ot['Patente']) ?></div>
    </div>

    <div class="sticker-auto">
      <?= htmlspecialchars(trim(($ot['Marca'] ?? '') . ' ' . ($ot['Modelo'] ?? '') . ' ' . ($ot['Anio'] ?? ''))) ?>
    </div>

    <div class="sticker-row">
      <span class="sticker-row-label">Fecha Servicio:</span>
      <span class="sticker-row-val"><?= $fechaRealizado ?></span>
    </div>
    <div class="sticker-row">
      <span class="sticker-row-label">Km Actual:</span>
      <span class="sticker-row-val"><?= number_format($kmRealizado, 0, ',', '.') ?> KM</span>
    </div>

    <!-- Bloque de Próximo Cambio -->
    <div class="sticker-next-box">
      <div class="sticker-next-header">★ PRÓXIMO CAMBIO ★</div>
      <?php if ($kmProximo): ?>
        <div class="sticker-next-km"><?= number_format($kmProximo, 0, ',', '.') ?> KM</div>
      <?php endif; ?>
      <div class="sticker-next-date">O HASTA: <?= $fechaProxima ?></div>
    </div>

    <div class="sticker-notes">
      <strong>LUBRICANTE:</strong> <?= htmlspecialchars($lubricanteUtilizado) ?><br>
      <strong>FILTRO:</strong> Reemplazado en este servicio
    </div>

    <div class="sticker-footer">
      Adherir en parabrisas interior o marco de puerta
    </div>
  </div>

</body>
</html>
