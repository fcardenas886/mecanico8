<?php
$folio = formatFolioOT($ot['OrdenTrabajoID']);
$tipoLabel = ['Repuesto' => 'Repuesto', 'ManoObra' => 'Mano de Obra', 'Terceros' => 'Terceros'];
$tipoClase = ['Repuesto' => 'badge-success', 'ManoObra' => 'badge-warning', 'Terceros' => 'badge-danger'];
$pendiente = $presupuesto && $presupuesto['DecisionCliente'] === 'Pendiente';
$decidido = $presupuesto && $presupuesto['DecisionCliente'] !== 'Pendiente';

$decisionLabel = [
    'AprobadoTotal' => 'Aprobado Total',
    'AprobadoParcial' => 'Aprobado Parcial',
    'Rechazado' => 'Rechazado',
];
$decisionClase = [
    'AprobadoTotal' => 'badge-success',
    'AprobadoParcial' => 'badge-warning',
    'Rechazado' => 'badge-danger',
];
?>
<style>
  .pr-section { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
  .pr-hallazgo { font-size: 0.85rem; padding: 0.3rem 0; border-bottom: 1px dashed var(--border-dark); }
  .pr-hallazgo:last-child { border-bottom: none; }
  .pr-add-row { display: flex; gap: 0.6rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 0.75rem; }
  .pr-add-row label { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 0.2rem; }
  .pr-lineas-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
  .pr-lineas-table th { text-align: left; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); padding: 0.5rem 0.4rem; border-bottom: 1px solid var(--border-dark); }
  .pr-lineas-table td { padding: 0.55rem 0.4rem; border-bottom: 1px dashed var(--border-dark); vertical-align: middle; }
  .pr-total-row td { border-bottom: none; font-weight: 700; padding-top: 0.75rem; }
  .pr-decision-box { border: 1px solid var(--border-dark); border-radius: 10px; padding: 1rem; margin-top: 1rem; }

  @media print {
    body * { visibility: hidden !important; }
    .pr-print, .pr-print * { visibility: visible !important; }
    .pr-print { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; }
    .no-print { display: none !important; }
  }
</style>

<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;" class="no-print">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Presupuesto para el cliente</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
      <strong><?= htmlspecialchars($folio) ?></strong> ·
      <code style="font-weight: 700;"><?= htmlspecialchars($ot['Patente']) ?></code>
      <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> — <?= htmlspecialchars($ot['ClienteNombre']) ?>
    </p>
  </div>
  <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
    <span class="badge <?= $pendiente ? 'badge-warning' : 'badge-success' ?>"><?= htmlspecialchars(otEstadoInfo($ot + ['PresupuestoID' => $presupuesto['PresupuestoID'] ?? null, 'DecisionCliente' => $presupuesto['DecisionCliente'] ?? null, 'CantidadLineasPresupuesto' => count($lineas)])['etiqueta']) ?></span>
    <?php if ($presupuesto): ?>
      <a href="comprobante_presupuesto.php?id=<?= $otId ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" target="_blank">
        <i class="fa-solid fa-print"></i> Imprimir presupuesto
      </a>
    <?php endif; ?>
    <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
      <i class="fa-solid fa-arrow-left"></i> Órdenes de trabajo
    </a>
  </div>
</div>
<?php otStepper($ot, 3); ?>
<?php if (!$presupuesto): ?>
  <?php tallerAyuda('<strong>Qué hago aquí:</strong> pulsa <strong>Crear presupuesto</strong> y agrega los repuestos y la mano de obra con su precio. Después se lo muestras o envías al cliente.'); ?>
<?php elseif ($pendiente): ?>
  <?php tallerAyuda('<strong>Qué hago aquí:</strong> 1) agrega los repuestos y la mano de obra; 2) imprime o envía el presupuesto al cliente; 3) cuando responda, marca las líneas que aceptó y pulsa <strong>Aprobado total</strong>, <strong>Aprobado parcial</strong> (solo lo marcado) o <strong>Rechazado</strong>.'); ?>
<?php elseif ($presupuesto['DecisionCliente'] !== 'Rechazado'): ?>
  <?php tallerAyuda('<strong>Presupuesto aprobado.</strong> Siguiente paso: <a href="ejecucion.php?id=' . (int)$otId . '" style="color:#fff; text-decoration: underline;"><strong>iniciar la reparación y cobrar</strong></a>.'); ?>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="no-print" style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if (!empty($hallazgos)): ?>
  <div class="pr-section no-print">
    <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem;">Hallazgos del diagnóstico</h2>
    <?php foreach ($hallazgos as $h): ?>
      <div class="pr-hallazgo"><strong><?= htmlspecialchars($h['Area']) ?>:</strong> <?= htmlspecialchars($h['Hallazgo']) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$presupuesto): ?>

  <div class="pr-section no-print" style="text-align: center;">
    <p style="color: var(--text-muted); margin-bottom: 1rem;">Todavía no hay presupuesto para esta orden.</p>
    <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="crear_presupuesto">
      <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
        <i class="fa-solid fa-file-invoice-dollar"></i> Crear Presupuesto
      </button>
    </form>
  </div>

<?php else: ?>

  <?php if ($pendiente && !empty($lineas)): ?>
    <!-- Banner de Guía Intuitiva: ¿Qué sigue ahora? -->
    <div class="no-print" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.45), rgba(15, 23, 42, 0.75)); border: 2px solid #3b82f6; border-radius: 12px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
          <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.05rem; font-weight: 800; color: #60a5fa;">
            <i class="fa-solid fa-circle-question" style="font-size: 1.25rem;"></i> ¿Qué sigue ahora con este presupuesto?
          </div>
          <div style="font-size: 0.88rem; color: #e2e8f0; margin-top: 0.45rem; line-height: 1.55;">
            <strong>1. Comparte el total de <?= formatCLP($total) ?></strong> con <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong>: por WhatsApp o con el botón "Imprimir presupuesto" de arriba.<br>
            <strong>2. Cuando el cliente responda</strong>, usa los tres botones de más abajo: <strong>Aprobó todo</strong>, <strong>Aprobó solo lo marcado</strong> o <strong>Rechazó</strong>.<br>
            <strong>3. Si aprueba</strong>, la orden pasa a reparación y cobro.
          </div>
        </div>
        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
          <?php if (!empty($ot['ClienteTelefono'])): 
            $telLimpio = preg_replace('/\D/', '', $ot['ClienteTelefono']);
            $msgWa = urlencode("Hola {$ot['ClienteNombre']}, te enviamos el presupuesto formal de tu {$ot['Marca']} {$ot['Modelo']} (Patente {$ot['Patente']}) por un total de " . formatCLP($total) . ". ¿Nos autorizas para iniciar los trabajos en el taller?");
          ?>
            <a href="https://wa.me/56<?= $telLimpio ?>?text=<?= $msgWa ?>" target="_blank" class="btn" style="background: #25d366; color: #fff; font-weight: 700; padding: 0.65rem 1rem; border: none;" title="Enviar cotización por WhatsApp al cliente">
              <i class="fa-brands fa-whatsapp"></i> Enviar por WhatsApp
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($pendiente): ?>
    <div class="pr-section">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
        <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Agregar repuesto</h2>
        <a href="buscador_repuestos.php?vehiculo_id=<?= $ot['VehiculoID'] ?>" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.6rem; font-size: 0.78rem; color: #93c5fd; border-color: #3b82f6;" title="Ver aceites y filtros compatibles con este vehículo">
          <i class="fa-solid fa-wand-magic-sparkles"></i> ¿Qué repuestos necesita este auto?
        </a>
      </div>
      <form method="POST" action="presupuesto.php?id=<?= $otId ?>" class="pr-add-row">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="agregar_repuesto">
        <div style="flex: 1; min-width: 260px;">
          <label>REPUESTO</label>
          <input type="search" id="filtroRepuesto" class="form-control" placeholder="Escribe nombre, marca o N° de parte (ej. W 67/1) para filtrar la lista" autocomplete="off" style="margin-bottom: 0.4rem;">
          <select name="producto_id" id="selRepuesto" class="form-control" required>
            <option value="">Selecciona un repuesto...</option>
            <?php foreach ($productos as $p):
              $partes = array_filter([$p['MarcaRepuesto'], $p['NumeroParteAlternativo'], $p['NumeroParteOEM'], $p['CodigoBarras']]);
            ?>
              <option value="<?= $p['ProductoID'] ?>"
                      data-plano="<?= htmlspecialchars(mb_strtolower($p['Nombre'] . ' ' . implode(' ', $partes))) ?>"
                      data-norm="<?= htmlspecialchars(normalizarReferencia($p['Nombre'] . implode('', $partes))) ?>">
                <?= htmlspecialchars($p['Nombre']) ?><?= $p['NumeroParteAlternativo'] ? ' · N° ' . htmlspecialchars($p['NumeroParteAlternativo']) : '' ?> — <?= formatCLP($p['PrecioVenta']) ?> (stock: <?= (float)$p['Stock'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="width: 110px;">
          <label>CANTIDAD</label>
          <input type="number" name="cantidad" class="form-control" value="1" min="0.01" step="0.01">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
      </form>

      <h2 style="font-size: 1rem; font-weight: 700; margin: 1.25rem 0 0.75rem;">Agregar mano de obra o tercero</h2>
      <form method="POST" action="presupuesto.php?id=<?= $otId ?>" class="pr-add-row">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="agregar_linea">
        <div style="width: 160px;">
          <label>TIPO</label>
          <select name="tipo" class="form-control">
            <option value="ManoObra">Mano de Obra</option>
            <option value="Terceros">Terceros (ej. tornería)</option>
          </select>
        </div>
        <div style="flex: 1; min-width: 220px;">
          <label>DESCRIPCIÓN</label>
          <input type="text" name="descripcion" class="form-control" placeholder="Ej: Cambio de pastillas de freno" required>
        </div>
        <div style="width: 110px;">
          <label>CANTIDAD</label>
          <input type="number" name="cantidad" class="form-control" value="1" min="0.01" step="0.01">
        </div>
        <div style="width: 140px;">
          <label>PRECIO ($)</label>
          <input type="number" name="precio" class="form-control" placeholder="Ej: 15000" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
      </form>

      <h2 style="font-size: 1rem; font-weight: 700; margin: 1.25rem 0 0.5rem;">Tiempo de entrega estimado</h2>
      <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="display: flex; gap: 0.6rem; max-width: 420px;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="guardar_tiempo_entrega">
        <input type="text" name="tiempo_entrega" class="form-control" placeholder="Ej: 2 días hábiles" value="<?= htmlspecialchars($presupuesto['TiempoEntrega'] ?? '') ?>">
        <button type="submit" class="btn btn-secondary">Guardar</button>
      </form>
    </div>
  <?php endif; ?>

  <div class="pr-section pr-print">
    <?php if ($decidido): ?>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1rem;">
        <div>
          <div style="font-weight: 800; font-size: 1.1rem;"><?= htmlspecialchars($nombreEmpresa ?? 'Taller Mecánico') ?></div>
          <div style="color: var(--text-muted); font-size: 0.85rem;">Presupuesto — <?= htmlspecialchars($folio) ?> — <?= htmlspecialchars($ot['Patente']) ?> <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?></div>
        </div>
        <span class="badge <?= $decisionClase[$presupuesto['DecisionCliente']] ?>" style="font-size: 0.9rem;"><?= $decisionLabel[$presupuesto['DecisionCliente']] ?></span>
      </div>
    <?php else: ?>
      <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem;">Líneas del presupuesto</h2>
    <?php endif; ?>

    <?php if (empty($lineas)): ?>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Todavía no hay líneas agregadas.</p>
    <?php else: ?>
      <form method="POST" action="presupuesto.php?id=<?= $otId ?>" id="formDecision">
        <?= csrfField() ?>
        <div style="overflow-x: auto;">
          <table class="pr-lineas-table">
            <thead>
              <tr>
                <?php if ($pendiente): ?><th></th><?php endif; ?>
                <th>Tipo</th>
                <th>Descripción</th>
                <th>Cant.</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
                <?php if ($decidido): ?><th>Estado</th><?php endif; ?>
                <?php if ($pendiente): ?><th></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lineas as $l): ?>
                <tr>
                  <?php if ($pendiente): ?>
                    <td><input type="checkbox" name="lineas_aprobadas[]" value="<?= $l['PresupuestoDetalleID'] ?>" checked style="width: 1.05rem; height: 1.05rem;"></td>
                  <?php endif; ?>
                  <td><span class="badge <?= $tipoClase[$l['TipoLinea']] ?>"><?= $tipoLabel[$l['TipoLinea']] ?></span></td>
                  <td><?= htmlspecialchars($l['Descripcion']) ?></td>
                  <td><?= rtrim(rtrim(number_format((float)$l['Cantidad'], 3, ',', '.'), '0'), ',') ?></td>
                  <td><?= formatCLP($l['PrecioUnitario']) ?></td>
                  <td style="font-weight: 700;"><?= formatCLP($l['Subtotal']) ?></td>
                  <?php if ($decidido): ?>
                    <td><span class="badge <?= $l['Aprobado'] ? 'badge-success' : 'badge-danger' ?>"><?= $l['Aprobado'] ? 'Aprobada' : 'No aprobada' ?></span></td>
                  <?php endif; ?>
                  <?php if ($pendiente): ?>
                    <td>
                      <button type="submit" form="formEliminar<?= $l['PresupuestoDetalleID'] ?>" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
              <tr class="pr-total-row">
                <td colspan="<?= $pendiente ? 5 : 4 ?>" style="text-align: right;">Total</td>
                <td><?= formatCLP($total) ?></td>
                <?php if ($decidido): ?><td></td><?php endif; ?>
                <?php if ($pendiente): ?><td></td><?php endif; ?>
              </tr>
              <?php if ($decidido && $presupuesto['DecisionCliente'] === 'AprobadoParcial'): ?>
                <tr class="pr-total-row">
                  <td colspan="<?= $decidido ? 4 : 3 ?>" style="text-align: right; color: var(--success);">Total Aprobado</td>
                  <td style="color: var(--success);"><?= formatCLP($totalAprobado) ?></td>
                  <td></td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($presupuesto['TiempoEntrega']): ?>
          <p style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted);">
            <i class="fa-solid fa-clock"></i> Tiempo de entrega estimado: <strong style="color: #fff;"><?= htmlspecialchars($presupuesto['TiempoEntrega']) ?></strong>
          </p>
        <?php endif; ?>

        <?php if ($pendiente): ?>
          <div class="pr-decision-box no-print" style="background: rgba(0,0,0,0.2); border: 1px solid var(--border-dark); border-radius: 10px; padding: 1.25rem;">
            <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.35rem; color: #fff;">
              <i class="fa-solid fa-stamp"></i> ¿Qué respondió el cliente <?= htmlspecialchars($ot['ClienteNombre']) ?>?
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
              Marca en la tabla las líneas que el cliente aceptó y registra su respuesta:
            </p>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
              <button type="submit" name="decision" value="AprobadoTotal" class="btn btn-primary" style="padding: 0.75rem 1.35rem; font-weight: 800; font-size: 0.95rem; background: #10b981; border-color: #059669;">
                <i class="fa-solid fa-circle-check"></i> Aprobó todo
              </button>
              <button type="submit" name="decision" value="AprobadoParcial" class="btn btn-secondary" style="padding: 0.75rem 1.1rem; font-weight: 600;">
                <i class="fa-solid fa-list-check"></i> Aprobó solo lo marcado
              </button>
              <button type="submit" name="decision" value="Rechazado" class="btn btn-secondary" style="padding: 0.75rem 1.1rem; color: #f87171; border-color: rgba(239,68,68,0.4);"
                onclick="return confirm('¿Confirmas que el cliente rechazó este presupuesto?');">
                <i class="fa-solid fa-xmark"></i> Rechazó
              </button>
              <input type="hidden" name="action" value="decidir">
            </div>
          </div>
        <?php endif; ?>
      </form>

      <?php if ($pendiente): ?>
        <?php foreach ($lineas as $l): ?>
          <form method="POST" action="presupuesto.php?id=<?= $otId ?>" id="formEliminar<?= $l['PresupuestoDetalleID'] ?>" style="display: none;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="eliminar_linea">
            <input type="hidden" name="linea_id" value="<?= $l['PresupuestoDetalleID'] ?>">
          </form>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($decidido): ?>
        <div class="no-print" style="margin-top: 1rem; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
          <?php if ($presupuesto['DecisionCliente'] !== 'Rechazado'): ?>
            <a href="ejecucion.php?id=<?= $otId ?>" class="btn btn-primary" style="padding: 0.65rem 1.2rem; font-weight: 700;">
              <i class="fa-solid fa-arrow-right"></i> Siguiente: reparación y cobro
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

<?php endif; ?>

<script>
// Filtro de la lista de repuestos: por nombre, marca o número de parte (sin importar espacios, guiones o barras).
(function () {
  const filtro = document.getElementById('filtroRepuesto');
  const sel = document.getElementById('selRepuesto');
  if (!filtro || !sel) return;
  const norm = s => s.toUpperCase().replace(/[\s\-\/\.]+/g, '');
  const opciones = Array.from(sel.options).filter(o => o.value !== '');
  filtro.addEventListener('input', function () {
    const q = filtro.value.trim();
    const tokens = q.toLowerCase().split(/\s+/).filter(Boolean);
    const qn = norm(q);
    let visibles = [];
    opciones.forEach(o => {
      const coincide = !q || tokens.every(t => (o.dataset.plano || '').includes(t)) || (qn.length >= 3 && (o.dataset.norm || '').includes(qn));
      o.hidden = !coincide;
      o.disabled = !coincide;
      if (coincide) visibles.push(o);
    });
    if (sel.selectedOptions[0] && sel.selectedOptions[0].disabled) sel.value = '';
    if (q && visibles.length === 1) sel.value = visibles[0].value;
    sel.options[0].textContent = !q ? 'Selecciona un repuesto...' : (visibles.length ? visibles.length + ' coincidencia(s): elige una' : 'Ningún repuesto coincide');
  });
})();
</script>
