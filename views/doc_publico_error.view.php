<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($errorTitulo ?? 'Acceso No Autorizado') ?> — <?= htmlspecialchars($nombreTaller ?? 'Taller Mecánico') ?></title>
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
  <style>
    :root {
      --bg: #0f172a;
      --card-bg: #1e293b;
      --border: #334155;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --danger: #ef4444;
      --primary: #3b82f6;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .error-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 16px;
      max-width: 480px;
      width: 100%;
      padding: 2.25rem;
      text-align: center;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
    }
    .error-icon {
      width: 64px;
      height: 64px;
      margin: 0 auto 1.25rem auto;
      background: rgba(239, 68, 68, 0.15);
      border: 2px solid rgba(239, 68, 68, 0.4);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--danger);
      font-size: 1.75rem;
    }
    h1 {
      font-size: 1.35rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      color: #fff;
    }
    p {
      color: var(--text-muted);
      font-size: 0.95rem;
      line-height: 1.5;
      margin-bottom: 1.5rem;
    }
    .footer-taller {
      border-top: 1px solid var(--border);
      padding-top: 1.25rem;
      font-size: 0.85rem;
      color: var(--text-muted);
    }
    .footer-taller strong {
      color: #fff;
    }
    .btn-contact {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #25d366;
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.9rem;
      padding: 0.65rem 1.25rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      transition: opacity 0.2s;
    }
    .btn-contact:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <div class="error-card">
    <div class="error-icon">
      <i class="fa-solid fa-shield-halved"></i>
    </div>
    <h1><?= htmlspecialchars($errorTitulo ?? 'Acceso No Autorizado') ?></h1>
    <p><?= htmlspecialchars($errorMensaje ?? 'El documento solicitado requiere un código de seguridad válido.') ?></p>
    
    <?php if (!empty($telTaller)): ?>
      <a href="https://wa.me/<?= normalizarTelefonoChile($telTaller) ?>" class="btn-contact">
        <i class="fa-brands fa-whatsapp"></i> Contactar al Taller
      </a>
    <?php endif; ?>

    <div class="footer-taller">
      <strong><?= htmlspecialchars($nombreTaller ?? 'Taller Mecánico') ?></strong><br>
      <?= !empty($dirTaller) ? htmlspecialchars($dirTaller) : '' ?>
    </div>
  </div>
</body>
</html>
