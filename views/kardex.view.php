<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Kardex de Movimientos de Inventario</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Trazabilidad completa de entradas, salidas, ventas, compras y ajustes de stock</p>
  </div>

  <form method="GET" action="kardex.php" style="display: flex; gap: 0.5rem;">
    <select name="producto_id" class="form-control" onchange="this.form.submit()" style="padding: 0.4rem 0.85rem; font-size: 0.9rem;">
      <option value="0">-- Todos los productos --</option>
      <?php foreach ($productosList as $p): ?>
        <option value="<?= $p['ProductoID'] ?>" <?= $productoID == $p['ProductoID'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['Nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($productoInfo): ?>
  <?php 
    $ultimoMov = !empty($kardexList) ? $kardexList[0] : null;
    $ultimoSaldo = $ultimoMov ? (float)$ultimoMov['StockSaldo'] : 0.0;
    $stockActual = (float)$productoInfo['Stock'];
    $cuadra = abs($stockActual - $ultimoSaldo) < 0.001;
  ?>
  <div style="background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
    <div>
      <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">ESTADO DE STOCK AUDITADO</div>
      <div style="font-size: 1.15rem; font-weight: 700; color: #fff; margin-top: 0.2rem;">
        <?= htmlspecialchars($productoInfo['Nombre']) ?>
      </div>
      <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
        Costo: <?= formatCLP($productoInfo['CostoCompra']) ?> | Venta: <?= formatCLP($productoInfo['PrecioVenta']) ?> | Stock Mínimo: <?= $productoInfo['StockMinimo'] ?>
      </div>
    </div>
    <div style="display: flex; gap: 1.5rem; align-items: center;">
      <div style="text-align: right;">
        <div style="font-size: 0.75rem; color: var(--text-muted);">Stock Actual (Catálogo)</div>
        <div style="font-size: 1.3rem; font-weight: 800; color: var(--primary);"><?= $stockActual ?></div>
      </div>
      <div style="text-align: right;">
        <div style="font-size: 0.75rem; color: var(--text-muted);">Último Saldo Kardex</div>
        <div style="font-size: 1.3rem; font-weight: 800; color: #fff;"><?= $ultimoSaldo ?></div>
      </div>
      <div style="padding: 0.4rem 0.85rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; background: <?= $cuadra ? 'rgba(34, 197, 94, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $cuadra ? '#4ade80' : '#f87171' ?>; border: 1px solid <?= $cuadra ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.3)' ?>;">
        <i class="fa-solid <?= $cuadra ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
        <?= $cuadra ? 'Stock Cuadrado al 100%' : 'Descuadre Detectado' ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="table-card">
  <div class="table-header">
    <h2 style="font-size: 1.1rem; font-weight: 600;">Historial de Movimientos de Stock</h2>
    <span style="color: var(--text-muted); font-size: 0.85rem;"><?= count($kardexList) ?> registros de Kardex</span>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>N° Mov.</th>
        <th>Fecha / Hora</th>
        <th>Producto</th>
        <th>Tipo Transacción</th>
        <th>Documento / Ref.</th>
        <th>Entrada</th>
        <th>Salida</th>
        <th>Saldo Stock</th>
        <th>Valor Unit.</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($kardexList)): ?>
        <tr><td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay registros en Kardex.</td></tr>
      <?php else: ?>
        <?php foreach ($kardexList as $k): ?>
          <?php
            $docRef = '-';
            if (!empty($k['VentaID'])) {
              $docRef = '<a href="ventas.php" style="color: var(--primary); text-decoration: none;"><i class="fa-solid fa-receipt"></i> Venta #' . $k['VentaID'] . '</a>';
            } elseif (!empty($k['CompraID'])) {
              $docRef = '<a href="compras.php" style="color: var(--primary); text-decoration: none;"><i class="fa-solid fa-truck"></i> Compra #' . $k['CompraID'] . '</a>';
            } elseif (!empty($k['AjusteStockID'])) {
              $docRef = '<a href="ajustes.php" style="color: var(--primary); text-decoration: none;"><i class="fa-solid fa-sliders"></i> Ajuste #' . $k['AjusteStockID'] . '</a>';
            } elseif (!empty($k['DevolucionID'])) {
              $docRef = '<a href="devoluciones.php" style="color: var(--primary); text-decoration: none;"><i class="fa-solid fa-arrow-rotate-left"></i> Devolución #' . $k['DevolucionID'] . '</a>';
            } elseif ($k['TipoTransaccion'] === 'INICIAL') {
              $docRef = '<span style="color: var(--text-muted); font-size: 0.8rem;"><i class="fa-solid fa-flag"></i> Saldo Inicial</span>';
            }
          ?>
          <tr>
            <td>#<?= $k['KardexID'] ?></td>
            <td><?= date('d/m/Y H:i', strtotime($k['FechaMovimiento'])) ?></td>
            <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($k['ProductoName']) ?></td>
            <td>
              <span class="badge <?= in_array($k['TipoTransaccion'], ['COMPRA', 'INICIAL', 'AJUSTE_ENTRADA', 'ANULACION_VENTA']) ? 'badge-success' : 'badge-danger' ?>">
                <?= $k['TipoTransaccion'] ?>
              </span>
            </td>
            <td><?= $docRef ?></td>
            <td style="color: var(--success); font-weight: bold;"><?= $k['CantidadEntrada'] > 0 ? '+' . $k['CantidadEntrada'] : '-' ?></td>
            <td style="color: var(--danger); font-weight: bold;"><?= $k['CantidadSalida'] > 0 ? '-' . $k['CantidadSalida'] : '-' ?></td>
            <td style="font-weight: 700; color: #fff;"><?= $k['StockSaldo'] ?></td>
            <td><?= formatCLP($k['ValorUnitario']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
