<?php
/**
 * Dominio: Gestión de tokens de acceso seguro para documentos públicos (WhatsApp / Enlaces directos).
 * Permite a los clientes consultar su Presupuesto, Comprobante de Custodia o Entrega sin requerir inicio de sesión
 * y protegiendo el sistema de accesos no autorizados o escaneos por IDs correlativos.
 */

if (!function_exists('asegurarTablaTokensDocumentos')) {
    function asegurarTablaTokensDocumentos(PDO $pdo): void {
        static $verificada = false;
        if ($verificada) return;
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `tokens_documentos` (
                  `TokenID` int NOT NULL AUTO_INCREMENT,
                  `Token` varchar(16) NOT NULL,
                  `TipoDocumento` varchar(30) NOT NULL COMMENT 'presupuesto, ingreso, entrega, venta',
                  `ReferenciaID` int NOT NULL COMMENT 'OrdenTrabajoID o VentaID',
                  `FechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `UltimoAcceso` datetime DEFAULT NULL,
                  PRIMARY KEY (`TokenID`),
                  UNIQUE KEY `UQ_TokensDoc_Token` (`Token`),
                  KEY `IX_TokensDoc_Ref` (`TipoDocumento`, `ReferenciaID`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            $verificada = true;
        } catch (\Exception $e) {
            // No interrumpir si ya existe o por permisos
        }
    }
}

if (!function_exists('generarTokenCortoAleatorio')) {
    /**
     * Genera un token corto alfanumérico legible (8 caracteres).
     * Excluye 0, O, 1, I para evitar ambigüedades en pantallas móviles.
     */
    function generarTokenCortoAleatorio(int $longitud = 8): string {
        $alfabeto = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $alfabetoLen = strlen($alfabeto);
        $token = '';
        $bytes = random_bytes($longitud);
        for ($i = 0; $i < $longitud; $i++) {
            $token .= $alfabeto[ord($bytes[$i]) % $alfabetoLen];
        }
        return $token;
    }
}

if (!function_exists('obtenerOCrearTokenDocumento')) {
    /**
     * Obtiene el token existente o genera uno nuevo para un documento dado.
     * @param PDO $pdo Conexión a la base de datos
     * @param string $tipoDocumento 'presupuesto' | 'ingreso' | 'entrega' | 'venta'
     * @param int $referenciaId OrdenTrabajoID o VentaID
     * @return string Token único de 8 caracteres
     */
    function obtenerOCrearTokenDocumento(PDO $pdo, string $tipoDocumento, int $referenciaId): string {
        asegurarTablaTokensDocumentos($pdo);

        $stmt = $pdo->prepare("
            SELECT Token 
            FROM tokens_documentos 
            WHERE TipoDocumento = :tipo AND ReferenciaID = :ref 
            LIMIT 1
        ");
        $stmt->execute([':tipo' => $tipoDocumento, ':ref' => $referenciaId]);
        $tokenExistente = $stmt->fetchColumn();

        if ($tokenExistente) {
            return (string)$tokenExistente;
        }

        // Generar un nuevo token garantizando unicidad
        $intentos = 0;
        do {
            $nuevoToken = generarTokenCortoAleatorio(8);
            $check = $pdo->prepare("SELECT 1 FROM tokens_documentos WHERE Token = :t LIMIT 1");
            $check->execute([':t' => $nuevoToken]);
            $colision = (bool)$check->fetchColumn();
            $intentos++;
        } while ($colision && $intentos < 10);

        $insert = $pdo->prepare("
            INSERT INTO tokens_documentos (Token, TipoDocumento, ReferenciaID) 
            VALUES (:token, :tipo, :ref)
        ");
        $insert->execute([
            ':token' => $nuevoToken,
            ':tipo' => $tipoDocumento,
            ':ref' => $referenciaId,
        ]);

        return $nuevoToken;
    }
}

if (!function_exists('validarTokenDocumento')) {
    /**
     * Valida un token corto recibido por URL y actualiza su fecha de último acceso.
     * @param PDO $pdo Conexión a BD
     * @param string $token Código alfanumérico
     * @return array|null Fila del token con TipoDocumento y ReferenciaID, o null si no es válido
     */
    function validarTokenDocumento(PDO $pdo, string $token): ?array {
        $tokenLimpio = strtoupper(trim($token));
        if (strlen($tokenLimpio) < 6 || strlen($tokenLimpio) > 16) {
            return null;
        }

        asegurarTablaTokensDocumentos($pdo);

        $stmt = $pdo->prepare("
            SELECT TokenID, Token, TipoDocumento, ReferenciaID, FechaCreacion, UltimoAcceso
            FROM tokens_documentos 
            WHERE Token = :t 
            LIMIT 1
        ");
        $stmt->execute([':t' => $tokenLimpio]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        // Registrar último acceso de forma asíncrona / no bloqueante
        try {
            $pdo->prepare("UPDATE tokens_documentos SET UltimoAcceso = NOW() WHERE TokenID = :id")
                ->execute([':id' => $row['TokenID']]);
        } catch (\Exception $e) {}

        return $row;
    }
}

if (!function_exists('obtenerUrlBasePublica')) {
    /**
     * Resuelve la URL base absoluta del sistema para los enlaces públicos.
     * Soporta detección automática de HTTPS/HTTP y subdirectorios de Laragon / Apache.
     */
    function obtenerUrlBasePublica(?PDO $pdo = null): string {
        if (!$pdo && function_exists('getDB')) {
            try {
                $pdo = getDB();
            } catch (\Exception $e) {}
        }
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT Valor FROM configuraciones WHERE Clave = 'APP_URL_BASE' AND Valor != '' LIMIT 1");
                $urlConfig = $stmt ? trim((string)$stmt->fetchColumn()) : '';
                if (!empty($urlConfig)) {
                    return rtrim($urlConfig, '/');
                }
            } catch (\Exception $e) {}
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $protocolo = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (empty($host) || str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
            // WhatsApp no activa como link la palabra 'localhost' sin puntos ni dominios.
            // Si hay una IP de red local (ej: 192.168.X.X), usarla para que WhatsApp la reconozca como link y sea accesible desde celulares.
            $ip = gethostbyname(gethostname());
            if (!empty($ip) && $ip !== '127.0.0.1' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $host = $ip;
            } else {
                $host = 'tallermecanico-php.test';
            }
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $subDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($subDir === '.' || $subDir === '/') {
            $subDir = '';
        }

        // Evitar subcarpetas internas como /api o /views si se invoca desde ellas
        $subDir = preg_replace('#/(api|includes|views|assets).*$#', '', $subDir);

        return rtrim($protocolo . $host . $subDir, '/');
    }
}

if (!function_exists('obtenerUrlPublicaDocumento')) {
    /**
     * Construye el enlace público completo con el token para WhatsApp.
     */
    function obtenerUrlPublicaDocumento(string $token, ?PDO $pdo = null): string {
        $base = obtenerUrlBasePublica($pdo);
        return $base . '/doc.php?t=' . rawurlencode(strtoupper(trim($token)));
    }
}
