<?php
$folio = formatFolioOT($ot['OrdenTrabajoID']);
$pendiente = $presupuesto && $presupuesto['DecisionCliente'] === 'Pendiente';
$decidido = $presupuesto && !$pendiente;

require_once __DIR__ . '/../includes/whatsapp_helper.php';
$waMsg = $presupuesto ? mensajePresupuestoWhatsApp($ot, $presupuesto, (float)$tot['total'], obtenerNombreTaller($pdo)) : '';
$waUrl = $presupuesto ? generarUrlWhatsapp($ot['ClienteTelefono'] ?? '', $waMsg) : '';

$grupos = [
    'ManoObra' => ['label' => 'Mano de obra', 'icon' => '🔧'],
    'Repuesto' => ['label' => 'Repuestos', 'icon' => '📦'],
    'Terceros' => ['label' => 'Trabajos de terceros', 'icon' => '🤝'],
];
$origenTag = [
    'Cliente'        => ['Pedido del cliente', 'is-cliente'],
    'Diagnostico'    => ['Diagnóstico', 'is-diag'],
    'Historial'      => ['Usado antes', 'is-hist'],
    'Compatibilidad' => ['Sugerido modelo', 'is-hist'],
];

$fmtCant = fn($c) => rtrim(rtrim(number_format((float)$c, 2, ',', '.'), '0'), ',');


// Qué productos ya están en el presupuesto (para marcar sugerencias)
$enPresupuesto = [];
foreach ($lineas as $l) {
    $enPresupuesto[$l['ProductoID'] ? 'p_' . $l['ProductoID'] : 'd_' . mb_strtolower(trim($l['Descripcion']))] = true;
}

$lineasPorGrupo = [];
foreach ($lineas as $l) $lineasPorGrupo[$l['TipoLinea']][] = $l;
?>
<link rel="stylesheet" href="assets/css/presupuesto.css?v=<?= APP_VERSION ?>">

<!-- ENCABEZADO: auto, cliente y acciones para compartir -->
<div class="pr-head no-print">
  <div>
    <div class="pr-head-car">
      <span class="pr-folio"><?= htmlspecialchars($folio) ?></span>
      <span class="pr-patente"><?= htmlspecialchars($ot['Patente']) ?></span>
      <span class="pr-car-name"><?= htmlspecialchars($ot['Marca'] . ' ' . $ot['Modelo']) ?></span>
      <span class="pr-muted"><?= htmlspecialchars((string)$ot['Anio']) ?><?= !empty($ot['KilometrajeUltimo']) ? ' · ' . number_format($ot['KilometrajeUltimo'], 0, ',', '.') . ' km' : '' ?></span>
    </div>
    <div class="pr-head-client">
      <span><i class="fa-solid fa-user pr-muted"></i> <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong></span>
      <?php if (!empty($ot['ClienteTelefono'])): ?>
        <span class="pr-muted"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($ot['ClienteTelefono']) ?></span>
        <button type="button" class="pr-link-btn" onclick="prModal(true)"><i class="fa-solid fa-pen"></i> Cambiar</button>
      <?php else: ?>
        <button type="button" class="pr-link-btn" style="color:#fbbf24;border-color:rgba(245,158,11,.5)" onclick="prModal(true)"><i class="fa-solid fa-phone-slash"></i> Agregar teléfono</button>
      <?php endif; ?>
    </div>
  </div>
  <div class="pr-head-actions">
    <span class="badge <?= $pendiente ? 'badge-warning' : ($presupuesto['DecisionCliente'] === 'Rechazado' ? 'badge-danger' : 'badge-success') ?>" style="padding:.4rem .7rem">
      <?= htmlspecialchars(otEstadoInfo($ot + ['PresupuestoID' => $presupuesto['PresupuestoID'] ?? null, 'DecisionCliente' => $presupuesto['DecisionCliente'] ?? null, 'CantidadLineasPresupuesto' => count($lineas)])['etiqueta']) ?>
    </span>
    <?php if (!empty($lineas)): ?>
      <span class="pr-save-pill" id="prSavePill" title="Todos los cambios se guardan al instante">
        <i class="fa-solid fa-circle-check" style="color:#10b981"></i> <span>Guardado</span>
      </span>
      <span style="background:rgba(251,191,36,0.12);border:1px solid rgba(251,191,36,0.35);color:#fbbf24;font-weight:800;font-size:0.95rem;padding:.38rem .75rem;border-radius:8px">
        Total <span class="pr-total-val"><?= formatCLP($tot['total']) ?></span>
      </span>
      <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" class="btn pr-btn-wa"><i class="fa-brands fa-whatsapp"></i> Enviar</a>
      <a href="comprobante_presupuesto.php?id=<?= $otId ?>" target="_blank" class="btn btn-secondary"><i class="fa-solid fa-print"></i> PDF</a>
    <?php endif; ?>
    <a href="ordenestrabajo.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> OTs</a>
  </div>
</div>

<?php otStepper($ot, 3); ?>

<div class="pr-layout" id="prRoot">

  <!-- ===== ANTECEDENTES Y DIAGNÓSTICO (INFORMATIVO COMPACTO) ===== -->
  <?php 
    $tieneCliente = !empty(trim($ot['OperacionesTextoLibre'] ?? '')) || !empty($operacionesSolicitadas);
    $tieneHallazgos = !empty($hallazgos) || $observaciones !== '';
  ?>
  <?php if ($tieneCliente || $tieneHallazgos): ?>
    <div class="pr-card pr-info-card no-print">
      <div class="pr-info-header">
        <div class="pr-info-title">
          <i class="fa-solid fa-clipboard-list" style="color:#fbbf24"></i>
          <span>Antecedentes para cotizar</span>
        </div>
        <?php if (!empty($hallazgos)): ?>
          <a href="diagnostico.php?id=<?= $otId ?>" class="pr-info-edit">
            <i class="fa-solid fa-pen"></i> Editar diagnóstico
          </a>
        <?php endif; ?>
      </div>

      <div class="pr-info-grid">
        <?php if ($tieneCliente): ?>
          <div class="pr-info-row">
            <span class="pr-info-lbl">🗣️ Cliente pidió:</span>
            <div class="pr-info-tags">
              <?php if (!empty(trim($ot['OperacionesTextoLibre'] ?? ''))): ?>
                <span class="pr-info-quote">“<?= htmlspecialchars(trim($ot['OperacionesTextoLibre'])) ?>”</span>
              <?php endif; ?>
              <?php foreach ($operacionesSolicitadas as $op): ?>
                <span class="pr-info-pill is-cli"><?= htmlspecialchars($op['NombreOperacion']) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($hallazgos)): ?>
          <div class="pr-info-row">
            <span class="pr-info-lbl">🔍 Hallazgos mecánico:</span>
            <div class="pr-info-tags">
              <?php foreach ($hallazgos as $h): ?>
                <span class="pr-info-pill is-mec">
                  <strong><?= htmlspecialchars($h['Area']) ?>:</strong> <?= htmlspecialchars($h['Hallazgo']) ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($observaciones !== ''): ?>
          <div class="pr-info-row">
            <span class="pr-info-lbl">📝 Observaciones:</span>
            <span style="font-size:0.83rem;color:#cbd5e1"><?= nl2br(htmlspecialchars($observaciones)) ?></span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- ===== EL PRESUPUESTO (ANCHO COMPLETO) ===== -->
  <main>
    <section class="pr-card">
      <div class="pr-card-title">
        <i class="fa-solid fa-file-invoice-dollar" style="color:#38bdf8"></i> Presupuesto
        <span class="pr-muted" id="prCount"><?= count($lineas) ?> <?= count($lineas) === 1 ? 'ítem' : 'ítems' ?></span>
      </div>

      <?php if ($pendiente): ?>
        <!-- AGREGAR ÍTEMS: tres formularios apilados, como los conoce el cliente -->
        <div class="pr-add no-print">

          <div class="pr-add-block">
            <div class="pr-add-title">
              <span><i class="fa-solid fa-box"></i> Agregar repuesto</span>
            </div>
            <?php if (!empty($sugerencias)): ?>
              <div class="pr-chips">
                <span class="pr-chips-label"><i class="fa-solid fa-wand-magic-sparkles"></i> Para este auto:</span>
                <?php foreach (array_slice($sugerencias, 0, 8) as $sg):
                  $ya = isset($enPresupuesto[$sg['producto_id'] ? 'p_' . $sg['producto_id'] : 'd_' . mb_strtolower(trim($sg['descripcion']))]);
                ?>
                  <?php if ($ya): ?>
                    <span class="pr-chip is-added" title="Ya está en el presupuesto"><i class="fa-solid fa-check"></i> <?= htmlspecialchars($sg['descripcion']) ?></span>
                  <?php else: ?>
                    <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="margin:0">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="reutilizar_repuesto_historial">
                      <input type="hidden" name="producto_id" value="<?= $sg['producto_id'] ?>">
                      <input type="hidden" name="descripcion" value="<?= htmlspecialchars($sg['descripcion']) ?>">
                      <input type="hidden" name="precio" value="<?= $sg['precio'] ?>">
                      <input type="hidden" name="tipo" value="<?= htmlspecialchars($sg['tipo']) ?>">
                      <input type="hidden" name="origen" value="<?= $sg['origen'] ?>">
                      <button type="submit" class="pr-chip" title="<?= htmlspecialchars($sg['detalle']) ?><?= $sg['stock'] !== null ? ' · Stock: ' . (float)$sg['stock'] : '' ?>">
                        <i class="fa-solid fa-plus"></i> <?= htmlspecialchars($sg['descripcion']) ?> <b><?= formatCLP($sg['precio']) ?></b>
                      </button>
                    </form>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <input type="text" class="form-control" id="prFiltroRepuesto" autocomplete="off" style="margin-bottom:.5rem"
                   placeholder="Escribe nombre, marca o N° de parte (ej. W 67/1) para filtrar la lista">
            <form method="POST" action="presupuesto.php?id=<?= $otId ?>" class="pr-add-row">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="agregar_repuesto">
              <select name="producto_id" class="form-control" id="prSelectRepuesto" required>
                <option value="">Selecciona un repuesto…</option>
                <?php foreach ($productos as $p): ?>
                  <option value="<?= (int)$p['ProductoID'] ?>" data-busca="<?= htmlspecialchars(implode(' ', array_filter([$p['Nombre'], $p['MarcaRepuesto'], $p['CodigoBarras'], $p['NumeroParteOEM'], $p['NumeroParteAlternativo']]))) ?>">
                    <?= htmlspecialchars($p['Nombre']) ?><?= $p['MarcaRepuesto'] ? ' (' . htmlspecialchars($p['MarcaRepuesto']) . ')' : '' ?> — <?= formatCLP($p['PrecioVenta']) ?> · Stock: <?= (float)$p['Stock'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="number" name="cantidad" class="form-control" value="1" min="0.1" step="any" required title="Cantidad">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
            </form>
          </div>

          <div class="pr-add-block">
            <div class="pr-add-title"><span><i class="fa-solid fa-wrench"></i> Agregar un servicio del catálogo</span> <small class="pr-muted">precio ya definido</small></div>
            <form method="POST" action="presupuesto.php?id=<?= $otId ?>" class="pr-add-row">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="agregar_servicio">
              <select name="servicio_id" class="form-control" required>
                <option value="">Elegir servicio…</option>
                <?php foreach ($servicios as $s): ?>
                  <option value="<?= (int)$s['OperacionID'] ?>"><?= htmlspecialchars($s['Nombre']) ?><?= $s['Categoria'] ? ' (' . htmlspecialchars($s['Categoria']) . ')' : '' ?> — <?= formatCLP($s['PrecioBase']) ?></option>
                <?php endforeach; ?>
              </select>
              <input type="number" name="cantidad" class="form-control" value="1" min="0.1" step="any" required title="Cantidad">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
            </form>
          </div>

          <div class="pr-add-block">
            <div class="pr-add-title"><span><i class="fa-solid fa-pen-to-square"></i> Agregar otra mano de obra o tercero</span> <small class="pr-muted">precio libre</small></div>
            <form method="POST" action="presupuesto.php?id=<?= $otId ?>" class="pr-add-row is-libre">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="agregar_linea">
              <select name="tipo" class="form-control" title="Tipo">
                <option value="ManoObra">Mano de obra</option>
                <option value="Terceros">Terceros</option>
                <option value="Repuesto">Repuesto</option>
              </select>
              <input type="text" name="descripcion" class="form-control" placeholder="Ej: Cambio de pastillas de freno" required>
              <input type="number" name="cantidad" class="form-control" value="1" min="0.1" step="any" required title="Cantidad">
              <input type="number" name="precio" class="form-control" placeholder="Precio $" min="100" step="100" required>
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Agregar</button>
            </form>
          </div>

        </div>
      <?php endif; ?>

      <?php if (empty($lineas)): ?>
        <div class="pr-empty">
          <i class="fa-solid fa-receipt"></i>
          <p style="font-size:.84rem">Agrega repuestos y servicios usando los formularios de arriba.</p>
        </div>
      <?php else: ?>
        <div class="pr-table-wrap">
          <table class="pr-table">
            <thead>
              <tr>
                <th class="col-chk"></th>
                <th>Descripción</th>
                <th class="num" style="width:80px">Cant.</th>
                <th class="num" style="width:130px">Precio unit.</th>
                <th class="num" style="width:110px">Subtotal</th>
                <?php if ($pendiente): ?><th class="col-del"></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($grupos as $tipo => $g): if (empty($lineasPorGrupo[$tipo])) continue; ?>
                <tr class="pr-grp">
                  <td class="col-chk"></td>
                  <td colspan="3"><?= $g['icon'] ?> <?= $g['label'] ?></td>
                  <td class="num" data-grupo="<?= $tipo ?>"><?= formatCLP($tot['grupos'][$tipo]) ?></td>
                  <?php if ($pendiente): ?><td></td><?php endif; ?>
                </tr>
                <?php foreach ($lineasPorGrupo[$tipo] as $l):
                  $lid = (int)$l['PresupuestoDetalleID'];
                  $condicional = $l['PoliticaCobro'] === 'SoloSiNoAprueba';
                  $listaProd = ($l['TipoLinea'] === 'Repuesto' && !empty($l['ProductoID'])) ? ($preciosLista[(int)$l['ProductoID']] ?? null) : null;
                  $precioEditado = $listaProd !== null && $listaProd !== (int)$l['PrecioUnitario'];
                  $tag = $origenTag[$l['Origen'] ?? ''] ?? null;
                  $claseFila = $condicional ? 'pr-row-cond' : (($decidido && !$l['Aprobado']) ? 'pr-row-no' : '');
                ?>
                  <tr id="pr-row-<?= $lid ?>" class="<?= $claseFila ?>" data-subtotal="<?= (int)$l['Subtotal'] ?>">
                    <td class="col-chk">
                      <?php if (!$condicional): ?>
                        <input type="checkbox" class="pr-chk" name="lineas_aprobadas[]" value="<?= $lid ?>" form="prFormDecision" checked>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="pr-desc"><?= htmlspecialchars($l['Descripcion']) ?></div>
                      <?php if ($tag || $condicional || $precioEditado || ($decidido && !$l['Aprobado'] && !$condicional)): ?>
                        <div class="pr-sub">
                          <?php if ($tag): ?><span class="pr-tag <?= $tag[1] ?>"><?= $tag[0] ?></span><?php endif; ?>
                          <?php if ($condicional): ?>
                            <span class="pr-tag is-warn" title="Sin costo si el cliente aprueba la reparación">Se cobra solo si no aprueba · no suma</span>
                            <?php if ($pendiente): ?>
                              <form method="POST" action="presupuesto.php?id=<?= $otId ?>" style="display:inline;margin:0">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="cambiar_politica">
                                <input type="hidden" name="linea_id" value="<?= $lid ?>">
                                <input type="hidden" name="politica" value="Siempre">
                                <button type="submit" class="pr-link-btn" style="font-size:.68rem;padding:0 .4rem">Cobrar siempre</button>
                              </form>
                            <?php endif; ?>
                          <?php endif; ?>
                          <?php if ($precioEditado): ?><span class="pr-tag is-warn" title="Precio de lista <?= formatCLP($listaProd) ?>">Precio editado</span><?php endif; ?>
                          <?php if ($decidido && !$l['Aprobado'] && !$condicional): ?><span class="pr-tag is-no">No aprobado</span><?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <?php if ($pendiente): ?>
                      <td class="num"><input type="number" class="pr-input-num is-qty" value="<?= (float)$l['Cantidad'] ?>" min="0.1" step="any" data-campo="cantidad" data-linea="<?= $lid ?>"></td>
                      <td class="num"><input type="number" class="pr-input-num" value="<?= (int)$l['PrecioUnitario'] ?>" min="1" step="100" data-campo="precio" data-linea="<?= $lid ?>"></td>
                    <?php else: ?>
                      <td class="num"><?= $fmtCant($l['Cantidad']) ?></td>
                      <td class="num"><?= formatCLP($l['PrecioUnitario']) ?></td>
                    <?php endif; ?>
                    <td class="num pr-subtotal"><?= formatCLP($l['Subtotal']) ?></td>
                    <?php if ($pendiente): ?>
                      <td class="col-del"><button type="button" class="pr-del" title="Quitar" data-borrar="<?= $lid ?>"><i class="fa-solid fa-trash-can"></i></button></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="pr-foot">
          <?php if ($pendiente): ?>
            <div class="pr-options no-print">
              <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_combos">
                <label class="pr-switch" title="Si se desactiva, el presupuesto y la Caja van a precio de lista">
                  <input type="checkbox" name="aplica_combos" value="1" <?= $tot['aplicaCombos'] ? 'checked' : '' ?> onchange="this.form.submit()">
                  Aplicar combos y promociones
                </label>
              </form>
              <form method="POST" action="presupuesto.php?id=<?= $otId ?>" class="pr-entrega">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="guardar_tiempo_entrega">
                <i class="fa-regular fa-clock pr-muted"></i>
                <input type="text" name="tiempo_entrega" class="form-control" list="prEntregaOpc" placeholder="Ej: 5 días hábiles"
                       value="<?= htmlspecialchars($presupuesto['TiempoEntrega'] ?? '') ?>" onchange="this.form.submit()">
                <datalist id="prEntregaOpc">
                  <option value="Mismo día">
                  <option value="24 horas">
                  <option value="2 a 3 días hábiles">
                  <option value="5 días hábiles">
                  <option value="Sujeto a llegada de repuestos">
                </datalist>
                <span class="pr-muted" style="font-size:0.75rem">(a contar de la recepción de repuestos y aprobación formal)</span>
              </form>
            </div>
          <?php elseif (!empty($presupuesto['TiempoEntrega'])): 
            $tEnt = trim($presupuesto['TiempoEntrega']);
            $sufEnt = !str_contains(mb_strtolower($tEnt), 'a contar') ? ' (a contar de la recepción de repuestos y aprobación formal)' : '';
          ?>
            <div class="pr-muted" style="font-size:.85rem"><i class="fa-regular fa-clock"></i> Entrega: <strong><?= htmlspecialchars($tEnt) ?></strong><?= htmlspecialchars($sufEnt) ?></div>
          <?php endif; ?>
          <div class="pr-totales-side">
            <div id="prTotales"><?php include __DIR__ . '/partials/presupuesto_totales.php'; ?></div>

            <?php if (!empty($lineas)): ?>
              <div class="pr-foot-actions no-print">
                <div class="pr-save-indicator" id="prSaveIndicator" title="Los cambios se guardan al instante">
                  <i class="fa-solid fa-circle-check" style="color:#10b981"></i>
                  <span>Todo guardado automáticamente</span>
                </div>
                <div class="pr-foot-btns">
                  <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" class="btn pr-btn-wa">
                    <i class="fa-brands fa-whatsapp"></i> Enviar por WhatsApp
                  </a>
                  <a href="comprobante_presupuesto.php?id=<?= $otId ?>" target="_blank" class="btn btn-secondary">
                    <i class="fa-solid fa-print"></i> PDF
                  </a>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <!-- DECISIÓN DEL CLIENTE -->
    <?php if (!empty($lineas)): ?>
      <section class="pr-card no-print">
        <div class="pr-card-title"><i class="fa-solid fa-handshake" style="color:#34d399"></i> ¿Qué respondió <?= htmlspecialchars($ot['ClienteNombre']) ?>?</div>

        <?php if ($pendiente): ?>
          <form method="POST" action="presupuesto.php?id=<?= $otId ?>" id="prFormDecision">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="decidir">
            <div class="pr-decision">
              <button type="submit" name="decision" value="AprobadoTotal" class="pr-dec-btn is-ok">
                <span class="t"><i class="fa-solid fa-check-double"></i> Aprobó todo</span>
                <span class="s">Total <strong class="pr-total-val"><?= formatCLP($tot['total']) ?></strong> · pasa a reparación</span>
              </button>
              <button type="button" class="pr-dec-btn is-part" onclick="prModoParcial(true)">
                <span class="t"><i class="fa-solid fa-list-check"></i> Aprobó solo lo marcado</span>
                <span class="s">Marca qué ítems acepta</span>
              </button>
              <button type="submit" name="decision" value="Rechazado" class="pr-dec-btn is-no" onclick="return confirm('¿Confirmas que el cliente rechazó el presupuesto?')">
                <span class="t"><i class="fa-solid fa-xmark"></i> Rechazó</span>
                <span class="s">No se hace el trabajo</span>
              </button>
            </div>
            <div class="pr-parcial-bar">
              <div>
                <strong><i class="fa-solid fa-hand-pointer"></i> Marca en la tabla los ítems que aprobó</strong>
                <div class="pr-muted" style="font-size:.8rem">Seleccionado: <strong id="prParcialTotal" style="color:var(--text-main)"></strong> · los descuentos se recalculan al confirmar</div>
              </div>
              <div style="display:flex;gap:.5rem">
                <button type="button" class="btn btn-secondary" onclick="prModoParcial(false)">Cancelar</button>
                <button type="submit" name="decision" value="AprobadoParcial" class="btn btn-success" style="font-weight:700"><i class="fa-solid fa-check"></i> Confirmar aprobación</button>
              </div>
            </div>
          </form>
        <?php else: ?>
          <div class="pr-decidido">
            <div>
              <span class="badge <?= $presupuesto['DecisionCliente'] === 'Rechazado' ? 'badge-danger' : 'badge-success' ?>">
                <?= ['AprobadoTotal' => 'Aprobó todo', 'AprobadoParcial' => 'Aprobó solo lo marcado', 'Rechazado' => 'Rechazó'][$presupuesto['DecisionCliente']] ?? htmlspecialchars($presupuesto['DecisionCliente']) ?>
              </span>
              <span class="pr-muted" style="font-size:.82rem">el <?= date('d/m/Y H:i', strtotime($presupuesto['FechaDecision'])) ?></span>
              <div class="pr-muted" style="font-size:.85rem;margin-top:.4rem">Monto aceptado</div>
              <div class="pr-decidido-monto"><?= formatCLP($tot['totalAprobado']) ?></div>
            </div>
            <?php if ($presupuesto['DecisionCliente'] !== 'Rechazado'): ?>
              <a href="ejecucion.php?id=<?= $otId ?>" class="btn btn-primary" style="font-weight:700;padding:.7rem 1.3rem"><i class="fa-solid fa-screwdriver-wrench"></i> Ir a reparación</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
</div>

<?php if ($message || $error): ?>
  <div class="pr-toast <?= $error ? 'is-err' : 'is-ok' ?>" id="prToast">
    <i class="fa-solid <?= $error ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>"></i> <?= htmlspecialchars($error ?: $message) ?>
  </div>
<?php endif; ?>

<!-- Modal teléfono del cliente -->
<div class="pr-modal" id="prModalTel" onclick="if (event.target === this) prModal(false)">
  <div class="pr-modal-box">
    <h3 style="font-size:1.1rem;margin-bottom:.4rem"><i class="fa-brands fa-whatsapp" style="color:#25d366"></i> Teléfono del cliente</h3>
    <p class="pr-muted" style="font-size:.84rem;margin-bottom:1rem">Para enviar el presupuesto por WhatsApp a <strong><?= htmlspecialchars($ot['ClienteNombre']) ?></strong>.</p>
    <form method="POST" action="presupuesto.php?id=<?= $otId ?>">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="actualizar_telefono_cliente">
      <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($ot['ClienteTelefono'] ?? '') ?>" placeholder="Ej: 912345678" required style="font-size:1.05rem;font-weight:700">
      <div class="pr-muted" style="font-size:.74rem;margin:.35rem 0 1rem">Con o sin +56, se normaliza solo.</div>
      <div style="display:flex;justify-content:flex-end;gap:.5rem">
        <button type="button" class="btn btn-secondary" onclick="prModal(false)">Cancelar</button>
        <button type="submit" class="btn pr-btn-wa"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
  window.PR = {
    otId: <?= (int)$otId ?>,
    csrf: <?= json_encode($_SESSION['csrf_token'] ?? '') ?>
  };
</script>
<script src="assets/js/presupuesto.js?v=<?= APP_VERSION ?>"></script>
