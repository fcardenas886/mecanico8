<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.75rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Promociones y Ofertas Especiales</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Configuración de descuentos por volumen, precios de oferta y fechas de vigencia</p>
  </div>
  <div style="display: flex; gap: 0.6rem;">
    <button onclick="document.getElementById('comboModal').style.display='flex'" class="btn btn-secondary" style="border-color: rgba(129,140,248,0.4); color: #a5b4fc;">
      <i class="fa-solid fa-boxes-packing"></i> Nuevo Combo
    </button>
    <button onclick="document.getElementById('promoModal').style.display='flex'" class="btn btn-primary">
      <i class="fa-solid fa-tags"></i> Nueva Oferta
    </button>
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

<div class="table-card">
  <div class="table-header">
    <h2 style="font-size: 1.1rem; font-weight: 600;">Listado de Ofertas Vigentes</h2>
    <span style="color: var(--text-muted); font-size: 0.85rem;"><?= count($promociones) ?> promociones</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>N°</th>
        <th>Producto</th>
        <th>Tipo Oferta</th>
        <th>Detalle de Oferta / Descuento</th>
        <th>Vigencia</th>
        <th>Estado</th>
        <th style="text-align: center; width: 100px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($promociones)): ?>
        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay promociones registradas.</td></tr>
      <?php else: ?>
        <?php foreach ($promociones as $pr): ?>
          <?php
            // Determinar descripción formateada en base al tipo de promoción
            if ($pr['Tipo'] === 'DESCUENTO_UNIT') {
                $tipoLabel = 'Descuento Unitario';
                $badgeClass = 'badge-success';
                $detalleHtml = '<strong style="color: var(--success);">' . number_format($pr['DescuentoPorcentaje'], 0) . '% de Descuento</strong>';
            } else { // MULTIBUY
                $tipoLabel = 'Volumen / Pack';
                $badgeClass = 'badge-warning';
                $detalleHtml = 'Llevar <strong style="color: #fff;">' . number_format($pr['CantidadMinima'], 0) . '</strong> por <strong style="color: var(--success);">' . formatCLP($pr['PrecioOferta']) . '</strong>';
            }
          ?>
          <tr>
            <td>#<?= $pr['PromocionID'] ?></td>
            <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($pr['ProductoName']) ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $tipoLabel ?></span></td>
            <td><?= $detalleHtml ?></td>
            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d/m/Y', strtotime($pr['FechaInicio'])) ?> al <?= date('d/m/Y', strtotime($pr['FechaFin'])) ?></td>
            <td>
              <span class="badge <?= $pr['Activa'] ? 'badge-success' : 'badge-danger' ?>">
                <?= $pr['Activa'] ? 'Activa' : 'Inactiva' ?>
              </span>
            </td>
            <td style="text-align: center;">
              <form method="POST" action="promociones.php" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar esta promoción?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="eliminar">
                <input type="hidden" name="promo_id" value="<?= $pr['PromocionID'] ?>">
                <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" title="Eliminar Promoción">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="table-card" style="margin-top: 1.5rem;">
  <div class="table-header">
    <h2 style="font-size: 1.1rem; font-weight: 600;"><i class="fa-solid fa-boxes-packing"></i> Combos por Tipo de Repuesto</h2>
    <span style="color: var(--text-muted); font-size: 0.85rem;"><?= count($combos) ?> combos</span>
  </div>
  <p style="padding: 0 1.25rem 0.75rem; margin: 0; font-size: 0.82rem; color: var(--text-muted);">
    En vez de un producto, cada cupo pide un <strong>tipo de repuesto</strong> (Aceite, Filtro de Aceite...) o un producto puntual.
    Así, una sola regla "1 Aceite + 1 Filtro de Aceite" se aplica sola a cualquier marca que el cliente lleve, sin crear una promoción por cada combinación.
    Se aplica automático en la Caja y no se acumula con el descuento individual del producto.
  </p>

  <table class="table">
    <thead>
      <tr>
        <th>Combo</th>
        <th>Cupos requeridos</th>
        <th>Descuento</th>
        <th>Vigencia</th>
        <th>Estado</th>
        <th style="text-align: center; width: 130px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($combos)): ?>
        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay combos creados todavía.</td></tr>
      <?php else: ?>
        <?php foreach ($combos as $c): ?>
          <tr>
            <td style="font-weight: 600; color: #fff;">#<?= $c['ComboID'] ?> <?= htmlspecialchars($c['Nombre']) ?></td>
            <td style="font-size: 0.85rem;">
              <?php foreach ($c['cupos'] as $i => $cu): ?>
                <?= $i > 0 ? ' <span style="color: var(--text-muted);">+</span> ' : '' ?>
                <span class="badge badge-warning" style="font-size: 0.72rem;">
                  <?= rtrim(rtrim(number_format((float)$cu['CantidadRequerida'], 3, ',', '.'), '0'), ',') ?>×
                  <?= $cu['ModoSeleccion'] === 'PRODUCTO_ESPECIFICO' ? htmlspecialchars($cu['ProductoNombre'] ?? '(producto eliminado)') : htmlspecialchars($tiposRepuestoList[$cu['TipoRepuesto']] ?? $cu['TipoRepuesto']) ?>
                </span>
              <?php endforeach; ?>
            </td>
            <td>
              <?php if ($c['TipoDescuento'] === 'PORCENTAJE'): ?>
                <strong style="color: var(--success);"><?= number_format($c['ValorDescuento'], 0) ?>% de descuento</strong>
              <?php elseif ($c['TipoDescuento'] === 'MONTO_FIJO'): ?>
                <strong style="color: var(--success);">-<?= formatCLP($c['ValorDescuento']) ?></strong>
              <?php else: ?>
                <strong style="color: var(--success);">Pack en <?= formatCLP($c['ValorDescuento']) ?></strong>
              <?php endif; ?>
            </td>
            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= date('d/m/Y', strtotime($c['FechaInicio'])) ?> al <?= date('d/m/Y', strtotime($c['FechaFin'])) ?></td>
            <td>
              <form method="POST" action="promociones.php" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="activar_combo">
                <input type="hidden" name="combo_id" value="<?= $c['ComboID'] ?>">
                <button type="submit" class="badge <?= $c['Activa'] ? 'badge-success' : 'badge-danger' ?>" style="border: none; cursor: pointer;">
                  <?= $c['Activa'] ? 'Activo' : 'Inactivo' ?>
                </button>
              </form>
            </td>
            <td style="text-align: center;">
              <form method="POST" action="promociones.php" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este combo?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="eliminar_combo">
                <input type="hidden" name="combo_id" value="<?= $c['ComboID'] ?>">
                <button type="submit" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);" title="Eliminar Combo">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal Nuevo Combo -->
<div id="comboModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 560px; max-width: 100%; max-height: 90vh; overflow-y: auto; padding: 1.75rem; box-shadow: var(--shadow-lg);">
    <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem; color: #a5b4fc;"><i class="fa-solid fa-boxes-packing"></i> Crear Combo</h2>

    <form method="POST" action="promociones.php" id="formCombo" style="display: flex; flex-direction: column; gap: 1rem;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="crear_combo">

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">NOMBRE DEL COMBO *</label>
        <input type="text" name="combo_nombre" class="form-control" placeholder="Ej: Pack Mantención Aceite + Filtro" required>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CUPOS REQUERIDOS (mínimo 2) *</label>
        <div id="cuposContainer" style="display: flex; flex-direction: column; gap: 0.6rem; margin-top: 0.4rem;"></div>
        <button type="button" onclick="agregarCupo()" class="btn btn-secondary" style="margin-top: 0.5rem; padding: 0.4rem 0.8rem; font-size: 0.82rem;">
          <i class="fa-solid fa-plus"></i> Agregar cupo
        </button>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TIPO DE DESCUENTO *</label>
        <select name="combo_tipo_descuento" id="comboTipoDescuento" class="form-control" onchange="toggleComboDescuento()" required>
          <option value="PORCENTAJE">% de descuento sobre lo que se lleve</option>
          <option value="MONTO_FIJO">Monto fijo de descuento ($)</option>
          <option value="PRECIO_FIJO">Precio cerrado del combo ($) — solo si son productos puntuales</option>
        </select>
        <input type="number" step="0.1" name="combo_valor_descuento" id="comboValorDescuento" class="form-control" placeholder="Ej: 10" style="margin-top: 0.5rem;" required>
        <small id="comboValorHint" style="color: var(--text-muted); font-size: 0.72rem;">Ej: 10 = 10% de descuento en los ítems que arman el combo.</small>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">FECHA INICIO</label>
          <input type="date" name="combo_fecha_inicio" value="<?= date('Y-m-d') ?>" class="form-control" required>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">FECHA FIN</label>
          <input type="date" name="combo_fecha_fin" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="form-control" required>
        </div>
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Guardar Combo</button>
        <button type="button" onclick="document.getElementById('comboModal').style.display='none'" class="btn btn-secondary btn-block">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
const TIPOS_REPUESTO = <?= json_encode($tiposRepuestoList) ?>;
const PRODUCTOS_LIST = <?= json_encode(array_map(fn($p) => ['id' => $p['ProductoID'], 'nombre' => $p['Nombre']], $productosList)) ?>;
let cupoContador = 0;

function agregarCupo() {
  const id = cupoContador++;
  const container = document.getElementById('cuposContainer');
  const row = document.createElement('div');
  row.id = `cupoRow${id}`;
  row.style.cssText = 'display:flex; gap:0.5rem; align-items:flex-end; background: rgba(255,255,255,0.03); border: 1px solid var(--border-dark); border-radius: 8px; padding: 0.5rem;';

  const opcionesTipos = Object.entries(TIPOS_REPUESTO).map(([k, v]) => `<option value="${k}">${v}</option>`).join('');
  const opcionesProductos = PRODUCTOS_LIST.map(p => `<option value="${p.id}">${escapeHtmlCombo(p.nombre)}</option>`).join('');

  row.innerHTML = `
    <div style="width: 90px;">
      <label style="font-size: 0.7rem; color: var(--text-muted);">CANT.</label>
      <input type="number" step="0.001" min="0.001" name="cupo_cantidad[]" class="form-control" value="1" style="padding: 0.4rem;">
    </div>
    <div style="width: 130px;">
      <label style="font-size: 0.7rem; color: var(--text-muted);">MODO</label>
      <select name="cupo_modo[]" class="form-control" style="padding: 0.4rem;" onchange="toggleCupoModo(${id}, this.value)">
        <option value="TIPO_REPUESTO">Tipo de repuesto</option>
        <option value="PRODUCTO_ESPECIFICO">Producto puntual</option>
      </select>
    </div>
    <div style="flex: 1;" id="cupoValorWrap${id}">
      <label style="font-size: 0.7rem; color: var(--text-muted);">CUÁL</label>
      <select name="cupo_tipo[]" id="cupoTipo${id}" class="form-control" style="padding: 0.4rem;">${opcionesTipos}</select>
      <select name="cupo_producto[]" id="cupoProducto${id}" class="form-control" style="padding: 0.4rem; display: none;">${opcionesProductos}</select>
    </div>
    <button type="button" onclick="document.getElementById('cupoRow${id}').remove()" class="btn btn-secondary" style="padding: 0.4rem 0.6rem;" title="Quitar cupo">
      <i class="fa-solid fa-xmark"></i>
    </button>
  `;
  container.appendChild(row);
}

function toggleCupoModo(id, modo) {
  document.getElementById(`cupoTipo${id}`).style.display = modo === 'TIPO_REPUESTO' ? 'block' : 'none';
  document.getElementById(`cupoProducto${id}`).style.display = modo === 'PRODUCTO_ESPECIFICO' ? 'block' : 'none';
}

function escapeHtmlCombo(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function toggleComboDescuento() {
  const tipo = document.getElementById('comboTipoDescuento').value;
  const hint = document.getElementById('comboValorHint');
  if (tipo === 'PORCENTAJE') hint.textContent = 'Ej: 10 = 10% de descuento en los ítems que arman el combo.';
  else if (tipo === 'MONTO_FIJO') hint.textContent = 'Ej: 4000 = se descuentan $4.000 del total de esos ítems.';
  else hint.textContent = 'Ej: 39990 = el combo completo queda en $39.990 en total. Úsalo solo con cupos de "Producto puntual", porque el precio de lista varía entre marcas. Ojo: con precio cerrado, la cantidad debe ser EXACTA (si el cupo pide 1 y el cliente lleva 2, no se arma el combo).';
}

// Arrancar el formulario con 2 cupos vacíos, que es el mínimo.
agregarCupo();
agregarCupo();
toggleComboDescuento();
</script>

<!-- Modal Nueva Oferta -->
<div id="promoModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center;">
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 16px; width: 450px; padding: 1.75rem; box-shadow: var(--shadow-lg);">
    <h2 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.25rem; color: #818cf8;">Crear Promoción</h2>
    
    <form method="POST" action="promociones.php" style="display: flex; flex-direction: column; gap: 1rem;">
      <?= csrfField() ?>
      
      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PRODUCTO EN OFERTA *</label>
        <select name="producto_id" class="form-control" required>
          <option value="">-- Selecciona Producto --</option>
          <?php foreach ($productosList as $p): ?>
            <option value="<?= $p['ProductoID'] ?>"><?= htmlspecialchars($p['Nombre']) ?> (Precio: <?= formatCLP($p['PrecioVenta']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">TIPO DE OFERTA *</label>
        <select id="promoTipo" name="tipo" class="form-control" onchange="togglePromoFields()" required>
          <option value="DESCUENTO_UNIT">Descuento Unitario (Porcentaje %)</option>
          <option value="MULTIBUY">Promoción por Volumen (Pack / Llevar X por $Y)</option>
        </select>
      </div>

      <!-- Campo para Descuento Unitario (Porcentaje) -->
      <div id="divDescuentoUnit">
        <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PORCENTAJE DE DESCUENTO (%) *</label>
        <input type="number" step="0.1" name="descuento_porcentaje" id="inputDescPorc" class="form-control" placeholder="Ej: 15" value="0">
      </div>

      <!-- Campos para Multibuy (Volumen) -->
      <div id="divMultibuy" style="display: none; grid-template-columns: 1fr 1.2fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">CANT. MÍNIMA *</label>
          <input type="number" step="1" name="cantidad_minima" id="inputCantMin" class="form-control" placeholder="Ej: 3" value="3">
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PRECIO OFERTA ($) *</label>
          <input type="number" name="precio_oferta" id="inputPrecioOf" class="form-control" placeholder="Ej: 4000" value="0">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">FECHA INICIO</label>
          <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" class="form-control" required>
        </div>
        <div>
          <label style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">FECHA FIN</label>
          <input type="date" name="fecha_fin" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="form-control" required>
        </div>
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Guardar Oferta</button>
        <button type="button" onclick="document.getElementById('promoModal').style.display='none'" class="btn btn-secondary btn-block">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
function togglePromoFields() {
  const tipo = document.getElementById('promoTipo').value;
  const divUnit = document.getElementById('divDescuentoUnit');
  const divMulti = document.getElementById('divMultibuy');
  const inputPorc = document.getElementById('inputDescPorc');
  const inputCant = document.getElementById('inputCantMin');
  const inputPrice = document.getElementById('inputPrecioOf');

  if (tipo === 'DESCUENTO_UNIT') {
    divUnit.style.display = 'block';
    divMulti.style.display = 'none';
    inputPorc.required = true;
    inputCant.required = false;
    inputPrice.required = false;
  } else {
    divUnit.style.display = 'none';
    divMulti.style.display = 'grid';
    inputPorc.required = false;
    inputCant.required = true;
    inputPrice.required = true;
  }
}
// Ejecutar una vez al inicio
togglePromoFields();
</script>
