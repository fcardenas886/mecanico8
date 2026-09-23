<?php
$folio = formatFolioOT($ot['OrdenTrabajoID']);
$badgeEstado = 'badge-success';
if ($ot['Estado'] === 'En reparación') $badgeEstado = 'badge-warning';
elseif ($ot['Estado'] === 'Presupuesto rechazado') $badgeEstado = 'badge-danger';

$totalPresupuesto = $totalRepuestos + $totalManoObra;
$cobrado = !empty($ot['VentaID']) || (bool)$ot['ManoObraCobrada']; // el flag antiguo cubre OTs cobradas con el mecanismo previo
$listoParaEntregar = $ot['Estado'] === 'Listo para entregar' || $ot['Estado'] === 'Entregado';
$entregado = $ot['Estado'] === 'Entregado';
$puedeMarcarListo = $totalPresupuesto <= 0 || $cobrado;

$tipoLabel = ['Repuesto' => 'Repuesto', 'ManoObra' => 'Mano de Obra', 'Terceros' => 'Terceros'];
?>
<style>
  .ej-section { background: var(--card-bg); border: 1px solid var(--border-dark); border-radius: var(--radius); padding: 1.25rem 1.5rem; margin-bottom: 1.25rem; }
  .ej-cobro-card { border: 1px solid var(--border-dark); border-radius: 10px; padding: 1rem; }
  .ej-cobro-card.ok { border-color: var(--success); background: rgba(16,185,129,0.08); }
  .ej-cobro-card .monto { font-size: 1.6rem; font-weight: 800; margin: 0.35rem 0 0.75rem; }
  .ej-linea { display: flex; justify-content: space-between; font-size: 0.85rem; padding: 0.3rem 0; border-bottom: 1px dashed var(--border-dark); }
  .ej-linea:last-child { border-bottom: none; }
  .ej-cat-title { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin: 0.75rem 0 0.25rem; }
  .ej-cat-title:first-of-type { margin-top: 0; }
</style>

<div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Reparación, cobro y entrega</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">
      <strong><?= htmlspecialchars($folio) ?></strong> ·
      <code style="font-weight: 700;"><?= htmlspecialchars($ot['Patente']) ?></code>
      <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?> — <?= htmlspecialchars($ot['ClienteNombre']) ?>
    </p>
  </div>
  <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
    <span class="badge <?= $badgeEstado ?>"><?= htmlspecialchars(otEstadoInfo($ot)['etiqueta']) ?></span>
    <a href="comprobante_presupuesto.php?id=<?= $otId ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" target="_blank">
      <i class="fa-solid fa-print"></i> Presupuesto
    </a>
    <a href="sticker_aceite.php?ot=<?= $otId ?>" class="btn" style="background: #f59e0b; color: #000; font-weight: 700; padding: 0.4rem 0.8rem; font-size: 0.85rem;" target="_blank" title="Imprimir etiqueta para parabrisas">
      <i class="fa-solid fa-tag"></i> Sticker Aceite
    </a>
    <a href="ficha_vehiculo.php?id=<?= $ot['VehiculoID'] ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
      <i class="fa-solid fa-car"></i> Historial del vehículo
    </a>
    <a href="ordenestrabajo.php" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
      <i class="fa-solid fa-arrow-left"></i> Órdenes de trabajo
    </a>
  </div>
</div>
<?php otStepper($ot, $entregado || $ot['Estado'] === 'Listo para entregar' ? 5 : 4); ?>
<?php if (!$entregado): ?>
  <?php tallerAyuda('<strong>Qué hago aquí:</strong> 1) asigna al mecánico que hará el trabajo; 2) cuando esté terminado, pulsa <strong>Cobrar en Caja POS</strong> (repuestos y mano de obra van juntos en una sola boleta); 3) marca <strong>Listo para entregar</strong> y, al entregar el vehículo, <strong>Entregado</strong>.'); ?>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['aprobado'])): ?>
  <div style="background: rgba(16, 185, 129, 0.15); border: 2px solid var(--success); color: #34d399; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
    <i class="fa-solid fa-circle-check" style="font-size: 1.5rem;"></i>
    <div>
      <div style="font-weight: 800; font-size: 1.05rem;">¡Presupuesto aprobado con éxito!</div>
      <div style="font-size: 0.85rem; color: #cbd5e1;">El cliente autorizó los trabajos. Ahora puedes asignar el mecánico a cargo para dar inicio a las reparaciones en taller.</div>
    </div>
  </div>
<?php endif; ?>

<?php if ($entregado): ?>
  <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #34d399; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
    <div>
      <div style="font-weight: 700; font-size: 1rem;"><i class="fa-solid fa-circle-check"></i> Vehículo entregado el <?= date('d/m/Y H:i', strtotime($ot['FechaEntrega'])) ?></div>
      <div style="font-size: 0.82rem; color: #a7f3d0; margin-top: 0.2rem;">Los servicios preventivos fueron registrados automáticamente en el historial del vehículo.</div>
      <?php if (!empty($aprendidos)): ?>
        <div style="font-size: 0.82rem; color: #a7f3d0; margin-top: 0.2rem;">
          <i class="fa-solid fa-brain"></i> El sistema recordó qué usa este auto (<?= htmlspecialchars(implode(', ', $aprendidos)) ?>) y lo sugerirá la próxima vez en <a href="buscador_repuestos.php?vehiculo_id=<?= (int)$ot['VehiculoID'] ?>" style="color:#fff; text-decoration: underline;">¿Qué necesita este auto?</a>.
        </div>
      <?php endif; ?>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="sticker_aceite.php?ot=<?= $otId ?>" class="btn" style="background: #f59e0b; color: #000; font-weight: 700; padding: 0.4rem 0.8rem; font-size: 0.82rem;" target="_blank" title="Imprimir etiqueta térmica para parabrisas">
        <i class="fa-solid fa-tag"></i> Imprimir Sticker Aceite
      </a>
      <a href="ficha_vehiculo.php?id=<?= $ot['VehiculoID'] ?>" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.82rem;">
        <i class="fa-solid fa-file-waveform"></i> Ver historial y próximos servicios
      </a>
      <a href="comprobante_presupuesto.php?id=<?= $otId ?>" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.82rem;" target="_blank">
        <i class="fa-solid fa-file-pdf"></i> Ver presupuesto
      </a>
    </div>
  </div>
<?php endif; ?>

<div class="ej-section">
  <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem;">Mecánico asignado</h2>
  <form method="POST" action="ejecucion.php?id=<?= $otId ?>" style="display: flex; gap: 0.6rem; max-width: 420px;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="asignar_mecanico">
    <select name="mecanico_id" class="form-control">
      <option value="">Sin asignar</option>
      <?php foreach ($mecanicos as $m): ?>
        <option value="<?= $m['UsuarioID'] ?>" <?= $ot['MecanicoID'] == $m['UsuarioID'] ? 'selected' : '' ?>><?= htmlspecialchars($m['Nombre']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Guardar</button>
  </form>
</div>

<div class="ej-section">
  <h2 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">Cobro</h2>
  <div class="ej-cobro-card <?= $cobrado ? 'ok' : '' ?>">
    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total a cobrar (repuestos + mano de obra)</div>
    <div class="monto"><?= formatCLP($totalPresupuesto) ?></div>

    <?php if (!empty($lineasRepuesto)): ?>
      <div class="ej-cat-title">Repuestos</div>
      <?php foreach ($lineasRepuesto as $l): ?>
        <div class="ej-linea"><span><?= htmlspecialchars($l['Descripcion']) ?> ×<?= rtrim(rtrim(number_format((float)$l['Cantidad'], 3, ',', '.'), '0'), ',') ?></span><span><?= formatCLP($l['Subtotal']) ?></span></div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($lineasManoObra)): ?>
      <div class="ej-cat-title">Mano de Obra / Terceros</div>
      <?php foreach ($lineasManoObra as $l): ?>
        <div class="ej-linea"><span><?= htmlspecialchars($tipoLabel[$l['TipoLinea']]) ?>: <?= htmlspecialchars($l['Descripcion']) ?></span><span><?= formatCLP($l['Subtotal']) ?></span></div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($totalPresupuesto <= 0): ?>
      <p style="color: var(--text-muted); font-size: 0.82rem; margin-top: 0.5rem;">No hay líneas aprobadas que cobrar.</p>
    <?php elseif (!empty($ot['VentaID'])): ?>
      <p style="color: var(--success); font-size: 0.85rem; margin-top: 0.75rem;"><i class="fa-solid fa-circle-check"></i> Cobrado — Venta #<?= $ot['VentaID'] ?> (ver en <a href="ventas.php" style="color: var(--success); text-decoration: underline;">Ventas y Comprobantes</a>)</p>
    <?php elseif ($ot['ManoObraCobrada']): ?>
      <p style="color: var(--success); font-size: 0.85rem; margin-top: 0.75rem;"><i class="fa-solid fa-circle-check"></i> Cobrado (registrado con el mecanismo anterior de esta OT).</p>
    <?php else: ?>
      <a href="pos.php?cargar_ot=<?= $otId ?>" class="btn btn-primary" style="margin-top: 0.75rem; display: inline-block; padding: 0.6rem 1rem; font-size: 0.9rem;">
        <i class="fa-solid fa-cart-shopping"></i> Cobrar en Caja POS
      </a>
      <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.5rem;">
        Carga repuestos y mano de obra juntos al carrito del POS — una sola venta, con boleta/factura incluyendo todo.
      </p>
    <?php endif; ?>
  </div>
</div>

<div class="ej-section" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
  <?php if (!$listoParaEntregar): ?>
    <form method="POST" action="ejecucion.php?id=<?= $otId ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="marcar_listo">
      <button type="submit" class="btn btn-primary" style="padding: 0.7rem 1.3rem;" <?= $puedeMarcarListo ? '' : 'disabled' ?>>
        <i class="fa-solid fa-check"></i> Marcar Listo para Entregar
      </button>
    </form>
    <?php if (!$puedeMarcarListo): ?>
      <span style="color: var(--text-muted); font-size: 0.85rem;">Cobra la OT en caja primero.</span>
    <?php endif; ?>
  <?php elseif (!$entregado): ?>
    <form method="POST" action="ejecucion.php?id=<?= $otId ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="marcar_entregado">
      <button type="submit" class="btn btn-primary" style="padding: 0.7rem 1.3rem;">
        <i class="fa-solid fa-key"></i> Marcar Entregado
      </button>
    </form>
    <?php
      require_once __DIR__ . '/../includes/whatsapp_helper.php';
      $saldoPendiente = (!empty($ot['VentaID']) || !empty($ot['ManoObraCobrada'])) ? 0 : (float)$totalPresupuesto;
      $msgWAAutoListo = mensajeAutoListoWhatsApp($ot, $saldoPendiente, obtenerNombreTaller($pdo));
      $urlWAAutoListo = generarUrlWhatsapp($ot['ClienteTelefono'] ?? '', $msgWAAutoListo);
    ?>
    <?php if ($urlWAAutoListo): ?>
      <a href="<?= $urlWAAutoListo ?>" target="_blank" class="btn" style="background: #16a34a; color: #ffffff; padding: 0.7rem 1.2rem;" title="Notificar por WhatsApp que el auto está listo para retiro">
        <i class="fa-brands fa-whatsapp"></i> Avisar al Cliente: Auto Listo
      </a>
    <?php endif; ?>
    <a href="sticker_aceite.php?ot=<?= $otId ?>" target="_blank" class="btn" style="background: #f59e0b; color: #000; font-weight: 700; padding: 0.7rem 1.2rem;" title="Imprimir etiqueta para parabrisas">
      <i class="fa-solid fa-tag"></i> Imprimir Sticker Aceite
    </a>
  <?php else: ?>
    <span style="color: var(--text-muted); font-size: 0.9rem;">Esta OT ya fue entregada.</span>
    <a href="sticker_aceite.php?ot=<?= $otId ?>" target="_blank" class="btn" style="background: #f59e0b; color: #000; font-weight: 700; padding: 0.5rem 1rem;" title="Imprimir etiqueta para parabrisas">
      <i class="fa-solid fa-tag"></i> Imprimir Sticker Aceite
    </a>
  <?php endif; ?>
</div>
