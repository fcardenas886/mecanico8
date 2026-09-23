<?php
/**
 * Helper de mensajería y enlaces inteligentes para WhatsApp.
 * Especializado en números móviles chilenos (+56 9 XXXX XXXX).
 */

if (!function_exists('obtenerNombreTaller')) {
    function obtenerNombreTaller($pdo = null): string {
        static $nombre = null;
        if ($nombre === null) {
            if (!$pdo && function_exists('getDB')) {
                $pdo = getDB();
            }
            if ($pdo) {
                try {
                    $stmt = $pdo->query("SELECT Valor FROM configuraciones WHERE Clave IN ('EMPRESA_NOMBRE', 'nombre_empresa') AND Valor != '' LIMIT 1");
                    $val = $stmt ? $stmt->fetchColumn() : false;
                    if ($val) $nombre = $val;
                } catch (\Exception $e) {}
            }
            if (!$nombre) $nombre = 'Taller Mecánico Pro';
        }
        return $nombre;
    }
}

if (!function_exists('normalizarTelefonoChile')) {
    function normalizarTelefonoChile(?string $telefono): string {
        if (!$telefono) return '';
        $digits = preg_replace('/\D/', '', $telefono);
        if ($digits === '') return '';

        // Si ya tiene código de país chileno 569XXXXXXXX (11 dígitos)
        if (str_starts_with($digits, '569') && strlen($digits) === 11) {
            return $digits;
        }

        // Si tiene 56 y 9 dígitos más (11 dígitos)
        if (str_starts_with($digits, '56') && strlen($digits) === 11) {
            return $digits;
        }

        // Si es formato móvil chileno estándar de 9 dígitos empezando con 9 (9XXXXXXXX)
        if (strlen($digits) === 9 && str_starts_with($digits, '9')) {
            return '56' . $digits;
        }

        // Si ingresaron 8 dígitos (sin el 9 inicial)
        if (strlen($digits) === 8) {
            return '569' . $digits;
        }

        // Si empieza con 56 y tiene más de 9 dígitos
        if (str_starts_with($digits, '56') && strlen($digits) >= 10) {
            return $digits;
        }

        // Caso general si tiene 9 dígitos
        if (strlen($digits) === 9) {
            return '56' . $digits;
        }

        return '56' . $digits;
    }
}

if (!function_exists('generarUrlWhatsapp')) {
    function generarUrlWhatsapp(?string $telefono, string $mensaje): string {
        $tel = normalizarTelefonoChile($telefono);
        if (empty($tel)) return '';
        return 'https://wa.me/' . $tel . '?text=' . rawurlencode($mensaje);
    }
}

if (!function_exists('mensajePresupuestoWhatsApp')) {
    function mensajePresupuestoWhatsApp(array $ot, array $presupuesto, float $totalPresupuesto, string $nombreTaller): string {
        $folio = function_exists('formatFolioOT') ? formatFolioOT($ot['OrdenTrabajoID']) : '#' . $ot['OrdenTrabajoID'];
        $cliente = trim($ot['ClienteNombre'] ?? 'Estimado(a) cliente');
        $auto = trim(($ot['Marca'] ?? '') . ' ' . ($ot['Modelo'] ?? '') . ' (' . ($ot['Patente'] ?? '') . ')');

        $msg = "Hola {$cliente} 👋, te saludamos de *{$nombreTaller}*.\n\n";
        $msg .= "Te compartimos el detalle del *Presupuesto* para tu vehículo *{$auto}*:\n";
        $msg .= "📄 *Folio OT:* {$folio}\n";
        $msg .= "💰 *Monto Total:* $" . number_format($totalPresupuesto, 0, ',', '.') . " CLP\n";

        if (!empty($presupuesto['TiempoEntrega'])) {
            $msg .= "⏱️ *Tiempo estimado de entrega:* " . $presupuesto['TiempoEntrega'] . "\n";
        }

        $msg .= "\nPuedes responder a este mensaje indicándonos si *apruebas* los trabajos para dar inicio a la reparación de inmediato. ¡Muchas gracias!";
        return $msg;
    }
}

if (!function_exists('mensajeRecepcionWhatsApp')) {
    function mensajeRecepcionWhatsApp(array $ot, string $nombreTaller): string {
        $folio = function_exists('formatFolioOT') ? formatFolioOT($ot['OrdenTrabajoID']) : '#' . $ot['OrdenTrabajoID'];
        $cliente = trim($ot['ClienteNombre'] ?? 'Estimado(a) cliente');
        $auto = trim(($ot['Marca'] ?? '') . ' ' . ($ot['Modelo'] ?? '') . ' (' . ($ot['Patente'] ?? '') . ')');
        $km = !empty($ot['KilometrajeIngreso']) ? number_format((int)$ot['KilometrajeIngreso'], 0, ',', '.') . ' km' : 'No registrado';

        $msg = "Hola {$cliente} 👋, confirmamos la recepción de tu vehículo en *{$nombreTaller}*.\n\n";
        $msg .= "📋 *Orden de Ingreso:* {$folio}\n";
        $msg .= "🚗 *Vehículo:* {$auto}\n";
        $msg .= "📟 *Kilometraje:* {$km}\n";
        if (!empty($ot['MotivoIngreso'])) {
            $msg .= "🔧 *Motivo:* " . $ot['MotivoIngreso'] . "\n";
        }
        $msg .= "\nTu vehículo ha quedado bajo nuestra custodia. Te mantendremos informado del diagnóstico y avances. ¡Gracias por confiar en nosotros!";
        return $msg;
    }
}

if (!function_exists('mensajeAutoListoWhatsApp')) {
    function mensajeAutoListoWhatsApp(array $ot, float $saldoPendiente, string $nombreTaller): string {
        $folio = function_exists('formatFolioOT') ? formatFolioOT($ot['OrdenTrabajoID']) : '#' . $ot['OrdenTrabajoID'];
        $cliente = trim($ot['ClienteNombre'] ?? 'Estimado(a) cliente');
        $auto = trim(($ot['Marca'] ?? '') . ' ' . ($ot['Modelo'] ?? '') . ' (' . ($ot['Patente'] ?? '') . ')');

        $msg = "¡Buenas noticias, {$cliente}! 🎉\n\n";
        $msg .= "Tu vehículo *{$auto}* ya está *LISTO PARA RETIRAR* en *{$nombreTaller}*.\n";
        $msg .= "📄 *Orden de Trabajo:* {$folio}\n";

        if ($saldoPendiente > 0) {
            $msg .= "💳 *Saldo a pagar:* $" . number_format($saldoPendiente, 0, ',', '.') . " CLP\n";
        } else {
            $msg .= "✅ *Estado de pago:* Pagado totalmente\n";
        }

        $msg .= "\nPuedes pasar a retirarlo en nuestro horario habitual de atención. ¡Te esperamos!";
        return $msg;
    }
}

if (!function_exists('mensajeAlertaMantenimientoWhatsApp')) {
    function mensajeAlertaMantenimientoWhatsApp(array $cliente, array $vehiculo, array $mantenimiento, string $nombreTaller): string {
        $nombre = trim($cliente['Nombre'] ?? 'Estimado(a) cliente');
        $auto = trim(($vehiculo['Marca'] ?? '') . ' ' . ($vehiculo['Modelo'] ?? '') . ' (' . ($vehiculo['Patente'] ?? '') . ')');
        $servicio = $mantenimiento['TipoMantenimiento'] ?? 'Mantenimiento Preventivo';

        $msg = "Hola {$nombre} 👋, te recordamos desde *{$nombreTaller}*:\n\n";
        $msg .= "Tu vehículo *{$auto}* tiene programado su próximo servicio de:\n";
        $msg .= "🔧 *{$servicio}*\n";

        if (!empty($mantenimiento['KilometrajeProximo'])) {
            $msg .= "📟 *Kilometraje sugerido:* " . number_format((int)$mantenimiento['KilometrajeProximo'], 0, ',', '.') . " km\n";
        }
        if (!empty($mantenimiento['FechaProxima'])) {
            $msg .= "📅 *Fecha límite recomendada:* " . date('d/m/Y', strtotime($mantenimiento['FechaProxima'])) . "\n";
        }

        $msg .= "\nPara asegurar el óptimo rendimiento de tu motor y evitar averías mayores, te invitamos a agendar tu cita respondiendo a este mensaje. ¡Estamos a tu disposición!";
        return $msg;
    }
}
