<?php
/**
 * Helper de Revisión Técnica (PRT) para Chile
 * Basado en la normativa oficial del Ministerio de Transportes y Telecomunicaciones (MTT)
 * - Decreto Supremo 156 / MTT: Calendario según el último dígito de la patente.
 * - Particulares: Régimen anual (cada 12 meses).
 * - Transporte público, buses, camiones y escolares: Régimen semestral (cada 6 meses).
 * 
 * 100% Automático, sin captchas, 0 ms de latencia y costo $0.
 */

if (!function_exists('obtenerUltimoDigitoPatente')) {

    /**
     * Extrae el último dígito numérico de una patente chilena
     * Ejemplos: 'BBCL20' -> 0, 'KRPX88' -> 8, 'AB1234' -> 4
     */
    function obtenerUltimoDigitoPatente($patente) {
        $clean = strtoupper(trim(preg_replace('/[^A-Z0-9]/', '', (string)$patente)));
        if (preg_match_all('/\d/', $clean, $matches) && !empty($matches[0])) {
            return (int)end($matches[0]);
        }
        return null;
    }

    /**
     * Nombres de meses en español
     */
    function obtenerNombreMesEspanol($mes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[(int)$mes] ?? 'Desconocido';
    }

    /**
     * Devuelve el mes legal base (1 a 12) según el Decreto MTT de Chile
     * 0 -> Febrero (2)
     * 1 -> Abril (4)
     * 2 -> Mayo (5)
     * 3 -> Junio (6)
     * 4 -> Julio (7)
     * 5 -> Agosto (8)
     * 6 -> Septiembre (9)
     * 7 -> Octubre (10)
     * 8 -> Noviembre (11)
     * 9 -> Enero (1)
     */
    function obtenerMesLegalMTT($digito) {
        if ($digito === null || $digito < 0 || $digito > 9) {
            return null;
        }
        $map = [
            0 => 2,  // Febrero
            1 => 4,  // Abril
            2 => 5,  // Mayo
            3 => 6,  // Junio
            4 => 7,  // Julio
            5 => 8,  // Agosto
            6 => 9,  // Septiembre
            7 => 10, // Octubre
            8 => 11, // Noviembre
            9 => 1   // Enero
        ];
        return $map[$digito] ?? null;
    }

    /**
     * Determina si el tipo de vehículo clasifica como pesado, transporte público o carga
     */
    function esVehiculoPesadoOTransporte($tipoVehiculo) {
        $t = mb_strtolower(trim((string)$tipoVehiculo));
        if (empty($t)) return false;
        
        $palabrasPesadas = [
            'camion', 'camión', 'bus', 'micro', 'microbus', 'microbús',
            'tracto', 'tractocamion', 'tractocamión', 'remolque', 'semirremolque',
            'semitrailer', 'minibus', 'minibús', 'escolar', 'transporte', 'chasis'
        ];
        foreach ($palabrasPesadas as $palabra) {
            if (str_contains($t, $palabra)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calcula el último día de un mes en un año específico (Y-m-d)
     */
    function ultimoDiaDelMes($anio, $mes) {
        $fecha = new DateTime(sprintf('%04d-%02d-01', $anio, $mes));
        return $fecha->format('Y-m-t');
    }

    /**
     * Motor principal: Calcula el calendario completo de Revisión Técnica
     * 
     * @param string $patente
     * @param string $tipoVehiculo
     * @param bool $esTransportePublico
     * @param DateTime|null $fechaRef
     * @param string|null $ultimaRevision (Y-m-d)
     * @param string|null $vencimientoManual (Y-m-d)
     * @return array
     */
    function calcularCalendarioPRT($patente, $tipoVehiculo = '', $esTransportePublico = false, $fechaRef = null, $ultimaRevision = null, $vencimientoManual = null) {
        if (!$fechaRef) {
            $fechaRef = new DateTime('now');
        }

        $digito = obtenerUltimoDigitoPatente($patente);
        $esSemestral = (bool)$esTransportePublico || esVehiculoPesadoOTransporte($tipoVehiculo);
        $regimen = $esSemestral ? 'semestral' : 'anual';

        if ($digito === null) {
            return [
                'valido' => false,
                'patente' => $patente,
                'digito' => null,
                'regimen' => $regimen,
                'es_semestral' => $esSemestral,
                'mes1' => null,
                'mes2' => null,
                'meses_texto' => 'No determinado',
                'vencimiento' => null,
                'estado' => 'desconocido',
                'estado_label' => 'Sin dígito legal',
                'badge_color' => 'secondary',
                'badge_icon' => 'fa-solid fa-circle-question',
                'dias_restantes' => null,
                'alerta_comercial' => 'No es posible determinar el dígito legal de la patente.',
                'origen' => 'invalido'
            ];
        }

        $mes1 = obtenerMesLegalMTT($digito);
        $mes2 = $esSemestral ? (($mes1 + 6) > 12 ? ($mes1 + 6 - 12) : ($mes1 + 6)) : null;

        $nombreMes1 = obtenerNombreMesEspanol($mes1);
        $nombreMes2 = $mes2 ? obtenerNombreMesEspanol($mes2) : null;
        $mesesTexto = $esSemestral ? "$nombreMes1 y $nombreMes2" : $nombreMes1;

        $anioActual = (int)$fechaRef->format('Y');
        $hoyStr = $fechaRef->format('Y-m-d');
        $hoyTime = strtotime($hoyStr);

        // Si hay una fecha manual forzada válida
        if (!empty($vencimientoManual) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimientoManual)) {
            $vencStr = $vencimientoManual;
            $vencTime = strtotime($vencStr);
            $dias = (int)round(($vencTime - $hoyTime) / 86400);

            if ($dias < 0) {
                $estado = 'vencida';
                $label = 'Vencida el ' . date('d/m/Y', $vencTime);
                $color = 'danger';
                $icon = 'fa-solid fa-circle-xmark';
                $alerta = "Revisión técnica VENCIDA hace " . abs($dias) . " días. Ofrécele al cliente: Pre-Revisión Técnica y Frenos.";
            } elseif ($dias <= 30) {
                $estado = 'por_vencer';
                $label = 'Vence en ' . $dias . ' día' . ($dias === 1 ? '' : 's') . ' (' . date('d/m/Y', $vencTime) . ')';
                $color = 'warning';
                $icon = 'fa-solid fa-triangle-exclamation';
                $alerta = "Revisión técnica vence este mes. Ofrécele al cliente: Pre-Revisión Técnica y Luces.";
            } else {
                $estado = 'vigente';
                $label = 'Al día (Vence el ' . date('d/m/Y', $vencTime) . ')';
                $color = 'success';
                $icon = 'fa-solid fa-circle-check';
                $alerta = "Revisión técnica al día. Próxima inspección: " . date('d/m/Y', $vencTime);
            }

            return [
                'valido' => true,
                'patente' => $patente,
                'digito' => $digito,
                'regimen' => $regimen,
                'es_semestral' => $esSemestral,
                'mes1' => $mes1,
                'mes2' => $mes2,
                'meses_texto' => $mesesTexto,
                'vencimiento' => $vencStr,
                'ultima_revision' => $ultimaRevision,
                'estado' => $estado,
                'estado_label' => $label,
                'badge_color' => $color,
                'badge_icon' => $icon,
                'dias_restantes' => $dias,
                'alerta_comercial' => $alerta,
                'origen' => 'manual'
            ];
        }

        // CÁLCULO LEGAL MTT
        if (!$esSemestral) {
            // RÉGIMEN ANUAL (Particulares)
            $vencimientoEsteAnio = ultimoDiaDelMes($anioActual, $mes1);
            $vencTimeEsteAnio = strtotime($vencimientoEsteAnio);

            // Verificar si el taller ya registró que se renovó en el ciclo actual
            $renovadoEnCiclo = false;
            if (!empty($ultimaRevision)) {
                $ultTime = strtotime($ultimaRevision);
                // Si la última revisión fue posterior o igual al 1 de enero de este año y el mes actual >= mes legal
                if ($ultTime >= strtotime("$anioActual-01-01") && $ultTime >= $vencTimeEsteAnio - (60 * 86400)) {
                    $renovadoEnCiclo = true;
                }
            }

            if ($renovadoEnCiclo) {
                // Ya renovada para este ciclo: el próximo vencimiento es el año siguiente
                $vencStr = ultimoDiaDelMes($anioActual + 1, $mes1);
            } else {
                $vencStr = $vencimientoEsteAnio;
            }

            $vencTime = strtotime($vencStr);
            $dias = (int)round(($vencTime - $hoyTime) / 86400);

            if ($dias < 0) {
                $estado = 'vencida';
                $label = 'Vencida (' . $nombreMes1 . ' ' . date('Y', $vencTime) . ')';
                $color = 'danger';
                $icon = 'fa-solid fa-circle-xmark';
                $alerta = "Revisión Técnica VENCIDA (Dígito $digito - $nombreMes1). Ofrécele al cliente: Pre-Revisión Técnica, Frenos y Gases.";
            } elseif ($dias <= 31 || (int)$fechaRef->format('n') === $mes1) {
                $estado = 'por_vencer';
                $label = 'Vence este mes (' . $nombreMes1 . ' ' . date('Y', $vencTime) . ')';
                $color = 'warning';
                $icon = 'fa-solid fa-triangle-exclamation';
                $alerta = "Revisión Técnica vence este mes (Dígito $digito - $nombreMes1). Ofrécele al cliente: Pre-Revisión Técnica preventiva.";
            } else {
                $estado = 'vigente';
                $label = 'Al día (Vence en ' . $nombreMes1 . ' ' . date('Y', $vencTime) . ')';
                $color = 'success';
                $icon = 'fa-solid fa-circle-check';
                $alerta = "Revisión Técnica al día hasta $nombreMes1 " . date('Y', $vencTime) . ".";
            }

        } else {
            // RÉGIMEN SEMESTRAL (Camiones, Buses, Taxis, Escolares)
            // Dos ventanas de vencimiento en el año
            $fechasSemestrales = [
                ultimoDiaDelMes($anioActual, $mes1),
                ultimoDiaDelMes($anioActual, $mes2)
            ];
            // Ordenar cronológicamente
            usort($fechasSemestrales, function($a, $b) {
                return strcmp($a, $b);
            });

            // Encontrar el vencimiento aplicable
            $vencStr = null;
            foreach ($fechasSemestrales as $f) {
                if (strtotime($f) >= $hoyTime) {
                    $vencStr = $f;
                    break;
                }
            }

            // Si ambos vencimientos del año ya pasaron, el más próximo venció en el segundo semestre
            if (!$vencStr) {
                $vencStr = end($fechasSemestrales);
            }

            $vencTime = strtotime($vencStr);
            $mesVenc = (int)date('n', $vencTime);
            $nombreMesVenc = obtenerNombreMesEspanol($mesVenc);
            $dias = (int)round(($vencTime - $hoyTime) / 86400);

            if ($dias < 0) {
                $estado = 'vencida';
                $label = 'Semestral Vencida (' . $nombreMesVenc . ' ' . date('Y', $vencTime) . ')';
                $color = 'danger';
                $icon = 'fa-solid fa-circle-xmark';
                $alerta = "Revisión Semestral VENCIDA (Dígito $digito - $nombreMesVenc). Ofrécele: Inspección técnica para vehículos de carga/pasajeros.";
            } elseif ($dias <= 31 || (int)$fechaRef->format('n') === $mesVenc) {
                $estado = 'por_vencer';
                $label = 'Semestral vence este mes (' . $nombreMesVenc . ')';
                $color = 'warning';
                $icon = 'fa-solid fa-triangle-exclamation';
                $alerta = "Revisión Semestral vence este mes ($nombreMesVenc). Ofrécele al transportista: Pre-Revisión Técnica y Frenos de aire.";
            } else {
                $estado = 'vigente';
                $label = 'Semestral al día (Hasta ' . $nombreMesVenc . ' ' . date('Y', $vencTime) . ')';
                $color = 'success';
                $icon = 'fa-solid fa-circle-check';
                $alerta = "Revisión Semestral al día hasta $nombreMesVenc " . date('Y', $vencTime) . ".";
            }
        }

        return [
            'valido' => true,
            'patente' => $patente,
            'digito' => $digito,
            'regimen' => $regimen,
            'es_semestral' => $esSemestral,
            'mes1' => $mes1,
            'mes2' => $mes2,
            'meses_texto' => $mesesTexto,
            'vencimiento' => $vencStr,
            'ultima_revision' => $ultimaRevision,
            'estado' => $estado,
            'estado_label' => $label,
            'badge_color' => $color,
            'badge_icon' => $icon,
            'dias_restantes' => $dias,
            'alerta_comercial' => $alerta,
            'origen' => 'calculo_mtt'
        ];
    }

    /**
     * Sincroniza y persiste el cálculo de PRT en la base de datos para un vehículo
     */
    function sincronizarVehiculoPRT($vehiculoId, $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT Patente, TipoVehiculo, EsTransportePublico, RevisionTecnicaUltima, RevisionTecnicaVencimiento FROM vehiculos WHERE VehiculoID = :id LIMIT 1");
            $stmt->execute([':id' => $vehiculoId]);
            $veh = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$veh) return false;

            $cal = calcularCalendarioPRT(
                $veh['Patente'],
                $veh['TipoVehiculo'] ?? '',
                (bool)($veh['EsTransportePublico'] ?? false),
                null,
                $veh['RevisionTecnicaUltima'] ?? null,
                null
            );

            if ($cal['valido']) {
                $upd = $pdo->prepare("
                    UPDATE vehiculos SET
                        RevisionTecnicaRegimen = :regimen,
                        RevisionTecnicaMes1 = :mes1,
                        RevisionTecnicaMes2 = :mes2,
                        RevisionTecnicaVencimiento = :venc,
                        RevisionTecnicaEstado = :estado,
                        RevisionTecnicaActualizadoEn = NOW()
                    WHERE VehiculoID = :id
                ");
                $upd->execute([
                    ':regimen' => $cal['regimen'],
                    ':mes1' => $cal['mes1'],
                    ':mes2' => $cal['mes2'],
                    ':venc' => $cal['vencimiento'],
                    ':estado' => $cal['estado'],
                    ':id' => $vehiculoId
                ]);
            }
            return $cal;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Marca la revisión técnica como renovada (al día) para el ciclo actual
     */
    function marcarPRTRenovada($vehiculoId, $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT Patente, TipoVehiculo, EsTransportePublico, RevisionTecnicaRegimen, RevisionTecnicaMes1, RevisionTecnicaMes2 FROM vehiculos WHERE VehiculoID = :id LIMIT 1");
            $stmt->execute([':id' => $vehiculoId]);
            $veh = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$veh) return ['success' => false, 'error' => 'Vehículo no encontrado'];

            $hoy = new DateTime('now');
            $hoyStr = $hoy->format('Y-m-d');
            $digito = obtenerUltimoDigitoPatente($veh['Patente']);
            $esSemestral = $veh['RevisionTecnicaRegimen'] === 'semestral' || (bool)$veh['EsTransportePublico'];

            $mes1 = $veh['RevisionTecnicaMes1'] ?: obtenerMesLegalMTT($digito);
            $mes2 = $veh['RevisionTecnicaMes2'] ?: ($mes1 ? (($mes1 + 6) > 12 ? ($mes1 + 6 - 12) : ($mes1 + 6)) : null);

            $anioActual = (int)$hoy->format('Y');

            if (!$esSemestral) {
                // Avanza al siguiente año
                $nuevoVencimiento = ultimoDiaDelMes($anioActual + 1, $mes1);
            } else {
                // En semestral, avanza 6 meses hacia el siguiente ciclo
                $mesActual = (int)$hoy->format('n');
                if ($mesActual <= $mes1) {
                    $nuevoVencimiento = ultimoDiaDelMes($anioActual, $mes2);
                } else {
                    $nuevoVencimiento = ultimoDiaDelMes($anioActual + 1, $mes1);
                }
            }

            $upd = $pdo->prepare("
                UPDATE vehiculos SET
                    RevisionTecnicaUltima = :hoy,
                    RevisionTecnicaVencimiento = :venc,
                    RevisionTecnicaEstado = 'vigente',
                    RevisionTecnicaActualizadoEn = NOW()
                WHERE VehiculoID = :id
            ");
            $upd->execute([
                ':hoy' => $hoyStr,
                ':venc' => $nuevoVencimiento,
                ':id' => $vehiculoId
            ]);

            return [
                'success' => true,
                'mensaje' => 'Revisión Técnica marcada como renovada exitosamente.',
                'nuevo_vencimiento' => $nuevoVencimiento,
                'nuevo_vencimiento_format' => date('d/m/Y', strtotime($nuevoVencimiento))
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Genera el texto del mensaje de WhatsApp para recordar la Revisión Técnica
     */
    function generarMensajeWhatsAppPRT($vehiculo, $nombreTaller = 'Taller Mecánico') {
        $cliente = trim($vehiculo['ClienteNombre'] ?? 'Estimado cliente');
        $marca = trim($vehiculo['Marca'] ?? '');
        $modelo = trim($vehiculo['Modelo'] ?? '');
        $patente = strtoupper(trim($vehiculo['Patente'] ?? ''));
        $meses = $vehiculo['RevisionTecnicaMesesTexto'] ?? 'este mes';
        $vencimiento = !empty($vehiculo['RevisionTecnicaVencimiento']) ? date('d/m/Y', strtotime($vehiculo['RevisionTecnicaVencimiento'])) : $meses;
        $esSemestral = ($vehiculo['RevisionTecnicaRegimen'] ?? '') === 'semestral' || !empty($vehiculo['EsTransportePublico']);

        $saludo = "Hola *{$cliente}*, te saludamos desde *{$nombreTaller}* 🚗🔧";
        if ($esSemestral) {
            $cuerpo = "Te recordamos que tu vehículo comercial/transporte *{$marca} {$modelo}* (Patente *{$patente}*) tiene programada su *Revisión Técnica Semestral* correspondiente a *{$meses}* (vence el {$vencimiento}).\n\n¿Deseas agendar con nosotros una inspección preventiva de frenos, luces, suspensión y gases para asegurar su aprobación ante la fiscalización del MTT?";
        } else {
            $cuerpo = "Te recordamos que tu vehículo *{$marca} {$modelo}* (Patente *{$patente}*) tiene programada su *Revisión Técnica* para el mes de *{$meses}* (vence el {$vencimiento}).\n\n¿Deseas agendar con nosotros una *Pre-Revisión Técnica* preventiva para revisar frenos, luces, tren delantero y emisiones antes de ir a la planta?";
        }

        return "{$saludo}\n\n{$cuerpo}\n\nQuedamos a tu disposición para coordinar tu hora.";
    }
}
