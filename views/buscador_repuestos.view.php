<?php
// Etiquetas bajo cada repuesto: de dónde viene la sugerencia, para qué motor y avisos de compatibilidad.
function compatBadges(array $r): string {
    $h = '<div style="margin-top: 3px; display: flex; gap: 4px; flex-wrap: wrap; align-items: center;">';
    if (($r['Origen'] ?? '') === 'Aprendido') {
        $h .= '<span class="badge" style="background: rgba(167,139,250,0.18); color: #c4b5fd; font-size: 0.68rem;" title="El taller ya usó este repuesto en este modelo"><i class="fa-solid fa-brain"></i> Comprobado en el taller · ' . (int)$r['VecesUsado'] . ' ' . ((int)$r['VecesUsado'] === 1 ? 'vez' : 'veces') . '</span>';
    }
    if (!empty($r['Motor'])) {
        $h .= '<span class="badge" style="background: rgba(56,189,248,0.15); color: #7dd3fc; font-size: 0.68rem;">Motor ' . htmlspecialchars($r['Motor']) . '</span>';
    }
    foreach ($r['_avisos'] ?? [] as $a) {
        $h .= '<span class="badge badge-warning" style="font-size: 0.68rem;"><i class="fa-solid fa-triangle-exclamation"></i> ' . htmlspecialchars($a) . '</span>';
    }
    if (($r['Origen'] ?? '') === 'Aprendido') {
        $h .= '<form method="POST" action="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" style="display:inline;" onsubmit="return confirm(\'¿Quitar esta sugerencia? Solo hazlo si el repuesto se registró por error.\');">'
            . csrfField() . '<input type="hidden" name="action" value="quitar_compat"><input type="hidden" name="compat_id" value="' . (int)$r['CompatibilidadID'] . '">'
            . '<button type="submit" style="background:none; border:none; color: var(--text-muted); font-size: 0.7rem; cursor: pointer; text-decoration: underline;">quitar</button></form>';
    }
    return $h . '</div>';
}
?>
<style>
  .br-search-box { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; }
  .br-quick-car { background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 8px; padding: 0.75rem 1rem; cursor: pointer; text-decoration: none; color: #fff; transition: 0.15s; display: flex; align-items: center; justify-content: space-between; }
  .br-quick-car:hover { background: rgba(59, 130, 246, 0.15); border-color: #3b82f6; }

  .br-cat-section { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
  .br-cat-header { font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; color: #fff; }

  .guide-card-box { background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9)); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
  .guide-link-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: 0.15s; border: 1px solid transparent; }
  .guide-shell { background: rgba(234, 179, 8, 0.15); color: #fde047; border-color: rgba(234, 179, 8, 0.3); }
  .guide-shell:hover { background: rgba(234, 179, 8, 0.25); color: #fff; }
  .guide-liquimoly { background: rgba(59, 130, 246, 0.15); color: #93c5fd; border-color: rgba(59, 130, 246, 0.3); }
  .guide-liquimoly:hover { background: rgba(59, 130, 246, 0.25); color: #fff; }
  .guide-mann { background: rgba(34, 197, 94, 0.15); color: #86efac; border-color: rgba(34, 197, 94, 0.3); }
  .guide-mann:hover { background: rgba(34, 197, 94, 0.25); color: #fff; }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">
      <i class="fa-solid fa-wand-magic-sparkles" style="color: #60a5fa;"></i> ¿Qué necesita este auto?
    </h1>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.25rem 0 0 0;">
      Buscador inteligente de aceites, filtros y repuestos compatibles con stock en bodega y guías técnicas públicas.
    </p>
  </div>
  <div style="display: flex; gap: 0.5rem;">
    <a href="vehiculos.php" class="btn btn-secondary">
      <i class="fa-solid fa-car-side"></i> Ficha de Vehículos
    </a>
  </div>
</div>

<!-- Buscador Rápido -->
<div class="br-search-box">
  <form method="GET" action="buscador_repuestos.php" style="display: flex; flex-direction: column; gap: 1rem;">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; align-items: flex-end;">
      
      <!-- Opción 1: Elegir de vehículos registrados -->
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.4rem;">
          SELECCIONAR UN VEHÍCULO REGISTRADO EN EL TALLER
        </label>
        <select name="vehiculo_id" class="form-control" onchange="if(this.value) this.form.submit();">
          <option value="">-- Elige un vehículo del taller --</option>
          <?php foreach ($vehiculos as $v): 
            $selected = $vehiculoId === (int)$v['VehiculoID'] ? 'selected' : '';
          ?>
            <option value="<?= $v['VehiculoID'] ?>" <?= $selected ?>>
              [<?= htmlspecialchars($v['Patente']) ?>] <?= htmlspecialchars($v['Marca'] . ' ' . $v['Modelo'] . ($v['Anio'] ? ' ' . $v['Anio'] : '')) ?> — <?= htmlspecialchars($v['ClienteNombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Opción 2: Búsqueda libre por Marca y Modelo -->
      <div style="display: flex; gap: 0.5rem;">
        <div style="flex: 1;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.4rem;">
            O ESCRIBE MARCA
          </label>
          <input type="text" name="marca" class="form-control" placeholder="Ej: Toyota" value="<?= htmlspecialchars($marcaParam) ?>">
        </div>
        <div style="flex: 1;">
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.4rem;">
            MODELO
          </label>
          <input type="text" name="modelo" class="form-control" placeholder="Ej: Yaris" value="<?= htmlspecialchars($modeloParam) ?>">
        </div>
      </div>

      <div>
        <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px;">
          <i class="fa-solid fa-magnifying-glass"></i> Consultar Compatibilidad
        </button>
      </div>

    </div>

  </form>
</div>

<!-- Si no hay búsqueda activa, sugerir accesos rápidos de autos populares -->
<?php if (empty($marcaParam) && empty($modeloParam) && !$vehiculoSeleccionado): ?>
  <div class="fv-section">
    <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-muted); text-transform: uppercase;">
      Vehículos más frecuentes en el taller (Consultas Rápidas)
    </h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
      <a href="buscador_repuestos.php?marca=Toyota&modelo=Yaris" class="br-quick-car">
        <span><strong>Toyota Yaris</strong> <small style="color: var(--text-muted);">1.3 / 1.5</small></span>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
      </a>
      <a href="buscador_repuestos.php?marca=Hyundai&modelo=Accent" class="br-quick-car">
        <span><strong>Hyundai Accent</strong> <small style="color: var(--text-muted);">1.4 / 1.6</small></span>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
      </a>
      <a href="buscador_repuestos.php?marca=Kia&modelo=Rio" class="br-quick-car">
        <span><strong>Kia Rio</strong> <small style="color: var(--text-muted);">1.2 / 1.4</small></span>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
      </a>
      <a href="buscador_repuestos.php?marca=Chevrolet&modelo=Sail" class="br-quick-car">
        <span><strong>Chevrolet Sail</strong> <small style="color: var(--text-muted);">1.4 / 1.5</small></span>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
      </a>
      <a href="buscador_repuestos.php?marca=Nissan&modelo=Versa" class="br-quick-car">
        <span><strong>Nissan Versa</strong> <small style="color: var(--text-muted);">1.6</small></span>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
      </a>
      <a href="buscador_repuestos.php?marca=Toyota&modelo=Corolla" class="br-quick-car">
        <span><strong>Toyota Corolla</strong> <small style="color: var(--text-muted);">1.8 / 2.0</small></span>
        <i class="fa-solid fa-chevron-right" style="color: var(--text-muted); font-size: 0.8rem;"></i>
      </a>
    </div>
  </div>
<?php endif; ?>

<!-- Panel Guías Oficiales Públicas de Lubricantes -->
<?php if ($marcaParam !== '' || $modeloParam !== ''): ?>
  <div class="guide-card-box">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0 0 0.25rem 0; color: #fff;">
          <i class="fa-solid fa-book-open" style="color: #60a5fa;"></i> Guías Técnicas Públicas de Fabricantes
        </h2>
        <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0;">
          Para consultar capacidades de cárter exactas, viscosidades sugeridas por clima y referencias de filtros oficiales:
        </p>
      </div>
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="https://lubematch.shell.com/" target="_blank" class="guide-link-btn guide-shell" title="Buscador Shell LubeMatch">
          <i class="fa-solid fa-oil-can"></i> Shell LubeMatch
        </a>
        <a href="https://www.liqui-moly.com/es/es/servicio/guia-de-aceites.html" target="_blank" class="guide-link-btn guide-liquimoly" title="Guía técnica de lubricantes y fluidos alemana Liqui Moly">
          <i class="fa-solid fa-flask"></i> Liqui Moly Guide
        </a>
        <a href="https://catalog.mann-filter.com/" target="_blank" class="guide-link-btn guide-mann" title="Catálogo Mann-Filter oficial">
          <i class="fa-solid fa-filter"></i> Catálogo Mann-Filter
        </a>
      </div>
    </div>
  </div>

  <?php if ($vehiculoSeleccionado): ?>
    <div style="background: rgba(59, 130, 246, 0.12); border: 1px solid #3b82f6; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
      <div>
        Vehículo Seleccionado: <strong style="color: #fff;"><?= htmlspecialchars($vehiculoSeleccionado['Marca'] . ' ' . $vehiculoSeleccionado['Modelo']) ?></strong>
        (Patente: <code style="font-weight: 700;"><?= htmlspecialchars($vehiculoSeleccionado['Patente']) ?></code>)
        — Cliente: <?= htmlspecialchars($vehiculoSeleccionado['ClienteNombre']) ?>
      </div>
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <?php if (!empty($vehiculoSeleccionado['VIN'])): ?>
          <button type="button" onclick="buscarEnMannFilterConVin(<?= htmlspecialchars(json_encode($vehiculoSeleccionado['VIN']), ENT_QUOTES) ?>)" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.78rem; border-color: #16a34a; color: #4ade80;" title="Copia el VIN y abre el catálogo Mann-Filter en otra pestaña">
            <i class="fa-solid fa-filter"></i> Buscar en Mann-Filter con VIN
          </button>
        <?php else: ?>
          <span style="font-size: 0.75rem; color: var(--text-muted); align-self: center;" title="Registra el VIN en la ficha del vehículo para poder buscar en Mann-Filter por VIN"><i class="fa-solid fa-circle-info"></i> Sin VIN registrado</span>
        <?php endif; ?>
        <a href="ficha_vehiculo.php?id=<?= $vehiculoSeleccionado['VehiculoID'] ?>" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.78rem;">
          <i class="fa-solid fa-file-waveform"></i> Ver Ficha Clínica
        </a>
        <a href="ordeningreso.php?vehiculo_id=<?= $vehiculoSeleccionado['VehiculoID'] ?>" class="btn btn-primary" style="padding: 0.25rem 0.6rem; font-size: 0.78rem;">
          <i class="fa-solid fa-right-to-bracket"></i> Nueva OT
        </a>
      </div>
    </div>
    <?php if (empty(trim((string)$vehiculoSeleccionado['Motor']))): ?>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin: -0.75rem 0 1.25rem;"><i class="fa-solid fa-lightbulb"></i> Este vehículo no tiene motor registrado. Si lo completas en su ficha, el sistema puede avisar cuando un repuesto sea para otro motor.</p>
    <?php endif; ?>

    <div class="br-cat-section" style="border-color: rgba(167,139,250,0.45);">
      <div class="br-cat-header"><i class="fa-solid fa-clock-rotate-left" style="color: #c4b5fd;"></i> Lo que ya usamos en este auto</div>
      <?php if (empty($usadosEnEsteAuto)): ?>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Todavía no hay órdenes entregadas de este vehículo con repuestos de aceite, filtros o frenos. Cuando se entregue una, el sistema recordará qué se le puso.</p>
      <?php else: ?>
        <table class="table" style="font-size: 0.85rem;">
          <thead><tr><th>Tipo</th><th>Repuesto</th><th>Última vez</th><th>Stock</th><th>Precio</th></tr></thead>
          <tbody>
            <?php foreach ($usadosEnEsteAuto as $tipo => $h): ?>
              <tr>
                <td><?= htmlspecialchars(preg_replace('/(?<!^)([A-Z])/', ' $1', $tipo)) ?></td>
                <td><strong style="color:#fff;"><?= htmlspecialchars($h['Nombre']) ?></strong><?= $h['ViscosidadAceite'] ? ' <span class="badge badge-warning">' . htmlspecialchars($h['ViscosidadAceite']) . '</span>' : '' ?></td>
                <td><?= date('d/m/Y', strtotime($h['FechaEntrega'])) ?><?= $h['KilometrajeIngreso'] ? ' · ' . number_format($h['KilometrajeIngreso'], 0, ',', '.') . ' km' : '' ?> <span style="color: var(--text-muted);">(<?= htmlspecialchars(formatFolioOT($h['OrdenTrabajoID'])) ?>)</span></td>
                <td><?= $h['Stock'] > 0 ? '<span style="color:#34d399; font-weight:700;">' . (int)$h['Stock'] . ' disp.</span>' : '<span style="color:#f87171;">Sin stock</span>' ?></td>
                <td style="font-weight:700; color:#93c5fd;">$<?= number_format($h['PrecioVenta'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($mensaje)): ?>
    <div style="background: rgba(16,185,129,0.15); border: 1px solid var(--success); color: #34d399; padding: 0.6rem 1rem; border-radius: 8px; margin-bottom: 1rem;"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <!-- Categoría 1: Aceites de Motor -->
  <div class="br-cat-section">
    <div class="br-cat-header">
      <i class="fa-solid fa-droplet" style="color: #f59e0b;"></i> 1. Aceites de Motor Compatibles
    </div>
    <?php if (empty($repuestosPorTipo['Aceite'])): ?>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">No hay aceites vinculados en la matriz local para este modelo. Consulta en Shell LubeMatch.</p>
    <?php else: ?>
      <table class="table" style="font-size: 0.85rem;">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Viscosidad</th>
            <th>Marca</th>
            <th>Capacidad / Detalle</th>
            <th>Stock en Bodega</th>
            <th>Precio Venta</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($repuestosPorTipo['Aceite'] as $r): ?>
            <tr>
              <td>
                <strong style="color: #fff;"><?= htmlspecialchars($r['ProductoNombre']) ?></strong><?= compatBadges($r) ?>
                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($r['ProductoDesc']) ?></div>
              </td>
              <td><span class="badge badge-warning" style="font-weight: 700;"><?= htmlspecialchars($r['ViscosidadAceite']) ?></span></td>
              <td><?= htmlspecialchars($r['MarcaRepuesto'] ?: '-') ?></td>
              <td><?= htmlspecialchars($r['Notas'] ?: 'Según cárter') ?></td>
              <td>
                <?php if ($r['Stock'] > 0): ?>
                  <span style="color: #34d399; font-weight: 700;"><?= (int)$r['Stock'] ?> disp.</span>
                <?php else: ?>
                  <span style="color: #f87171; font-weight: 600;">Sin stock</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 700; color: #93c5fd;">$<?= number_format($r['PrecioVenta'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Categoría 2: Filtros de Aceite -->
  <div class="br-cat-section">
    <div class="br-cat-header">
      <i class="fa-solid fa-filter" style="color: #60a5fa;"></i> 2. Filtros de Aceite Compatibles
    </div>
    <?php if (empty($repuestosPorTipo['FiltroAceite'])): ?>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">No hay filtros de aceite en matriz local. Consulta en el Catálogo Mann-Filter.</p>
    <?php else: ?>
      <table class="table" style="font-size: 0.85rem;">
        <thead>
          <tr>
            <th>Filtro / Referencia</th>
            <th>Marca</th>
            <th>N° Parte (OEM / Alt)</th>
            <th>Motor / Notas</th>
            <th>Stock en Bodega</th>
            <th>Precio Venta</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($repuestosPorTipo['FiltroAceite'] as $r): ?>
            <tr>
              <td><strong style="color: #fff;"><?= htmlspecialchars($r['ProductoNombre']) ?></strong><?= compatBadges($r) ?></td>
              <td><?= htmlspecialchars($r['MarcaRepuesto'] ?: '-') ?></td>
              <td><code><?= htmlspecialchars($r['NumeroParteOEM'] ?: $r['NumeroParteAlternativo'] ?: $r['CodigoBarras']) ?></code></td>
              <td><?= htmlspecialchars($r['Notas'] ?: $r['Motor'] ?: '-') ?></td>
              <td>
                <?php if ($r['Stock'] > 0): ?>
                  <span style="color: #34d399; font-weight: 700;"><?= (int)$r['Stock'] ?> disp.</span>
                <?php else: ?>
                  <span style="color: #f87171; font-weight: 600;">Sin stock</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 700; color: #93c5fd;">$<?= number_format($r['PrecioVenta'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Categoría 3: Filtros de Aire y Cabina -->
  <div class="br-cat-section">
    <div class="br-cat-header">
      <i class="fa-solid fa-wind" style="color: #38bdf8;"></i> 3. Filtros de Aire & Habitáculo
    </div>
    <?php 
    $filtrosAireYOtros = array_merge($repuestosPorTipo['FiltroAire'], $repuestosPorTipo['FiltrosOtros']);
    if (empty($filtrosAireYOtros)): ?>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">No hay filtros de aire registrados para este modelo.</p>
    <?php else: ?>
      <table class="table" style="font-size: 0.85rem;">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Marca</th>
            <th>N° Parte</th>
            <th>Stock</th>
            <th>Precio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($filtrosAireYOtros as $r): ?>
            <tr>
              <td><span class="badge badge-success"><?= htmlspecialchars($r['TipoRepuesto']) ?></span></td>
              <td><strong style="color: #fff;"><?= htmlspecialchars($r['ProductoNombre']) ?></strong><?= compatBadges($r) ?></td>
              <td><?= htmlspecialchars($r['MarcaRepuesto'] ?: '-') ?></td>
              <td><code><?= htmlspecialchars($r['NumeroParteOEM'] ?: $r['NumeroParteAlternativo'] ?: $r['CodigoBarras']) ?></code></td>
              <td>
                <?php if ($r['Stock'] > 0): ?>
                  <span style="color: #34d399; font-weight: 700;"><?= (int)$r['Stock'] ?> disp.</span>
                <?php else: ?>
                  <span style="color: #f87171; font-weight: 600;">Sin stock</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 700; color: #93c5fd;">$<?= number_format($r['PrecioVenta'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- Categoría 4: Frenos y Bujías -->
  <?php 
  $frenosYBujias = array_merge($repuestosPorTipo['Frenos'], $repuestosPorTipo['Bujias']);
  if (!empty($frenosYBujias)): ?>
    <div class="br-cat-section">
      <div class="br-cat-header">
        <i class="fa-solid fa-gear" style="color: #a78bfa;"></i> 4. Frenos y Bujías de Encendido
      </div>
      <table class="table" style="font-size: 0.85rem;">
        <thead>
          <tr>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Marca</th>
            <th>N° Parte</th>
            <th>Stock</th>
            <th>Precio</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($frenosYBujias as $r): ?>
            <tr>
              <td><span class="badge badge-success"><?= htmlspecialchars($r['TipoRepuesto']) ?></span></td>
              <td><strong style="color: #fff;"><?= htmlspecialchars($r['ProductoNombre']) ?></strong><?= compatBadges($r) ?></td>
              <td><?= htmlspecialchars($r['MarcaRepuesto'] ?: '-') ?></td>
              <td><code><?= htmlspecialchars($r['NumeroParteOEM'] ?: $r['NumeroParteAlternativo'] ?: $r['CodigoBarras']) ?></code></td>
              <td>
                <?php if ($r['Stock'] > 0): ?>
                  <span style="color: #34d399; font-weight: 700;"><?= (int)$r['Stock'] ?> disp.</span>
                <?php else: ?>
                  <span style="color: #f87171; font-weight: 600;">Sin stock</span>
                <?php endif; ?>
              </td>
              <td style="font-weight: 700; color: #93c5fd;">$<?= number_format($r['PrecioVenta'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php endif; ?>
