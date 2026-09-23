<?php
tallerEstiloUI();

// Clasifica cada orden y cuenta por grupo para las pestañas de la vista lista.
$filas = [];
$conteo = ['activas' => 0, 'diagnosticar' => 0, 'presupuestar' => 0, 'esperando' => 0, 'reparacion' => 0, 'retirar' => 0, 'entregadas' => 0, 'todas' => 0];
foreach ($ordenes as $o) {
    $inf = otEstadoInfo($o);
    $filas[] = [$o, $inf];
    $conteo['todas']++;
    $conteo[$inf['grupo']]++;
    if ($inf['grupo'] !== 'entregadas') $conteo['activas']++;
}

$pestanas = [
    'activas'      => ['En curso', 'Todo lo que aún no se entrega'],
    'diagnosticar' => ['Por diagnosticar', 'Vehículos recibidos que falta revisar'],
    'presupuestar' => ['Por presupuestar', 'Diagnóstico listo, falta armar el presupuesto'],
    'esperando'    => ['Esperando al cliente', 'Presupuesto enviado, falta su respuesta'],
    'reparacion'   => ['En reparación', 'Aprobados, en reparación o por cobrar'],
    'retirar'      => ['Listos para retirar', 'Terminados, esperando al cliente'],
    'entregadas'   => ['Entregadas', 'Órdenes ya terminadas'],
    'todas'        => ['Todas', ''],
];
$filtro = $_GET['f'] ?? ($q !== '' ? 'todas' : 'activas');
if (!isset($pestanas[$filtro])) $filtro = 'activas';

$visibles = array_filter($filas, function ($par) use ($filtro) {
    return $filtro === 'todas' || ($filtro === 'activas' ? $par[1]['grupo'] !== 'entregadas' : $par[1]['grupo'] === $filtro);
});
?>
<style>
  .ot-tabs { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 1rem; }
  .ot-tab { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.85rem; border-radius: 999px; border: 1px solid var(--border-dark); color: var(--text-muted); font-size: 0.85rem; font-weight: 600; text-decoration: none; background: var(--card-bg); }
  .ot-tab:hover { border-color: var(--primary); color: var(--text); }
  .ot-tab.active { background: var(--primary); border-color: var(--primary); color: #fff; }
  .ot-tab .n { background: rgba(255,255,255,0.15); border-radius: 999px; padding: 0 0.45rem; font-size: 0.75rem; }
  .ot-tab:not(.active) .n { background: rgba(255,255,255,0.08); }
  .ot-guia summary { cursor: pointer; font-weight: 600; font-size: 0.9rem; color: #93c5fd; list-style: none; }
  .ot-guia summary::-webkit-details-marker { display: none; }
  .ot-guia ol { margin: 0.75rem 0 0 1.2rem; padding: 0; font-size: 0.88rem; line-height: 1.6; color: var(--text-muted); }
  .ot-guia ol strong { color: var(--text); }
  .ot-vehiculo { font-weight: 700; }
  .ot-vehiculo a { color: #38bdf8; text-decoration: none; letter-spacing: 0.03em; }
  .ot-sub { color: var(--text-muted); font-size: 0.82rem; }
  .ot-accion { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; }
  .ot-mas summary { cursor: pointer; font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem; list-style: none; }
  .ot-mas summary::-webkit-details-marker { display: none; }
  .ot-mas summary:hover { color: var(--text); }
  .ot-mas .links { display: flex; flex-direction: column; gap: 0.25rem; margin-top: 0.4rem; font-size: 0.82rem; }
  .ot-mas .links a { color: #93c5fd; text-decoration: none; }
  .ot-mas .links a:hover { text-decoration: underline; }
</style>

<!-- Barra Superior con Título y Botón de Recepción -->
<div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 700;">Órdenes de trabajo</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem;">Cada fila es un vehículo en el taller. El botón de la derecha te dice qué hacer a continuación.</p>
  </div>
  <a href="ordeningreso.php" class="btn btn-primary" style="padding: 0.7rem 1.2rem;">
    <i class="fa-solid fa-plus"></i> Recibir un vehículo
  </a>
</div>

<details class="ts-help ot-guia" style="display: block;">
  <summary><i class="fa-solid fa-circle-info" style="margin-right: 0.4rem;"></i> ¿Cómo funciona? (los 5 pasos de cada orden)</summary>
  <ol>
    <li><strong>Recepción:</strong> anotas cliente, vehículo y en qué estado llega. Se imprime un comprobante.</li>
    <li><strong>Diagnóstico:</strong> el mecánico revisa y anota lo que encuentra. Si el cliente ya sabe qué necesita, se puede saltar.</li>
    <li><strong>Presupuesto:</strong> se detallan repuestos y mano de obra con precio; el cliente aprueba todo, una parte o nada.</li>
    <li><strong>Reparación y cobro:</strong> se asigna al mecánico y se cobra en caja lo aprobado.</li>
    <li><strong>Entrega:</strong> se avisa al cliente y se le entrega el vehículo.</li>
  </ol>
</details>

<form method="GET" style="margin-bottom: 1rem; display: flex; gap: 0.5rem; max-width: 460px;">
  <input type="hidden" name="f" value="<?= htmlspecialchars($filtro) ?>">
  <input type="text" name="q" class="form-control" placeholder="Buscar por patente, cliente o N° de orden..." value="<?= htmlspecialchars($q) ?>">
  <button type="submit" class="btn btn-secondary" title="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="ot-tabs">
  <?php foreach ($pestanas as $clave => [$nombre, $desc]):
    if ($clave === 'entregadas' || $clave === 'todas' || $clave === 'activas' || $conteo[$clave] > 0 || $filtro === $clave): ?>
    <a class="ot-tab <?= $filtro === $clave ? 'active' : '' ?>" href="?f=<?= $clave ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" title="<?= htmlspecialchars($desc) ?>">
      <?= htmlspecialchars($nombre) ?> <span class="n"><?= $conteo[$clave] ?></span>
    </a>
  <?php endif; endforeach; ?>
</div>

  <div class="table-card">
    <table class="table">
      <thead>
        <tr>
          <th>Orden</th>
          <th>Vehículo y cliente</th>
          <th>Mecánico</th>
          <th>Situación</th>
          <th>Qué sigue</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($visibles)): ?>
          <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
            <?php if ($conteo['todas'] === 0): ?>
              Aún no hay órdenes. Empieza con <a href="ordeningreso.php" style="color: #93c5fd;">Recibir un vehículo</a>.
            <?php else: ?>
              No hay órdenes en esta categoría.
            <?php endif; ?>
          </td></tr>
        <?php else: ?>
          <?php foreach ($visibles as [$ot, $inf]):
            $id = (int)$ot['OrdenTrabajoID'];
            $tienePresupuesto = !empty($ot['PresupuestoID']) && (int)($ot['CantidadLineasPresupuesto'] ?? 0) > 0;
            [$txtAccion, $urlAccion, $claseAccion] = $inf['accion'];
          ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars(formatFolioOT($id)) ?></strong>
                <div class="ot-sub"><?= date('d/m/Y H:i', strtotime($ot['FechaIngreso'])) ?></div>
              </td>
              <td>
                <div class="ot-vehiculo">
                  <a href="ficha_vehiculo.php?id=<?= $ot['VehiculoID'] ?>" title="Ver historial del vehículo"><?= htmlspecialchars($ot['Patente']) ?></a>
                  <?= htmlspecialchars($ot['Marca']) ?> <?= htmlspecialchars($ot['Modelo']) ?>
                </div>
                <div class="ot-sub"><?= htmlspecialchars($ot['ClienteNombre']) ?></div>
              </td>
              <td>
                <?php if (!empty($ot['MecanicoNombre'])): ?>
                  <span style="font-size: 0.85rem; font-weight: 600; color: #38bdf8;">👨‍🔧 <?= htmlspecialchars($ot['MecanicoNombre']) ?></span>
                <?php else: ?>
                  <span style="color: var(--text-muted); font-size: 0.82rem; font-style: italic;">Sin asignar</span>
                <?php endif; ?>
              </td>
              <td>
                <?= otMiniProgreso($inf) ?>
                <span class="badge <?= $inf['badge'] ?>"><?= htmlspecialchars($inf['etiqueta']) ?></span>
                <div class="ot-sub" style="margin-top: 3px;">
                  <?= htmlspecialchars($inf['ayuda']) ?>
                  <?php if ($inf['grupo'] === 'esperando'): ?>
                    · <strong style="color: #fbbf24;"><?= formatCLP($ot['TotalPresupuesto']) ?></strong>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div class="ot-accion">
                  <a href="<?= htmlspecialchars($urlAccion) ?>" class="btn <?= $claseAccion ?>" style="padding: 0.45rem 0.9rem; font-size: 0.85rem; font-weight: 700; <?= $claseAccion === 'btn-warning' ? 'background:#f59e0b; color:#000; border:none;' : '' ?>">
                    <?= htmlspecialchars($txtAccion) ?> <i class="fa-solid fa-arrow-right" style="font-size: 0.75rem;"></i>
                  </a>
                  <?php
                    if ($inf['grupo'] === 'esperando' && !empty($ot['ClienteTelefono'])):
                      $msgWAPres = mensajePresupuestoWhatsApp($ot, ['TiempoEntrega' => ''], (float)($ot['TotalPresupuesto'] ?? 0), obtenerNombreTaller());
                      $urlWAPres = generarUrlWhatsapp($ot['ClienteTelefono'], $msgWAPres);
                  ?>
                    <?php if ($urlWAPres): ?>
                      <a href="<?= $urlWAPres ?>" target="_blank" class="btn" style="background: #25d366; color: #fff; padding: 0.45rem 0.75rem; font-size: 0.82rem; border: none;" title="Enviar el presupuesto al cliente por WhatsApp">
                        <i class="fa-brands fa-whatsapp"></i> Presupuesto
                      </a>
                    <?php endif; ?>
                  <?php elseif ($inf['grupo'] === 'retirar' && !empty($ot['ClienteTelefono'])):
                      $msgWAListo = mensajeAutoListoWhatsApp($ot, 0, obtenerNombreTaller());
                      $urlWAListo = generarUrlWhatsapp($ot['ClienteTelefono'], $msgWAListo);
                  ?>
                    <?php if ($urlWAListo): ?>
                      <a href="<?= $urlWAListo ?>" target="_blank" class="btn" style="background: #25d366; color: #fff; padding: 0.45rem 0.75rem; font-size: 0.82rem; border: none;" title="Avisar al cliente que su auto está listo por WhatsApp">
                        <i class="fa-brands fa-whatsapp"></i> Avisar
                      </a>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
                <details class="ot-mas">
                  <summary><i class="fa-solid fa-folder-open"></i> Ver documentos y pasos anteriores</summary>
                  <div class="links">
                    <a href="comprobante_ot.php?id=<?= $id ?>"><i class="fa-solid fa-file-lines"></i> Comprobante de recepción</a>
                    <?php if ($inf['paso'] > 2 || $inf['grupo'] === 'entregadas'): ?>
                      <a href="diagnostico.php?id=<?= $id ?>"><i class="fa-solid fa-stethoscope"></i> Ver diagnóstico</a>
                    <?php endif; ?>
                    <?php if ($tienePresupuesto): ?>
                      <a href="presupuesto.php?id=<?= $id ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Ver presupuesto</a>
                      <a href="comprobante_presupuesto.php?id=<?= $id ?>" target="_blank"><i class="fa-solid fa-file-pdf"></i> Presupuesto para imprimir</a>
                    <?php endif; ?>
                    <a href="sticker_aceite.php?ot=<?= $id ?>" target="_blank"><i class="fa-solid fa-tag"></i> Sticker cambio de aceite</a>
                    <a href="ficha_vehiculo.php?id=<?= $ot['VehiculoID'] ?>"><i class="fa-solid fa-car-side"></i> Historial del vehículo</a>
                  </div>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

