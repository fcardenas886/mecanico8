<?php
// Bloque de totales del presupuesto. Lo usa la vista y la respuesta AJAX de editar/eliminar línea.
// Requiere: $tot (totalesPresupuesto) y $pendiente.
?>
<div class="pr-totales">
  <?php if ($tot['combos']['descuento'] > 0): ?>
    <div class="pr-tot-row"><span>Subtotal</span><span><?= formatCLP($tot['subtotal']) ?></span></div>
    <?php foreach ($tot['combos']['promos'] ?? [] as $pm): ?>
      <div class="pr-tot-row is-desc"><span>🏷️ Oferta «<?= htmlspecialchars($pm['nombre']) ?>»</span><span>-<?= formatCLP($pm['monto']) ?></span></div>
    <?php endforeach; ?>
    <?php foreach ($tot['combos']['combos'] ?? [] as $cb): ?>
      <div class="pr-tot-row is-desc"><span>🏷️ Combo «<?= htmlspecialchars($cb['nombre']) ?>»</span><span>-<?= formatCLP($cb['monto']) ?></span></div>
    <?php endforeach; ?>
  <?php endif; ?>
  <div class="pr-tot-row is-total"><span>Total</span><span><?= formatCLP($tot['total']) ?></span></div>
</div>
