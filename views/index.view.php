<!-- Taller: qué hay que hacer hoy -->
<?php
$tg = $tallerRes['por_grupo'];
$tarjetasTaller = [
    ['diagnosticar', 'Por diagnosticar', 'fa-stethoscope', '#60a5fa'],
    ['presupuestar', 'Por presupuestar', 'fa-file-invoice-dollar', '#a78bfa'],
    ['esperando', 'Esperando al cliente', 'fa-hourglass-half', '#fbbf24'],
    ['reparacion', 'En reparación o por cobrar', 'fa-screwdriver-wrench', '#f97316'],
    ['retirar', 'Listos para retirar', 'fa-key', '#34d399'],
];
?>
<div class="table-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
    <div>
      <h2 style="font-size: 1.1rem; font-weight: 700; margin: 0;"><i class="fa-solid fa-wrench" style="color: #f59e0b;"></i> Taller: qué hay que hacer hoy</h2>
      <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0.2rem 0 0;">
        <?= (int)$tallerRes['total_activas'] === 0 ? 'No hay vehículos en el taller ahora.' : (int)$tallerRes['total_activas'] . ' vehículo' . ((int)$tallerRes['total_activas'] === 1 ? '' : 's') . ' en el taller. Pulsa una tarjeta para ver esas órdenes.' ?>
      </p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="ordeningreso.php" class="btn btn-primary" style="padding: 0.6rem 1.1rem;"><i class="fa-solid fa-plus"></i> Recibir un vehículo</a>
      <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.6rem 1.1rem;">Ver todas las órdenes</a>
    </div>
  </div>
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.75rem;">
    <?php foreach ($tarjetasTaller as [$clave, $titulo, $icono, $color]): ?>
      <a href="ordenestrabajo.php?f=<?= $clave ?>" style="text-decoration: none; color: inherit; border: 1px solid var(--border-dark); border-left: 4px solid <?= $color ?>; border-radius: 10px; padding: 0.85rem 1rem; display: block; <?= $tg[$clave] > 0 ? '' : 'opacity: 0.6;' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 1.7rem; font-weight: 800;"><?= (int)$tg[$clave] ?></span>
          <i class="fa-solid <?= $icono ?>" style="color: <?= $color ?>;"></i>
        </div>
        <div style="font-size: 0.82rem; font-weight: 600; margin-top: 0.15rem;"><?= $titulo ?></div>
        <?php if ($clave === 'esperando' && $tallerRes['monto_esperando'] > 0): ?>
          <div style="font-size: 0.75rem; color: #fbbf24;"><?= formatCLP($tallerRes['monto_esperando']) ?> por aprobar</div>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Tarjetas de Estadísticas -->
<div class="grid-stats">
  <div class="stat-card">
    <div class="stat-info">
      <h3>Ventas del Día</h3>
      <div class="stat-value"><?= formatCLP($statsVentas['total_monto']) ?></div>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
        <?= $statsVentas['total_ventas'] ?> transacciones completadas
      </p>
    </div>
    <div class="stat-icon">
      <i class="fa-solid fa-sack-dollar"></i>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-info">
      <h3>Estado de Caja</h3>
      <div class="stat-value" style="font-size: 1.35rem;">
        <?php if ($turnoActivo): ?>
          <span style="color: var(--success);"><i class="fa-solid fa-circle-check"></i> Turno Abierto</span>
        <?php else: ?>
          <span style="color: var(--warning);"><i class="fa-solid fa-lock"></i> Turno Cerrado</span>
        <?php endif; ?>
      </div>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
        <?= $turnoActivo ? 'Abierto a las ' . date('H:i', strtotime($turnoActivo['FechaApertura'])) : 'Abre caja para vender' ?>
      </p>
    </div>
    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">
      <i class="fa-solid fa-cash-register"></i>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-info">
      <h3>Alertas de Stock</h3>
      <div class="stat-value" style="color: <?= $lowStockCount > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>;">
        <?= $lowStockCount ?>
      </div>
      <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.25rem;">
        <a href="alertas_stock.php" style="color: var(--danger); text-decoration: none;">Ver sugerencias &rarr;</a>
      </p>
    </div>
    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger);">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
  </div>
</div>

<!-- Accesos Rápidos & Últimas Ventas -->
<div style="display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem;">
  
  <!-- Accesos directos -->
  <div class="table-card" style="padding: 1.25rem;">
    <h2 style="font-size: 1.1rem; margin-bottom: 1rem; font-weight: 600;">Módulos del Sistema</h2>
    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
      <a href="pos.php" class="btn btn-primary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-cart-shopping" style="font-size: 1.1rem;"></i> Caja Registradora (POS)
      </a>
      <?php if ($esSupervisorNav): ?>
      <a href="compras.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-truck-ramp-box" style="font-size: 1.1rem;"></i> Recepción de Compras
      </a>
      <?php endif; ?>
      <a href="caja.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-vault" style="font-size: 1.1rem;"></i> Turnos y Arqueo de Caja
      </a>
      <?php if ($esSupervisorNav): ?>
      <a href="productos.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-boxes-stacked" style="font-size: 1.1rem;"></i> Catálogo de Productos
      </a>
      <?php endif; ?>
      <a href="clientes.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-users" style="font-size: 1.1rem;"></i> Clientes y Puntos
      </a>
      <a href="alertas_stock.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.1rem;"></i> Sugerencias de Reabastecimiento
      </a>
      <a href="ventas.php" class="btn btn-secondary" style="justify-content: flex-start; padding: 0.75rem 1rem;">
        <i class="fa-solid fa-receipt" style="font-size: 1.1rem;"></i> Historial de Ventas
      </a>
    </div>
  </div>

  <!-- Últimas Ventas -->
  <div class="table-card">
    <div class="table-header">
      <h2 style="font-size: 1.1rem; font-weight: 600;">Últimas Ventas Registradas</h2>
      <a href="ventas.php" style="color: #818cf8; text-decoration: none; font-size: 0.85rem;">Ver todas &rarr;</a>
    </div>
    <table class="table">
      <thead>
        <tr>
          <th>ID Venta</th>
          <th>Fecha / Hora</th>
          <th>Documento</th>
          <th>Total</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ultimasVentas)): ?>
          <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay ventas registradas aún.</td></tr>
        <?php else: ?>
          <?php foreach ($ultimasVentas as $v): ?>
            <tr>
              <td>#<?= $v['VentaID'] ?></td>
              <td><?= date('d/m/Y H:i', strtotime($v['FechaVenta'])) ?></td>
              <td><?= htmlspecialchars($v['TipoDocumento']) ?></td>
              <td style="font-weight: 700; color: var(--success);"><?= formatCLP($v['MontoTotal']) ?></td>
              <td>
                <span class="badge <?= $v['Estado'] == 'Completada' ? 'badge-success' : 'badge-danger' ?>">
                  <?= $v['Estado'] ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
