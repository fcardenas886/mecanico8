<style>
  .fv-header { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; }
  .fv-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
  .fv-kpi-card { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem; }
  .fv-kpi-title { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.4rem; }
  .fv-kpi-val { font-size: 1.5rem; font-weight: 700; color: #fff; }

  .fv-section { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
  .fv-section-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; }

  .alerta-card { padding: 0.85rem 1rem; border-radius: 8px; border-left: 4px solid; margin-bottom: 0.6rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
  .alerta-Vencido { background: rgba(239, 68, 68, 0.12); border-left-color: #ef4444; }
  .alerta-Proximo { background: rgba(245, 158, 11, 0.12); border-left-color: #f59e0b; }
  .alerta-Vigente { background: rgba(16, 185, 129, 0.12); border-left-color: #10b981; }

  .ot-timeline-item { background: rgba(255,255,255,0.02); border: 1px solid var(--border-dark); border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 0.85rem; }
  .ot-timeline-item:hover { border-color: #475569; }

  .guide-link-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.85rem; border-radius: 6px; font-size: 0.82rem; font-weight: 600; text-decoration: none; transition: 0.15s; border: 1px solid transparent; }
  .guide-shell { background: rgba(234, 179, 8, 0.15); color: #fde047; border-color: rgba(234, 179, 8, 0.3); }
  .guide-shell:hover { background: rgba(234, 179, 8, 0.25); color: #fff; }
  .guide-liquimoly { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border-color: rgba(59, 130, 246, 0.3); }
  .guide-liquimoly:hover { background: rgba(59, 130, 246, 0.25); color: #fff; }
  .guide-mann { background: rgba(34, 197, 94, 0.15); color: #86efac; border-color: rgba(34, 197, 94, 0.3); }
  .guide-mann:hover { background: rgba(34, 197, 94, 0.25); color: #fff; }
</style>

<!-- Cabecera de la Ficha del Vehículo -->
<div class="fv-header">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
    <div style="display: flex; gap: 1.25rem; align-items: center; flex-wrap: wrap;">
      <code style="font-size: 1.75rem; font-weight: 800; background: #0f172a; color: #38bdf8; padding: 4px 14px; border-radius: 8px; border: 2px solid #38bdf8; letter-spacing: 0.06em;">
        <?= htmlspecialchars($vehiculo['Patente']) ?>
      </code>
      <div>
        <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 0.25rem 0;">
          <?= htmlspecialchars($vehiculo['Marca']) ?> <?= htmlspecialchars($vehiculo['Modelo']) ?>
          <?= $vehiculo['Anio'] ? ' (' . $vehiculo['Anio'] . ')' : '' ?>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
          Propietario: <strong style="color: #f8fafc;"><?= htmlspecialchars($vehiculo['ClienteNombre']) ?></strong>
          <?php if (!empty($vehiculo['ClienteTelefono'])): ?>
            • Tel: <?= htmlspecialchars($vehiculo['ClienteTelefono']) ?>
          <?php endif; ?>
          <?php if (!empty($vehiculo['Color'])): ?>
            • Color: <?= htmlspecialchars($vehiculo['Color']) ?>
          <?php endif; ?>
        </p>
        <?php if (!empty($vehiculo['Combustible']) || !empty($vehiculo['Motor']) || !empty($vehiculo['Transmision']) || !empty($vehiculo['VIN'])): ?>
          <div style="display: flex; gap: 0.4rem; margin-top: 0.45rem; flex-wrap: wrap; align-items: center;">
            <?php if (!empty($vehiculo['Combustible'])): ?>
              <span class="badge" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); font-size: 0.75rem;">
                <i class="fa-solid fa-gas-pump"></i> <?= htmlspecialchars($vehiculo['Combustible']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($vehiculo['Motor'])): ?>
              <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 0.75rem;">
                <i class="fa-solid fa-gears"></i> <?= htmlspecialchars($vehiculo['Motor']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($vehiculo['Transmision'])): ?>
              <span class="badge" style="background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); font-size: 0.75rem;">
                <i class="fa-solid fa-code-branch"></i> <?= htmlspecialchars($vehiculo['Transmision']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($vehiculo['TipoVehiculo'])): ?>
              <span class="badge" style="background: rgba(100, 116, 139, 0.2); color: #cbd5e1; font-size: 0.75rem;">
                <?= htmlspecialchars($vehiculo['TipoVehiculo']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($vehiculo['VIN'])): ?>
              <span style="color: var(--text-muted); font-size: 0.75rem; font-family: monospace; margin-left: 0.3rem;">
                VIN: <?= htmlspecialchars($vehiculo['VIN']) ?>
              </span>
              <button type="button" onclick="buscarEnMannFilterConVin(<?= htmlspecialchars(json_encode($vehiculo['VIN']), ENT_QUOTES) ?>)" class="btn btn-secondary" style="padding: 0.15rem 0.5rem; font-size: 0.72rem; margin-left: 0.3rem;" title="Copia el VIN y abre el catálogo Mann-Filter"><i class="fa-solid fa-filter"></i> Buscar filtros con este VIN</button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <?php if (!empty($vehiculo['ClienteTelefono'])): 
        $telLimpio = preg_replace('/\D/', '', $vehiculo['ClienteTelefono']);
      ?>
        <a href="https://wa.me/56<?= $telLimpio ?>" target="_blank" class="btn btn-secondary" style="background: rgba(16, 185, 129, 0.15); border-color: #10b981; color: #34d399;" title="Contactar por WhatsApp">
          <i class="fa-brands fa-whatsapp"></i> WhatsApp
        </a>
      <?php endif; ?>
      <a href="ordeningreso.php?vehiculo_id=<?= $vehiculo['VehiculoID'] ?>" class="btn btn-primary">
        <i class="fa-solid fa-right-to-bracket"></i> Nueva Orden de Ingreso (OT)
      </a>
      <a href="vehiculos.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver
      </a>
    </div>
  </div>
</div>

<?php if (!empty($message)): ?>
  <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<!-- Grilla de KPIs y Odómetro -->
<div class="fv-kpi-grid">

  <!-- Odómetro / Kilometraje Actual -->
  <div class="fv-kpi-card">
    <div class="fv-kpi-title"><i class="fa-solid fa-gauge-high"></i> Kilometraje Actual</div>
    <div class="fv-kpi-val" style="color: #60a5fa;">
      <?= $vehiculo['KilometrajeUltimo'] ? number_format($vehiculo['KilometrajeUltimo'], 0, ',', '.') . ' km' : 'Sin registrar' ?>
    </div>
    <form method="POST" action="ficha_vehiculo.php?id=<?= $vehiculoId ?>" style="margin-top: 0.6rem; display: flex; gap: 0.4rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="actualizar_km">
      <input type="number" name="kilometraje" class="form-control" placeholder="Nuevo km" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" required>
      <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;" title="Actualizar odómetro"><i class="fa-solid fa-check"></i></button>
    </form>
  </div>

  <!-- Estado de Alertas Preventivas -->
  <div class="fv-kpi-card">
    <div class="fv-kpi-title"><i class="fa-solid fa-bell"></i> Alertas de Mantenimiento</div>
    <div class="fv-kpi-val" style="display: flex; gap: 0.5rem; align-items: baseline;">
      <?php if ($alertasVencidas > 0): ?>
        <span style="color: #ef4444; font-size: 1.4rem;"><i class="fa-solid fa-circle-exclamation"></i> <?= $alertasVencidas ?> Vencida<?= $alertasVencidas > 1 ? 's' : '' ?></span>
      <?php elseif ($alertasProximas > 0): ?>
        <span style="color: #f59e0b; font-size: 1.4rem;"><i class="fa-solid fa-triangle-exclamation"></i> <?= $alertasProximas ?> Próxima<?= $alertasProximas > 1 ? 's' : '' ?></span>
      <?php else: ?>
        <span style="color: #10b981; font-size: 1.3rem;"><i class="fa-solid fa-circle-check"></i> Al día</span>
      <?php endif; ?>
    </div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem;">
      Total programas registrados: <?= count($mantenimientos) ?>
    </div>
  </div>

  <!-- Visitas al Taller (Órdenes de Trabajo) -->
  <div class="fv-kpi-card">
    <div class="fv-kpi-title"><i class="fa-solid fa-clipboard-list"></i> Historial de Órdenes (OT)</div>
    <div class="fv-kpi-val" style="color: #f59e0b;">
      <?= count($ordenes) ?> visita<?= count($ordenes) !== 1 ? 's' : '' ?>
    </div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem;">
      Atendido en el taller mecánico
    </div>
  </div>

  <!-- VIN / N° Chasis -->
  <div class="fv-kpi-card">
    <div class="fv-kpi-title"><i class="fa-solid fa-fingerprint"></i> VIN / N° de Chasis</div>
    <div style="font-size: 0.95rem; font-weight: 700; color: #cbd5e1; font-family: monospace; word-break: break-all; margin-top: 0.2rem;">
      <?= htmlspecialchars($vehiculo['VIN'] ?: 'No registrado') ?>
    </div>
    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.5rem;">
      Color: <?= htmlspecialchars($vehiculo['Color'] ?: 'No especificado') ?>
    </div>
  </div>

</div>

<!-- Sección 1: Alertas de Próximo Mantenimiento Preventivo -->
<div class="fv-section">
  <div class="fv-section-title">
    <span><i class="fa-solid fa-clock-rotate-left" style="color: #f59e0b;"></i> Alertas de Mantenimiento Preventivo</span>
    <button onclick="document.getElementById('modalMantenimiento').style.display='flex'" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
      <i class="fa-solid fa-plus"></i> Programar Mantenimiento
    </button>
  </div>

  <?php if (empty($mantenimientos)): ?>
    <div style="text-align: center; color: var(--text-muted); padding: 1.5rem 0;">
      <i class="fa-solid fa-wrench" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.4;"></i>
      <p style="margin: 0; font-size: 0.88rem;">No hay mantenimientos preventivos programados para este auto.</p>
    </div>
  <?php else: ?>
    <div>
      <?php foreach ($mantenimientos as $m): 
        $st = $m['EstadoCalculado'];
        $iconClass = $st === 'Vencido' ? 'fa-circle-xmark' : ($st === 'Proximo' ? 'fa-triangle-exclamation' : 'fa-circle-check');
        $textColor = $st === 'Vencido' ? '#ef4444' : ($st === 'Proximo' ? '#f59e0b' : '#10b981');
      ?>
        <div class="alerta-card alerta-<?= $st ?>">
          <div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
              <i class="fa-solid <?= $iconClass ?>" style="color: <?= $textColor ?>; font-size: 1.1rem;"></i>
              <strong style="font-size: 0.95rem; color: #fff;"><?= htmlspecialchars($m['TipoMantenimiento']) ?></strong>
              <span class="badge" style="background: <?= $textColor ?>; color: #000; font-weight: 700; font-size: 0.72rem;">
                <?= strtoupper($st) ?>
              </span>
            </div>
            <div style="font-size: 0.8rem; color: #cbd5e1; margin-top: 0.3rem;">
              Realizado el <strong><?= date('d/m/Y', strtotime($m['FechaRealizado'])) ?></strong>
              <?php if ((int)$m['KilometrajeRealizado'] > 0): ?>
                a los <strong><?= number_format($m['KilometrajeRealizado'], 0, ',', '.') ?> km</strong>
              <?php else: ?>
                <span style="color: var(--text-muted);">(no se anotó el kilometraje en esa visita)</span>
              <?php endif; ?>
              <?php if ($m['KilometrajeProximo']): ?>
                • Próximo cambio a los: <strong><?= number_format($m['KilometrajeProximo'], 0, ',', '.') ?> km</strong>
                <?php if ($m['DiferenciaKm'] !== null): ?>
                  (<?= $m['DiferenciaKm'] > 0 ? "faltan " . number_format($m['DiferenciaKm'], 0, ',', '.') . " km" : "hace " . number_format(abs($m['DiferenciaKm']), 0, ',', '.') . " km" ?>)
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($m['FechaProxima']): ?>
                • Fecha límite: <strong><?= date('d/m/Y', strtotime($m['FechaProxima'])) ?></strong>
              <?php endif; ?>
            </div>
            <?php if (!empty($m['Notas'])): ?>
              <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.2rem;">
                <em>Detalle: <?= htmlspecialchars($m['Notas']) ?></em>
              </div>
            <?php endif; ?>
          </div>

          <form method="POST" action="ficha_vehiculo.php?id=<?= $vehiculoId ?>" onsubmit="return confirm('¿Eliminar este registro de mantenimiento?');">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="eliminar_mantenimiento">
            <input type="hidden" name="mantenimiento_id" value="<?= $m['MantenimientoID'] ?>">
            <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Eliminar registro">
              <i class="fa-solid fa-trash"></i>
            </button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Sección 2: ¿Qué necesita este auto? -->
<?php
$tiposRepuestoTxt = [
    'Aceite' => 'Aceite de motor', 'FiltroAceite' => 'Filtro de aceite', 'FiltroAire' => 'Filtro de aire',
    'FiltroCabina' => 'Filtro de cabina', 'FiltroCombustible' => 'Filtro de combustible', 'Frenos' => 'Frenos',
    'Bujias' => 'Bujías', 'Distribucion' => 'Distribución', 'General' => 'Otros',
];
$nombreAuto = $vehiculo['Marca'] . ' ' . $vehiculo['Modelo'];
?>
<div class="fv-section">
  <div class="fv-section-title">
    <div>
      <i class="fa-solid fa-car-tunnel" style="color: #60a5fa;"></i> ¿Qué necesita este auto?
      <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted); margin-left: 0.5rem;">
        Aceite y filtros para <?= htmlspecialchars($nombreAuto) ?>
      </span>
    </div>
    <a href="buscador_repuestos.php?vehiculo_id=<?= (int)$vehiculo['VehiculoID'] ?>" class="btn btn-secondary" style="padding: 0.3rem 0.75rem; font-size: 0.8rem;">
      Ver búsqueda completa <i class="fa-solid fa-arrow-right" style="font-size: 0.7rem;"></i>
    </a>
  </div>

  <?php if (!empty($usadosEnEsteAuto)): ?>
    <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: #c4b5fd; margin-bottom: 0.4rem;">
      <i class="fa-solid fa-clock-rotate-left"></i> Lo último que se le puso a este auto
    </div>
    <div style="overflow-x: auto; margin-bottom: 1rem;">
      <table class="table" style="font-size: 0.85rem;">
        <thead><tr><th>Qué es</th><th>Repuesto</th><th>Cuándo</th><th>En bodega</th><th>Precio hoy</th></tr></thead>
        <tbody>
          <?php foreach ($usadosEnEsteAuto as $tipo => $u): ?>
            <tr>
              <td><span class="badge badge-success" style="font-size: 0.72rem;"><?= htmlspecialchars($tiposRepuestoTxt[$tipo] ?? $tipo) ?></span></td>
              <td><strong style="color:#fff;"><?= htmlspecialchars($u['Nombre']) ?></strong><?= $u['ViscosidadAceite'] ? ' <span style="color:#f59e0b; font-weight:600;">(' . htmlspecialchars($u['ViscosidadAceite']) . ')</span>' : '' ?></td>
              <td><?= date('d/m/Y', strtotime($u['FechaEntrega'])) ?><?= $u['KilometrajeIngreso'] ? ' · ' . number_format($u['KilometrajeIngreso'], 0, ',', '.') . ' km' : '' ?> <span style="color: var(--text-muted);">(<?= htmlspecialchars(formatFolioOT($u['OrdenTrabajoID'])) ?>)</span></td>
              <td><?= $u['Stock'] > 0 ? '<span style="color:#34d399; font-weight:700;">' . (int)$u['Stock'] . ' disp.</span>' : '<span style="color:#f87171; font-weight:600;">Sin stock</span>' ?></td>
              <td style="font-weight:700; color:#93c5fd;">$<?= number_format($u['PrecioVenta'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if (!empty($usadosEnEsteAuto) && !empty($repuestosCompatibles)): ?>
    <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--text-muted); margin-bottom: 0.4rem;">
      Otros repuestos registrados para este modelo
    </div>
  <?php endif; ?>

  <?php if (empty($repuestosCompatibles) && !empty($usadosEnEsteAuto)): ?>
    <?php /* Ya se muestra lo usado en este auto; no hace falta el aviso de "sin datos". */ ?>
  <?php elseif (empty($repuestosCompatibles)): ?>
    <div style="background: rgba(255,255,255,0.02); border: 1px dashed var(--border-dark); border-radius: 8px; padding: 1rem 1.25rem; display: flex; gap: 0.85rem; align-items: flex-start;">
      <i class="fa-solid fa-circle-info" style="color: #60a5fa; margin-top: 0.2rem;"></i>
      <div style="font-size: 0.88rem; line-height: 1.5;">
        <strong>Todavía no hay repuestos guardados para este modelo.</strong>
        <div style="color: var(--text-muted);">
          Se van llenando solos: cuando se entregue una orden de este auto con aceite o filtros, el sistema lo recuerda y lo muestra aquí.
          Mientras tanto, consulta lo que corresponde en el catálogo del fabricante (abajo).
        </div>
      </div>
    </div>
  <?php else: ?>
    <div style="overflow-x: auto;">
      <table class="table" style="font-size: 0.85rem;">
        <thead>
          <tr>
            <th>Qué es</th>
            <th>Repuesto</th>
            <th>Marca</th>
            <th>N° de parte</th>
            <th>En bodega</th>
            <th>Precio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($repuestosCompatibles as $rc): ?>
            <tr>
              <td>
                <span class="badge badge-success" style="font-size: 0.72rem;"><?= htmlspecialchars($tiposRepuestoTxt[$rc['TipoRepuesto']] ?? $rc['TipoRepuesto']) ?></span>
              </td>
              <td>
                <strong style="color: #fff;"><?= htmlspecialchars($rc['ProductoNombre']) ?></strong>
                <?php if (!empty($rc['ViscosidadAceite'])): ?>
                  <span style="color: #f59e0b; font-weight: 600;">(<?= htmlspecialchars($rc['ViscosidadAceite']) ?>)</span>
                <?php endif; ?>
                <?php if (($rc['Origen'] ?? '') === 'Aprendido'): ?>
                  <div style="font-size: 0.72rem; color: #c4b5fd;"><i class="fa-solid fa-brain"></i> Comprobado en el taller · <?= (int)$rc['VecesUsado'] ?> <?= (int)$rc['VecesUsado'] === 1 ? 'vez' : 'veces' ?></div>
                <?php elseif (!empty($rc['Notas'])): ?>
                  <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($rc['Notas']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($rc['MarcaRepuesto'] ?: '-') ?></td>
              <td>
                <code style="font-size: 0.78rem;"><?= htmlspecialchars($rc['NumeroParteOEM'] ?: $rc['NumeroParteAlternativo'] ?: $rc['CodigoBarras']) ?></code>
              </td>
              <td>
                <?php if ($rc['Stock'] > 0): ?>
                  <span style="color: #34d399; font-weight: 700;"><?= (int)$rc['Stock'] ?> disp.</span>
                <?php else: ?>
                  <span style="color: #f87171; font-weight: 600;">Sin stock</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 700; color: #93c5fd;">
                $<?= number_format($rc['PrecioVenta'], 0, ',', '.') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- Catálogos de los fabricantes: un solo lugar, cada uno con su para qué -->
  <div style="margin-top: 1.1rem; padding-top: 0.9rem; border-top: 1px solid var(--border-dark);">
    <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--text-muted); margin-bottom: 0.6rem;">
      ¿No está aquí? Consúltalo en el catálogo del fabricante
    </div>
    <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
      <div>
        <a href="https://lubematch.shell.com/" target="_blank" rel="noopener" class="guide-link-btn guide-shell"><i class="fa-solid fa-oil-can"></i> Shell LubeMatch</a>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Qué aceite y viscosidad usa el auto</div>
      </div>
      <div>
        <a href="https://www.liqui-moly.com/es/es/servicio/guia-de-aceites.html" target="_blank" rel="noopener" class="guide-link-btn guide-liquimoly"><i class="fa-solid fa-flask"></i> Liqui Moly</a>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Aceites y fluidos alternativos</div>
      </div>
      <div>
        <a href="https://www.mann-filter.com/es/catalogo.html" target="_blank" rel="noopener" class="guide-link-btn guide-mann"><i class="fa-solid fa-filter"></i> Mann-Filter</a>
        <?php if (!empty($vehiculo['VIN'])): ?>
          <button type="button" onclick="buscarEnMannFilterConVin(<?= htmlspecialchars(json_encode($vehiculo['VIN']), ENT_QUOTES) ?>)" style="background: none; border: none; color: #4ade80; font-size: 0.75rem; cursor: pointer; text-decoration: underline; padding: 0; margin-left: 0.4rem;">buscar con el VIN</button>
        <?php endif; ?>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">Filtros de aceite, aire, cabina y combustible</div>
      </div>
    </div>
  </div>
</div>

<!-- Sección 3: Historial Clínico de Órdenes de Trabajo (Timeline) -->
<div class="fv-section">
  <div class="fv-section-title">
    <span><i class="fa-solid fa-timeline" style="color: #a78bfa;"></i> Historial Clínico de Órdenes de Trabajo (OT)</span>
    <span style="font-size: 0.85rem; font-weight: normal; color: var(--text-muted);">
      Total: <?= count($ordenes) ?> órdenes
    </span>
  </div>

  <?php if (empty($ordenes)): ?>
    <div style="text-align: center; color: var(--text-muted); padding: 1.5rem 0;">
      <p style="margin: 0; font-size: 0.88rem;">Este vehículo aún no tiene órdenes de trabajo cerradas o registradas.</p>
    </div>
  <?php else: ?>
    <div>
      <?php foreach ($ordenes as $otItem): 
        $folio = formatFolioOT($otItem['OrdenTrabajoID']);
        $detalles = $detallesPorOT[$otItem['OrdenTrabajoID']] ?? [];
        $danios = json_decode($otItem['DaniosCarroceriaJson'] ?? '[]', true) ?: [];
      ?>
        <div class="ot-timeline-item">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
            <div>
              <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <code style="font-weight: 700; font-size: 0.95rem; color: #93c5fd;"><?= htmlspecialchars($folio) ?></code>
                <?php
                  $badgeEstado = 'badge-primary';
                  if ($otItem['Estado'] === 'Entregado') $badgeEstado = 'badge-success';
                  elseif ($otItem['Estado'] === 'En reparación' || $otItem['Estado'] === 'En diagnóstico') $badgeEstado = 'badge-warning';
                  elseif (str_contains($otItem['Estado'], 'rechazado')) $badgeEstado = 'badge-danger';
                ?>
                <span class="badge <?= $badgeEstado ?>"><?= htmlspecialchars($otItem['Estado']) ?></span>
                <span style="color: var(--text-muted); font-size: 0.82rem;">
                  <i class="fa-regular fa-calendar"></i> <?= !empty($otItem['FechaIngreso']) ? date('d/m/Y H:i', strtotime($otItem['FechaIngreso'])) : (!empty($otItem['FechaCreacion']) ? date('d/m/Y H:i', strtotime($otItem['FechaCreacion'])) : '—') ?>
                </span>
                <?php if ($otItem['KilometrajeIngreso']): ?>
                  <span style="color: #60a5fa; font-size: 0.82rem; font-weight: 600;">
                    • <?= number_format($otItem['KilometrajeIngreso'], 0, ',', '.') ?> km
                  </span>
                <?php endif; ?>
              </div>
              <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.3rem;">
                Receptor / Mecánico: <?= htmlspecialchars($otItem['UsuarioNombre'] ?: 'No asignado') ?>
                <?php if ($otItem['NivelCombustible']): ?>
                  • Combustible: <?= htmlspecialchars($otItem['NivelCombustible']) ?>
                <?php endif; ?>
              </div>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
              <a href="comprobante_ot.php?id=<?= $otItem['OrdenTrabajoID'] ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.78rem;" title="Ver Comprobante de Ingreso y Custodia">
                <i class="fa-solid fa-file-lines"></i> Comprobante
              </a>
              <a href="diagnostico.php?id=<?= $otItem['OrdenTrabajoID'] ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.78rem;" title="Ver Diagnóstico Técnico">
                <i class="fa-solid fa-stethoscope"></i> Diagnóstico
              </a>
              <a href="presupuesto.php?id=<?= $otItem['OrdenTrabajoID'] ?>" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.78rem;" title="Ver Presupuesto">
                <i class="fa-solid fa-file-invoice-dollar"></i> Presupuesto
              </a>
              <a href="ejecucion.php?id=<?= $otItem['OrdenTrabajoID'] ?>" class="btn btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.78rem;" title="Ver Taller y Reparación">
                <i class="fa-solid fa-wrench"></i> Taller
              </a>
            </div>
          </div>

          <!-- Desglose de servicios y repuestos ejecutados en esta OT -->
          <?php if (!empty($detalles)): ?>
            <div style="margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px dashed var(--border-dark);">
              <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">
                Trabajos y Repuestos Aprobados en esta visita:
              </div>
              <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                <?php foreach ($detalles as $det): 
                  $isRep = $det['TipoLinea'] === 'Repuesto';
                ?>
                  <span style="display: inline-block; font-size: 0.78rem; padding: 2px 8px; border-radius: 4px; background: <?= $isRep ? 'rgba(59, 130, 246, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; color: <?= $isRep ? '#93c5fd' : '#34d399' ?>; border: 1px solid <?= $isRep ? '#3b82f6' : '#10b981' ?>;">
                    <?= $isRep ? '📦' : '🔧' ?> <?= htmlspecialchars($det['Descripcion']) ?>
                    ($<?= number_format($det['Subtotal'], 0, ',', '.') ?>)
                  </span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Daños registrados en esta visita -->
          <?php if (!empty($danios)): ?>
            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
              <strong>Daños al ingresar:</strong>
              <?php foreach ($danios as $d): ?>
                <span style="color: #fca5a5;">• <?= htmlspecialchars($d['zonaNombre'] ?? $d['tipoTxt'] ?? $d['tipo']) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Modal para Programar Mantenimiento Preventivo -->
<div id="modalMantenimiento" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 520px; max-width: 95%; max-height: 90vh; overflow-y: auto; padding: 1.75rem; box-shadow: var(--shadow-lg);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
      <div>
        <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem; color: #fff;">
          <i class="fa-solid fa-clock-rotate-left" style="color: #f59e0b;"></i> Programar Mantenimiento Preventivo
        </h2>
        <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
          Configura alertas personalizadas por kilometraje o fecha para notificar a tiempo al cliente.
        </p>
      </div>
      <button type="button" onclick="document.getElementById('modalMantenimiento').style.display='none'" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 0.2rem 0.4rem;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form method="POST" action="ficha_vehiculo.php?id=<?= $vehiculoId ?>" style="display: flex; flex-direction: column; gap: 0.85rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add_mantenimiento">

      <div>
        <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">TIPO DE SERVICIO *</label>
        <select name="tipo_mantenimiento" id="modal_tipo_mantenimiento" class="form-control" onchange="aplicarPresetServicio(this.value)" required>
          <option value="Cambio de Aceite y Filtro de Motor (Semisintético)">Cambio de Aceite y Filtro de Motor (Semisintético)</option>
          <option value="Cambio de Aceite y Filtro de Motor (Mineral)">Cambio de Aceite y Filtro de Motor (Mineral)</option>
          <option value="Cambio de Aceite y Filtro de Motor (100% Sintético)">Cambio de Aceite y Filtro de Motor (100% Sintético)</option>
          <option value="Cambio de Pastillas de Freno">Cambio de Pastillas de Freno</option>
          <option value="Cambio de Líquido de Frenos">Cambio de Líquido de Frenos</option>
          <option value="Cambio de Filtro de Aire">Cambio de Filtro de Aire</option>
          <option value="Cambio de Filtro de Cabina / Polen">Cambio de Filtro de Cabina / Polen</option>
          <option value="Cambio de Bujías de Encendido">Cambio de Bujías de Encendido</option>
          <option value="Cambio de Kit de Distribución / Correa">Cambio de Kit de Distribución / Correa</option>
          <option value="Cambio de Refrigerante / Anticongelante">Cambio de Refrigerante / Anticongelante</option>
          <option value="Cambio de Aceite de Caja (Valvulina / ATF)">Cambio de Aceite de Caja (Valvulina / ATF)</option>
          <option value="Alineación y Balanceo">Alineación y Balanceo</option>
          <option value="Rotación de Neumáticos">Rotación de Neumáticos</option>
        </select>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">KM ACTUAL / REALIZADO</label>
          <input type="number" name="km_realizado" id="modal_km_realizado" class="form-control" value="<?= $kmActual ?>" oninput="recalcularKmDesdeActual()" required>
        </div>
        <div>
          <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">PRÓXIMO AVISO (KM)</label>
          <input type="number" name="km_proximo" id="modal_km_proximo" class="form-control" value="<?= $kmActual ? ($kmActual + 10000) : '' ?>" placeholder="Ej: +10.000 km">
        </div>
      </div>

      <!-- Accesos Rápidos de Kilometraje -->
      <div>
        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.3rem;">
          <i class="fa-solid fa-gauge-high" style="color: #60a5fa;"></i> Sumar intervalo de KM:
        </div>
        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;" id="chips_km">
          <button type="button" class="btn-chip" onclick="seleccionarDeltaKm(5000, this)">+5.000 km</button>
          <button type="button" class="btn-chip active" onclick="seleccionarDeltaKm(10000, this)">+10.000 km</button>
          <button type="button" class="btn-chip" onclick="seleccionarDeltaKm(15000, this)">+15.000 km</button>
          <button type="button" class="btn-chip" onclick="seleccionarDeltaKm(25000, this)">+25.000 km</button>
          <button type="button" class="btn-chip" onclick="seleccionarDeltaKm(40000, this)">+40.000 km</button>
          <button type="button" class="btn-chip" onclick="seleccionarDeltaKm(60000, this)">+60.000 km</button>
        </div>
      </div>

      <div>
        <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">FECHA LÍMITE RECOMENDADA</label>
        <input type="date" name="fecha_proxima" id="modal_fecha_proxima" class="form-control" value="<?= date('Y-m-d', strtotime('+6 months')) ?>">
      </div>

      <!-- Accesos Rápidos de Tiempo / Meses -->
      <div>
        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 600; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.3rem;">
          <i class="fa-solid fa-calendar-days" style="color: #f59e0b;"></i> Plazo máximo sugerido:
        </div>
        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;" id="chips_meses">
          <button type="button" class="btn-chip" onclick="seleccionarMeses(3, this)">3 meses</button>
          <button type="button" class="btn-chip active" onclick="seleccionarMeses(6, this)">6 meses</button>
          <button type="button" class="btn-chip" onclick="seleccionarMeses(12, this)">12 meses (1 año)</button>
          <button type="button" class="btn-chip" onclick="seleccionarMeses(24, this)">24 meses (2 años)</button>
          <button type="button" class="btn-chip" onclick="seleccionarMeses(36, this)">36 meses (3 años)</button>
        </div>
      </div>

      <div>
        <label style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">NOTAS / MARCA DE REPUESTO UTILIZADA</label>
        <input type="text" name="notas" id="modal_notas" class="form-control" placeholder="Ej: Shell Helix Ultra 5W-30 + Filtro Mann W68/3">
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Guardar Alerta</button>
        <button type="button" onclick="document.getElementById('modalMantenimiento').style.display='none'" class="btn btn-secondary btn-block">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<style>
.btn-chip {
  padding: 0.25rem 0.55rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid var(--border-dark);
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-chip:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border-color: rgba(255, 255, 255, 0.25);
}
.btn-chip.active {
  background: rgba(59, 130, 246, 0.18);
  border-color: #3b82f6;
  color: #60a5fa;
  font-weight: 700;
}
</style>

<script>
let deltaKmSeleccionado = 10000;

function seleccionarDeltaKm(delta, btn) {
  deltaKmSeleccionado = delta;
  const kmBase = parseInt(document.getElementById('modal_km_realizado').value) || 0;
  document.getElementById('modal_km_proximo').value = kmBase + delta;

  // Actualizar clase activa
  document.querySelectorAll('#chips_km .btn-chip').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
}

function recalcularKmDesdeActual() {
  if (deltaKmSeleccionado > 0) {
    const kmBase = parseInt(document.getElementById('modal_km_realizado').value) || 0;
    document.getElementById('modal_km_proximo').value = kmBase + deltaKmSeleccionado;
  }
}

function seleccionarMeses(meses, btn) {
  const d = new Date();
  d.setMonth(d.getMonth() + meses);
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  document.getElementById('modal_fecha_proxima').value = `${yyyy}-${mm}-${dd}`;

  // Actualizar clase activa
  document.querySelectorAll('#chips_meses .btn-chip').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
}

function aplicarPresetServicio(tipo) {
  let targetDeltaKm = 10000;
  let targetMeses = 6;
  let notaSugerida = '';

  const tipoLower = tipo.toLowerCase();

  if (tipoLower.includes('mineral')) {
    targetDeltaKm = 5000;
    targetMeses = 3;
    notaSugerida = 'Aceite Mineral 15W-40 / 20W-50 + Filtro';
  } else if (tipoLower.includes('100% sintético') || tipoLower.includes('sintetico')) {
    targetDeltaKm = 15000;
    targetMeses = 12;
    notaSugerida = 'Aceite Sintético 5W-30 / 0W-20 + Filtro';
  } else if (tipoLower.includes('aceite')) {
    targetDeltaKm = 10000;
    targetMeses = 6;
    notaSugerida = 'Aceite Semisintético 10W-40 + Filtro';
  } else if (tipoLower.includes('freno')) {
    targetDeltaKm = 25000;
    targetMeses = 12;
    notaSugerida = 'Revisión / reemplazo de pastillas o purga DOT4';
  } else if (tipoLower.includes('filtro de aire') || tipoLower.includes('cabina')) {
    targetDeltaKm = 15000;
    targetMeses = 12;
    notaSugerida = 'Reemplazo elemento filtrante';
  } else if (tipoLower.includes('bujía') || tipoLower.includes('bujias')) {
    targetDeltaKm = 30000;
    targetMeses = 24;
    notaSugerida = 'Juego de bujías nuevas';
  } else if (tipoLower.includes('distribución') || tipoLower.includes('distribucion') || tipoLower.includes('correa')) {
    targetDeltaKm = 60000;
    targetMeses = 36;
    notaSugerida = 'Kit correa de distribución + tensores + bomba de agua';
  } else if (tipoLower.includes('refrigerante')) {
    targetDeltaKm = 40000;
    targetMeses = 24;
    notaSugerida = 'Refrigerante 50/50 orgánico / lavado de circuito';
  } else if (tipoLower.includes('caja')) {
    targetDeltaKm = 40000;
    targetMeses = 36;
    notaSugerida = 'Fluido de transmisión / ATF';
  } else if (tipoLower.includes('alineación') || tipoLower.includes('rotación')) {
    targetDeltaKm = 10000;
    targetMeses = 6;
    notaSugerida = 'Control de desgaste parejo de neumáticos';
  }

  // Buscar el botón correspondiente a los km
  const kmBtns = Array.from(document.querySelectorAll('#chips_km .btn-chip'));
  const matchKmBtn = kmBtns.find(b => b.textContent.includes(targetDeltaKm.toLocaleString('es-CL')));
  seleccionarDeltaKm(targetDeltaKm, matchKmBtn || null);

  // Buscar el botón correspondiente a los meses
  const mesesBtns = Array.from(document.querySelectorAll('#chips_meses .btn-chip'));
  const matchMesesBtn = mesesBtns.find(b => b.textContent.startsWith(targetMeses + ' '));
  seleccionarMeses(targetMeses, matchMesesBtn || null);

  const campoNotas = document.getElementById('modal_notas');
  if (campoNotas && !campoNotas.value) {
    campoNotas.placeholder = notaSugerida;
  }
}
</script>

