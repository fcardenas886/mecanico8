<?php
/**
 * Helper de Consulta de Vehículos vía API Externa (Chile y Global)
 * Soporta Boostr Chile, GetAPI Chile, AutoRiesgo, Apify, NHTSA (VIN gratis), Custom y Demo.
 */

if (!function_exists('consultarVehiculoPorPatenteOVin')) {

    function normalizarCombustible($c) {
        $c = mb_strtolower(trim((string)$c));
        if (str_contains($c, 'diesel') || str_contains($c, 'diésel') || str_contains($c, 'petroleo') || str_contains($c, 'petróleo')) return 'Diésel';
        if (str_contains($c, 'bencina') || str_contains($c, 'gasolina') || str_contains($c, 'gasoline')) return 'Bencina';
        if (str_contains($c, 'hibrid') || str_contains($c, 'híbrid')) return 'Híbrido';
        if (str_contains($c, 'electr') || str_contains($c, 'eléctr')) return 'Eléctrico';
        if (str_contains($c, 'gas') || str_contains($c, 'glp') || str_contains($c, 'gnc')) return 'Gas GLP/GNC';
        return !empty($c) ? mb_convert_case($c, MB_CASE_TITLE) : 'Bencina';
    }

    function normalizarTransmision($t) {
        $t = mb_strtolower(trim((string)$t));
        if (str_contains($t, 'auto') || str_contains($t, 'at') || str_contains($t, 'cvt')) return 'Automática';
        if (str_contains($t, 'man') || str_contains($t, 'mt') || str_contains($t, 'mecan')) return 'Manual';
        return !empty($t) ? mb_convert_case($t, MB_CASE_TITLE) : 'Manual';
    }

    function normalizarTipo($tipo) {
        $t = mb_strtolower(trim((string)$tipo));
        if (str_contains($t, 'sedan') || str_contains($t, 'sedán')) return 'Sedán';
        if (str_contains($t, 'suv') || str_contains($t, 'station') || str_contains($t, 'rural')) return 'SUV';
        if (str_contains($t, 'hatch')) return 'Hatchback';
        if (str_contains($t, 'camioneta') || str_contains($t, 'pickup') || str_contains($t, 'pick-up')) return 'Camioneta';
        if (str_contains($t, 'furgon') || str_contains($t, 'furgón') || str_contains($t, 'van')) return 'Furgón';
        if (str_contains($t, 'moto')) return 'Motocicleta';
        return !empty($tipo) ? mb_convert_case($tipo, MB_CASE_TITLE) : 'Automóvil';
    }

    function consultarBoostr($patente, $apiKey) {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'No se ha configurado la API Key de Boostr en Ajustes > API Vehículos.'];
        }

        $url = "https://api.boostr.cl/vehicles/{$patente}.json";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-API-KEY: ' . $apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => 'Error de conexión con Boostr: ' . $curlErr];
        }

        $json = json_decode($response, true);
        if ($httpCode === 200 && !empty($json['data'])) {
            $d = $json['data'];
            return [
                'success' => true,
                'fuente' => 'boostr',
                'mensaje' => 'Datos obtenidos exitosamente desde Boostr Chile.',
                'datos' => [
                    'patente' => $patente,
                    'marca' => mb_strtoupper($d['make'] ?? $d['marca'] ?? ''),
                    'modelo' => mb_strtoupper($d['model'] ?? $d['modelo'] ?? ''),
                    'anio' => !empty($d['year']) ? (int)$d['year'] : (!empty($d['anio']) ? (int)$d['anio'] : null),
                    'color' => mb_strtoupper($d['color'] ?? ''),
                    'combustible' => normalizarCombustible($d['fuel'] ?? $d['combustible'] ?? ''),
                    'motor' => $d['engine_number'] ?? $d['motor'] ?? '',
                    'transmision' => normalizarTransmision($d['transmission'] ?? $d['transmision'] ?? ''),
                    'tipo_vehiculo' => normalizarTipo($d['type'] ?? $d['tipo'] ?? ''),
                    'vin' => strtoupper($d['vin'] ?? $d['chassis'] ?? ''),
                    'propietario' => $d['owner'] ?? $d['propietario'] ?? '',
                    'rut_propietario' => $d['rut'] ?? ''
                ]
            ];
        }

        $msg = $json['message'] ?? $json['error'] ?? "No se encontraron datos para la patente $patente en Boostr.";
        return ['success' => false, 'error' => $msg, 'http_code' => $httpCode];
    }

    function consultarGetAPI($patente, $apiKey) {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'No se ha configurado la API Key de GetAPI en Ajustes > API Vehículos.'];
        }

        $url = "https://getapi.cl/v1/patente/{$patente}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-Api-Key: ' . $apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => 'Error de conexión con GetAPI: ' . $curlErr];
        }

        $json = json_decode($response, true);
        if ($httpCode === 200 && !empty($json)) {
            $d = $json['data'] ?? $json;
            return [
                'success' => true,
                'fuente' => 'getapi',
                'mensaje' => 'Datos obtenidos exitosamente desde GetAPI Chile.',
                'datos' => [
                    'patente' => $patente,
                    'marca' => mb_strtoupper($d['marca'] ?? ''),
                    'modelo' => mb_strtoupper($d['modelo'] ?? ''),
                    'anio' => !empty($d['anio']) ? (int)$d['anio'] : null,
                    'color' => mb_strtoupper($d['color'] ?? ''),
                    'combustible' => normalizarCombustible($d['combustible'] ?? ''),
                    'motor' => $d['motor'] ?? $d['cilindrada'] ?? '',
                    'transmision' => normalizarTransmision($d['transmision'] ?? ''),
                    'tipo_vehiculo' => normalizarTipo($d['tipo'] ?? ''),
                    'vin' => strtoupper($d['vin'] ?? $d['chasis'] ?? ''),
                    'propietario' => $d['propietario'] ?? $d['nombre_propietario'] ?? '',
                    'rut_propietario' => $d['rut'] ?? ''
                ]
            ];
        }

        $msg = $json['message'] ?? $json['error'] ?? "No se encontraron datos para la patente $patente en GetAPI.";
        return ['success' => false, 'error' => $msg, 'http_code' => $httpCode];
    }

    function consultarAutoRiesgo($patente, $apiKey, $endpoint) {
        $url = !empty($endpoint) ? str_replace('{patente}', $patente, $endpoint) : "https://api.autoriesgo.cl/v1/vehiculo/{$patente}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if ($httpCode === 200 && !empty($json)) {
            $d = $json['data'] ?? $json;
            return [
                'success' => true,
                'fuente' => 'autoriesgo',
                'mensaje' => 'Datos obtenidos exitosamente desde AutoRiesgo.',
                'datos' => [
                    'patente' => $patente,
                    'marca' => mb_strtoupper($d['marca'] ?? ''),
                    'modelo' => mb_strtoupper($d['modelo'] ?? ''),
                    'anio' => !empty($d['anio']) ? (int)$d['anio'] : null,
                    'color' => mb_strtoupper($d['color'] ?? ''),
                    'combustible' => normalizarCombustible($d['combustible'] ?? ''),
                    'motor' => $d['motor'] ?? '',
                    'transmision' => normalizarTransmision($d['transmision'] ?? ''),
                    'tipo_vehiculo' => normalizarTipo($d['tipo'] ?? ''),
                    'vin' => strtoupper($d['vin'] ?? $d['chasis'] ?? '')
                ]
            ];
        }

        return ['success' => false, 'error' => 'No se encontraron datos en AutoRiesgo para ' . $patente];
    }

    function consultarFindatos($patente) {
        $cleanPatente = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$patente)));
        if (empty($cleanPatente)) {
            return ['success' => false, 'error' => 'Patente no válida.'];
        }

        $url = 'https://findatos.com/api/vehiculos/search?q=' . urlencode($cleanPatente) . '&page=1&limit=5';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 7,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Referer: https://findatos.com/patentes-chile'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => 'Error de conexión con Findatos: ' . $curlErr];
        }

        $json = json_decode((string)$response, true);
        if ($httpCode === 200 && !empty($json['results'])) {
            $match = null;
            foreach ($json['results'] as $res) {
                if (strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string)($res['patente'] ?? ''))) === $cleanPatente) {
                    $match = $res;
                    break;
                }
            }
            if (!$match) {
                $match = $json['results'][0];
            }

            $marca = mb_strtoupper(trim($match['marca'] ?? ''));
            $modelo = mb_strtoupper(trim($match['modelo'] ?? ''));
            $anio = !empty($match['año']) ? (int)$match['año'] : (!empty($match['anio']) ? (int)$match['anio'] : null);
            $color = mb_strtoupper(trim($match['color'] ?? ''));
            $tipo = normalizarTipo($match['tipo_vehiculo'] ?? '');
            $motor = trim($match['id_motor'] ?? '');
            $vin = strtoupper(trim($match['id_chasis'] ?? ''));

            // Deducir combustible por palabras clave en modelo
            $combustible = 'Bencina';
            $textoCompleto = mb_strtolower($modelo . ' ' . $tipo);
            if (str_contains($textoCompleto, 'diesel') || str_contains($textoCompleto, 'd-max') || str_contains($textoCompleto, 'dmax') || str_contains($textoCompleto, 'crdi') || str_contains($textoCompleto, 'tdci') || str_contains($textoCompleto, 'hdi') || str_contains($textoCompleto, 'dakar') || str_contains($textoCompleto, 'navara')) {
                $combustible = 'Diésel';
            } elseif (str_contains($textoCompleto, 'hibrid') || str_contains($textoCompleto, 'hybrid')) {
                $combustible = 'Híbrido';
            } elseif (str_contains($textoCompleto, 'electr')) {
                $combustible = 'Eléctrico';
            }

            return [
                'success' => true,
                'fuente' => 'findatos',
                'mensaje' => "Datos obtenidos exitosamente desde Findatos Chile para la patente {$cleanPatente}.",
                'datos' => [
                    'patente' => $cleanPatente,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'anio' => $anio,
                    'color' => $color,
                    'combustible' => $combustible,
                    'motor' => $motor,
                    'transmision' => 'Manual',
                    'tipo_vehiculo' => $tipo,
                    'vin' => $vin,
                    'propietario' => trim($match['nombre'] ?? ''),
                    'rut_propietario' => trim($match['rut_masked'] ?? '')
                ]
            ];
        }

        return ['success' => false, 'error' => "No se encontraron datos para la patente {$cleanPatente} en Findatos."];
    }

    function apifyRequest($method, $url, $apiKey, $body = null, $timeout = 30) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $apiKey
            ]
        ];
        if ($body !== null) $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['code' => $code, 'err' => $err, 'json' => json_decode((string)$response, true)];
    }

    function consultarApify($patente, $apiKey) {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'No se ha configurado el token de Apify en Ajustes > API Vehículos.'];
        }

        $maxEspera = 100; // segundos totales esperando al actor
        @set_time_limit($maxEspera + 30);
        $base = 'https://api.apify.com/v2';

        // 1. Lanzar el actor (asíncrono, responde de inmediato con el id de la ejecución)
        $r = apifyRequest('POST', "$base/acts/scraperschile~patente-chile/runs?timeout=$maxEspera", $apiKey, [
            'queries' => [['searchType' => 'vehiculo', 'term' => $patente]],
            'timeoutSecs' => 45,
            'proxyAttempts' => 5,
            'allowPartialResults' => true
        ]);

        if ($r['err']) {
            return ['success' => false, 'error' => 'Error de conexión con Apify: ' . $r['err']];
        }
        if ($r['code'] === 401 || $r['code'] === 403) {
            return ['success' => false, 'error' => 'Token de Apify inválido o sin permisos.', 'http_code' => $r['code']];
        }
        $run = $r['json']['data'] ?? null;
        if ($r['code'] >= 400 || empty($run['id'])) {
            $msg = $r['json']['error']['message'] ?? "Apify no pudo iniciar la consulta (HTTP {$r['code']}).";
            return ['success' => false, 'error' => $msg, 'http_code' => $r['code']];
        }

        $runId = $run['id'];
        $datasetId = $run['defaultDatasetId'] ?? '';
        $inicio = time();
        $estado = $run['status'] ?? 'READY';

        // 2. Esperar hasta que termine (long-polling de 20s por vuelta)
        while (in_array($estado, ['READY', 'RUNNING'], true) && (time() - $inicio) < $maxEspera) {
            $st = apifyRequest('GET', "$base/actor-runs/$runId?waitForFinish=20", $apiKey, null, 30);
            if (!empty($st['json']['data']['status'])) {
                $estado = $st['json']['data']['status'];
                $datasetId = $st['json']['data']['defaultDatasetId'] ?? $datasetId;
            }
        }

        if (in_array($estado, ['READY', 'RUNNING'], true)) {
            apifyRequest('POST', "$base/actor-runs/$runId/abort", $apiKey, null, 10); // no seguir gastando crédito
            return ['success' => false, 'error' => "Apify tardó más de {$maxEspera}s y se canceló. Reintenta o usa otro proveedor."];
        }
        if ($estado !== 'SUCCEEDED') {
            return ['success' => false, 'error' => "La consulta en Apify terminó con estado $estado. Reintenta en unos segundos."];
        }

        // 3. Leer resultados
        $ds = apifyRequest('GET', "$base/datasets/$datasetId/items?format=json&clean=true", $apiKey, null, 20);
        $item = $ds['json'][0] ?? null;
        if (is_array($item) && isset($item['data']) && is_array($item['data'])) {
            $item = array_merge($item, $item['data']);
        }
        $marca = is_array($item) ? ($item['marca'] ?? $item['brand'] ?? $item['make'] ?? '') : '';
        if (empty($marca)) {
            $claves = is_array($item) ? implode(', ', array_keys($item)) : 'sin resultados';
            return ['success' => false, 'error' => "Apify respondió pero sin marca para $patente (campos recibidos: $claves)."];
        }

        return [
            'success' => true,
            'fuente' => 'apify',
            'mensaje' => 'Datos obtenidos desde Apify (Patente Chile).',
            'datos' => [
                'patente' => $patente,
                'marca' => mb_strtoupper($marca),
                'modelo' => mb_strtoupper($item['modelo'] ?? $item['model'] ?? ''),
                'anio' => (int)($item['anio'] ?? $item['año'] ?? $item['year'] ?? 0) ?: null,
                'color' => mb_strtoupper($item['color'] ?? ''),
                'combustible' => normalizarCombustible($item['combustible'] ?? $item['fuel'] ?? ''),
                'motor' => $item['motor'] ?? $item['engine'] ?? '',
                'transmision' => normalizarTransmision($item['transmision'] ?? $item['transmission'] ?? ''),
                'tipo_vehiculo' => normalizarTipo($item['tipo'] ?? $item['type'] ?? ''),
                'vin' => strtoupper($item['vin'] ?? $item['chasis'] ?? $item['chassis'] ?? '')
            ]
        ];
    }

    function consultarCustomAPI($patente, $apiKey, $endpoint) {
        if (empty($endpoint)) {
            return ['success' => false, 'error' => 'URL de API Personalizada no configurada en Ajustes.'];
        }

        $url = str_replace(['{patente}', '{plate}'], $patente, $endpoint);
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];
        if (!empty($apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
            $headers[] = 'X-API-KEY: ' . $apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if ($httpCode === 200 && is_array($json)) {
            $d = $json['data'] ?? $json;
            return [
                'success' => true,
                'fuente' => 'custom',
                'mensaje' => 'Datos obtenidos desde API Personalizada.',
                'datos' => [
                    'patente' => $patente,
                    'marca' => mb_strtoupper($d['marca'] ?? $d['make'] ?? ''),
                    'modelo' => mb_strtoupper($d['modelo'] ?? $d['model'] ?? ''),
                    'anio' => (int)($d['anio'] ?? $d['year'] ?? 0) ?: null,
                    'color' => mb_strtoupper($d['color'] ?? ''),
                    'combustible' => normalizarCombustible($d['combustible'] ?? $d['fuel'] ?? ''),
                    'motor' => $d['motor'] ?? $d['engine'] ?? '',
                    'transmision' => normalizarTransmision($d['transmision'] ?? $d['transmission'] ?? ''),
                    'tipo_vehiculo' => normalizarTipo($d['tipo'] ?? $d['type'] ?? ''),
                    'vin' => strtoupper($d['vin'] ?? '')
                ]
            ];
        }

        return ['success' => false, 'error' => 'Error al consultar API personalizada (HTTP ' . $httpCode . ')'];
    }

    function consultarNHTSA_VIN($vin) {
        $url = "https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/{$vin}?format=json";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 9,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if ($httpCode === 200 && !empty($json['Results'])) {
            $map = [];
            foreach ($json['Results'] as $r) {
                if (!empty($r['Value']) && !empty($r['Variable'])) {
                    $map[$r['Variable']] = trim($r['Value']);
                }
            }

            $marca = $map['Make'] ?? '';
            $modelo = $map['Model'] ?? '';
            $anio = (int)($map['Model Year'] ?? 0) ?: null;
            $combustible = normalizarCombustible($map['Fuel Type - Primary'] ?? '');
            $motor = !empty($map['Displacement (L)']) ? $map['Displacement (L)'] . 'L' : ($map['Engine Model'] ?? '');
            $tipo = normalizarTipo($map['Body Class'] ?? $map['Vehicle Type'] ?? '');

            return [
                'success' => true,
                'fuente' => 'nhtsa',
                'mensaje' => 'Datos decodificados exitosamente desde VIN (NHTSA)',
                'datos' => [
                    'patente' => '',
                    'vin' => $vin,
                    'marca' => mb_strtoupper($marca),
                    'modelo' => mb_strtoupper($modelo),
                    'anio' => $anio,
                    'combustible' => $combustible,
                    'motor' => $motor,
                    'tipo_vehiculo' => $tipo,
                    'color' => ''
                ]
            ];
        }

        return ['success' => false, 'error' => "No se pudo decodificar el VIN $vin."];
    }

    function consultarModoDemo($patente) {
        $catalogoDemo = [
            'ABCD12' => [
                'marca' => 'TOYOTA', 'modelo' => 'YARIS SEDAN', 'anio' => 2020,
                'color' => 'BLANCO', 'combustible' => 'Bencina', 'motor' => '1.5L 2NZ-FE',
                'transmision' => 'Manual', 'tipo_vehiculo' => 'Sedán', 'vin' => '9BRBT933X01928374'
            ],
            'BBCL20' => [
                'marca' => 'HYUNDAI', 'modelo' => 'TUCSON GL', 'anio' => 2019,
                'color' => 'GRIS PLATA', 'combustible' => 'Diésel', 'motor' => '2.0 CRDi DPF',
                'transmision' => 'Automática', 'tipo_vehiculo' => 'SUV', 'vin' => 'KMHJT81WDU1092834'
            ],
            'CGHT21' => [
                'marca' => 'CHEVROLET', 'modelo' => 'SAIL LT', 'anio' => 2021,
                'color' => 'AZUL METÁLICO', 'combustible' => 'Bencina', 'motor' => '1.5L 16V',
                'transmision' => 'Manual', 'tipo_vehiculo' => 'Sedán', 'vin' => 'LSVAA2148NE019284'
            ],
            'KRPX88' => [
                'marca' => 'NISSAN', 'modelo' => 'NAVARA LE 4X4', 'anio' => 2022,
                'color' => 'ROJO METALIZADO', 'combustible' => 'Diésel', 'motor' => '2.3 dCi Bi-Turbo',
                'transmision' => 'Automática', 'tipo_vehiculo' => 'Camioneta', 'vin' => 'MNTVCPD2309182746'
            ],
            'LPTZ44' => [
                'marca' => 'KIA', 'modelo' => 'SPORTAGE', 'anio' => 2023,
                'color' => 'NEGRO PERLADO', 'combustible' => 'Bencina', 'motor' => '2.0L MPI',
                'transmision' => 'Automática', 'tipo_vehiculo' => 'SUV', 'vin' => 'KNAFT813BPE918273'
            ],
            'HHYT10' => [
                'marca' => 'SUZUKI', 'modelo' => 'SWIFT GLX', 'anio' => 2018,
                'color' => 'BLANCO PERLA', 'combustible' => 'Bencina', 'motor' => '1.2L Dualjet',
                'transmision' => 'Manual', 'tipo_vehiculo' => 'Hatchback', 'vin' => 'JSAAZC53S00192845'
            ]
        ];

        if (isset($catalogoDemo[$patente])) {
            $veh = $catalogoDemo[$patente];
        } else {
            $marcasMuestra = [
                ['marca' => 'PEUGEOT', 'modelo' => '208 ALLURE', 'combustible' => 'Diésel', 'motor' => '1.5 BlueHDi', 'tipo' => 'Hatchback', 'transmision' => 'Manual'],
                ['marca' => 'FORD', 'modelo' => 'RANGER XLT 4X4', 'combustible' => 'Diésel', 'motor' => '3.2 TDCi', 'tipo' => 'Camioneta', 'transmision' => 'Automática'],
                ['marca' => 'VOLKSWAGEN', 'modelo' => 'GOL TREND', 'combustible' => 'Bencina', 'motor' => '1.6 MSI', 'tipo' => 'Hatchback', 'transmision' => 'Manual'],
                ['marca' => 'MAZDA', 'modelo' => 'CX-5', 'combustible' => 'Bencina', 'motor' => '2.0 SKYACTIV-G', 'tipo' => 'SUV', 'transmision' => 'Automática'],
                ['marca' => 'MITSUBISHI', 'modelo' => 'L200 DAKAR', 'combustible' => 'Diésel', 'motor' => '2.4 DID High Power', 'tipo' => 'Camioneta', 'transmision' => 'Manual'],
                ['marca' => 'CHERY', 'modelo' => 'TIGGO 2 PRO', 'combustible' => 'Bencina', 'motor' => '1.5 VVT', 'tipo' => 'SUV', 'transmision' => 'Manual']
            ];
            $seed = crc32($patente);
            $pick = $marcasMuestra[abs($seed) % count($marcasMuestra)];
            $colores = ['BLANCO', 'GRIS OSCURO', 'PLATA', 'ROJO', 'AZUL', 'NEGRO'];
            $color = $colores[abs($seed >> 3) % count($colores)];
            $anio = 2016 + (abs($seed >> 2) % 8);

            $veh = [
                'marca' => $pick['marca'],
                'modelo' => $pick['modelo'],
                'anio' => $anio,
                'color' => $color,
                'combustible' => $pick['combustible'],
                'motor' => $pick['motor'],
                'transmision' => $pick['transmision'],
                'tipo_vehiculo' => $pick['tipo'],
                'vin' => 'CL' . strtoupper(substr(md5($patente), 0, 15))
            ];
        }

        return [
            'success' => true,
            'fuente' => 'demo',
            'mensaje' => '✨ Modo Demo: Datos simulados para ' . $patente . ' (Configura tu API Key en Configuración > API Vehículos para datos en tiempo real)',
            'datos' => array_merge(['patente' => $patente], $veh)
        ];
    }

    /**
     * Función principal que ejecuta la consulta vehicular según configuración
     */
    function consultarVehiculoPorPatenteOVin($patente, $vin = '', $configs = []) {
        $provider = strtolower(trim($configs['VEHICULO_API_PROVIDER'] ?? 'demo'));
        $apiKey = trim($configs['VEHICULO_API_KEY'] ?? '');
        $endpoint = trim($configs['VEHICULO_API_ENDPOINT'] ?? '');

        if (!empty($vin) && (empty($patente) || $provider === 'nhtsa')) {
            return consultarNHTSA_VIN($vin);
        }

        if ($provider === 'nhtsa') {
            // Si ingresaron un VIN en el campo patente (17 caracteres)
            if (strlen($patente) === 17) {
                return consultarNHTSA_VIN($patente);
            }
            return [
                'success' => false,
                'error' => "NHTSA es una API internacional oficial que busca ÚNICAMENTE por número VIN/Chasis (17 caracteres), no por patentes chilenas. Para consultar patentes chilenas (ej. ABCD12), selecciona Boostr Chile, GetAPI o Modo Demo en Configuración."
            ];
        }

        switch ($provider) {
            case 'findatos':
                return consultarFindatos($patente);
            case 'boostr':
                return consultarBoostr($patente, $apiKey);
            case 'getapi':
                return consultarGetAPI($patente, $apiKey);
            case 'autoriesgo':
                return consultarAutoRiesgo($patente, $apiKey, $endpoint);
            case 'manual':
                return ['success' => false, 'error' => 'Sin API: pulsa "Abrir AutoRiesgo", copia el resultado y pégalo en el cuadro "Pegar datos de la consulta".'];
            case 'apify':
                return consultarApify($patente, $apiKey);
            case 'custom':
                return consultarCustomAPI($patente, $apiKey, $endpoint);
            case 'demo':
            default:
                return consultarModoDemo($patente);
        }
    }
}
