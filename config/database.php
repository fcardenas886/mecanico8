<?php
// Version de la app: unica fuente de verdad, para que login y footer nunca queden desincronizados.
define('APP_VERSION', 'v5.4.0');

// Novedades reales por versión, para la pantalla de bienvenida y el módulo "Acerca de".
// Registra la cronología completa de la evolución del sistema desde su inicio.
define('APP_CHANGELOG', [
    'v5.4.0' => [
        'Presupuesto rediseñado a pantalla completa: tarjeta informativa compacta arriba con lo que pidió el cliente y los hallazgos del mecánico, y abajo la tabla de presupuesto agrupada en Mano de obra, Repuestos y Terceros.',
        'Agregar repuesto, servicio o mano de obra libre en tres formularios simples (la lista de repuestos se filtra por nombre, marca o N° de parte), más sugerencias de un clic con lo que ya se usó en ese auto o en su modelo.',
        'Aprobación parcial: el cliente puede aprobar solo algunos ítems, marcándolos en la tabla.',
        'Precio y cantidad se editan directo en la tabla, y el total se actualiza al instante con combos y ofertas incluidos (antes se mostraba sin descuentos hasta recargar).',
        'Si se agrega un repuesto o servicio que ya está, se suma la cantidad en vez de duplicar la línea. Cabecera fija, botones de WhatsApp y PDF accesibles arriba y abajo junto al total, auto-guardado en tiempo real y plazo de entrega con respaldo formal (a contar de recepción de repuestos y aprobación).',
    ],
    'v5.3.7' => [
        'Diagnóstico simplificado: retiro de la cuadrícula de chequeo de niveles y fluidos a petición del cliente, manteniendo la caja de Observaciones de Estación de Servicio.',
    ],
    'v5.3.6' => [
        'Kardex: los productos ahora nacen con su movimiento de "saldo inicial" (antes el stock inicial se cargaba sin dejar registro y el Kardex no explicaba de dónde salía). Se agregó el saldo inicial que faltaba en los productos ya existentes, y el Kardex se ordena por fecha.',
    ],
    'v5.3.5' => [
        'Devoluciones: soporte completo para productos con líneas partidas por combo o ventas con múltiples packs. El cálculo de unidades disponibles y reembolso proporcional agrupa todas las presentaciones del producto sin bloquear unidades.',
        'Kardex: cálculo continuo del saldo (StockSaldo) cuando una venta contiene múltiples líneas o packs del mismo producto.',
    ],
    'v5.3.4' => [
        'Boleta reimpresa desde Ventas: los productos que la venta guarda en líneas separadas por cada pack se juntan por producto (Aceite x2, Filtro x2), como los ve el cliente.',
    ],
    'v5.3.3' => [
        'Boleta: el descuento de un combo ahora se muestra al final como "Descuento pack «nombre»", no repartido en cada producto (los productos quedan a precio de lista y no confunde). Las ofertas propias de un producto siguen bajo su línea.',
    ],
    'v5.3.2' => [
        'Boleta/comprobante: ahora detalla los descuentos. Cada producto muestra su propio descuento con el nombre del combo u oferta, y el resumen separa "Combos y ofertas" del "Descuento adicional (%)" que aplica el cajero al final. Igual al reimprimir desde Ventas.',
    ],
    'v5.3.1' => [
        'Combos: el mismo combo ahora se puede aplicar varias veces en una venta (ej. repuestos para 2 autos: 2 aceites + 2 filtros = 2 packs), tanto en líneas con cantidad 2 como en líneas separadas.',
    ],
    'v5.3.0' => [
        'El presupuesto es el que vale: al aprobarlo, sus descuentos (combos y ofertas) quedan congelados línea por línea, y la Caja cobra exactamente ese total aunque después venza una oferta, se apague un combo o cambie un precio.',
        'Un presupuesto aprobado muestra siempre lo que se congeló al aprobar, no un recálculo con las ofertas de hoy. La Caja valida contra el presupuesto (línea, cantidad y que la orden no esté ya cobrada).',
        'Los presupuestos aprobados antes de este cambio siguen cobrándose como antes.',
    ],
    'v5.2.1' => [
        'Presupuesto: ahora también incluye las ofertas individuales de cada producto (descuento por % o por volumen), no solo los combos, con el descuento a la vista. El presupuesto, el impreso y la Caja dan el mismo total.',
        'Caja: al cargar un presupuesto de OT, las ofertas individuales de los productos se ven en pantalla antes de cobrar.',
    ],
    'v5.2.0' => [
        'Presupuesto: ahora aplica los mismos combos de Promociones que la Caja, con el descuento a la vista, para que el presupuesto entregado diga lo mismo que se cobra. Hay un interruptor para aplicarlos o no.',
        'Presupuesto: el precio de cada línea se puede editar a mano. Un repuesto con el precio editado queda fuera de los combos (es el precio acordado).',
        'Corrección: al cargar en Caja un presupuesto de OT o una cotización, el combo ahora se ve en pantalla antes de cobrar (antes solo se aplicaba al emitir la venta).',
    ],
    'v5.1.1' => [
        'Combos: solo la cantidad que pide cada cupo recibe el descuento; si el cliente lleva más unidades, el resto se vende aparte a precio normal (boleta en líneas separadas). Aplica a %, monto fijo y precio cerrado.',
        'Corregida una fuga de dinero en combos de precio cerrado y un caso en que un combo sin descuento real podía cobrar de más.',
    ],
    'v5.1.0' => [
        'Nuevo: Combos por tipo de repuesto en Promociones (ej. "1 Aceite + 1 Filtro de Aceite"), con descuento en % o monto fijo. Se aplican solos a cualquier marca que el cliente lleve, sin crear una promoción por cada combinación.',
        'El combo se detecta y muestra en la Caja antes de cobrar, y no se acumula con el descuento individual del producto.',
        'El descuento del combo se reparte proporcional entre los productos, para que una devolución parcial reembolse lo realmente pagado, no el precio de lista. Devoluciones avisa cuando un producto vendido fue parte de un combo.',
    ],
    'v5.0.1' => [
        'Márgenes: se corrigió el cálculo en Compras, Actualizar precios, Reportes de utilidades e Inventario para que sea sobre el costo (recargo), no sobre el precio de venta. Ej: costo $1.000 y venta $1.300 ahora muestra 30%, no 23,1%.',
    ],
    'v5.0.0' => [
        'Presupuestos Inteligentes: Historial desplegable de repuestos y servicios previos utilizados en el vehículo con reutilización en 1 clic.',
        'Brújula de Presupuesto y Semáforo de Cobertura: Indicador visual en tiempo real para verificar que todas las peticiones del cliente y hallazgos técnicos estén contemplados.',
        'Chequeo de Fluidos Integrado: Detección automática del aceite y filtro de motor exactos con botón de carga en 1 clic.',
        'Envío por WhatsApp universal: Compartir presupuestos directamente por WhatsApp incluso si el cliente no tiene teléfono registrado previamente.',
    ],
    'v4.9.3' => [
        'Productos: nuevo campo Descripción (opcional) en la sección de repuesto, que se muestra en la Consulta rápida de repuestos.',
    ],
    'v4.9.2' => [
        'Consulta rápida de repuestos (antes ¿Qué necesita este auto?): una sola caja para escribir un modelo o elegir un auto del taller, con aviso de cuándo conviene usar la ficha del vehículo.',
    ],
    'v4.9.1' => [
        'Caja: la ventana Servicio ahora ofrece los servicios del catálogo con su precio ya cargado (se puede editar), además de escribir uno distinto.',
    ],
    'v4.9.0' => [
        'Nueva pantalla Servicios y precios: la mano de obra del taller con su precio ya definido, que se elige al armar el presupuesto.',
        'El diagnóstico es un servicio más: se agrega solo al presupuesto, no se cobra si el cliente aprueba la reparación y se cobra si no la aprueba (se puede cambiar a cobrar siempre).',
        'El presupuesto impreso, el listado de órdenes y el cobro respetan esa regla.',
    ],
    'v4.8.0' => [
        'Inicio: nuevo bloque "Taller: qué hay que hacer hoy" con las órdenes por diagnosticar, por presupuestar, esperando al cliente, en reparación y listas para retirar, y botón para recibir un vehículo.',
        'Una orden ya cobrada en caja deja de decir "Empezar reparación": ahora dice "Cobrada, falta entregar".',
        'Presupuesto: se quitaron botones repetidos (dos de aprobar y cuatro de imprimir/PDF); quedan Aprobó todo, Aprobó solo lo marcado y Rechazó.',
        'Una orden ya no se puede marcar como Entregada si no está cobrada y lista para entregar.',
        'Textos unificados: presupuesto (antes también cotización), historial del vehículo (antes ficha clínica), niveles y fluidos.',
    ],
    'v4.7.0' => [
        'Productos: nuevos campos de repuesto (tipo, marca, N° de parte del fabricante y OEM, viscosidad) y aclaración de cuándo usar un código interno si el repuesto no trae código de barras.',
        'Se puede buscar un repuesto por su número de parte en Productos, Caja POS y Presupuesto, escrito como sea (W 67/1, w671, W67-1).',
        'Presupuesto: la lista de repuestos ahora tiene un filtro por nombre, marca o N° de parte. Las fichas y el buscador muestran el N° de parte real, no el código de barras.',
    ],
    'v4.6.1' => [
        'Botón "Buscar en Mann-Filter con VIN": copia el VIN del vehículo y abre el catálogo Mann-Filter en otra pestaña, listo para pegar.',
    ],
    'v4.6.0' => [
        'El sistema aprende qué repuestos usa cada auto: al entregar una orden recuerda el aceite y los filtros (por marca, modelo, año y motor) y los sugiere la próxima vez.',
        '¿Qué necesita este auto? ahora muestra primero "Lo que ya usamos en este auto" y marca lo comprobado en el taller.',
        'Avisos automáticos cuando un repuesto es de otro motor (cilindrada o diésel/gasolina) o de otros años; lo aprendido por error se puede quitar.',
    ],
    'v4.5.0' => [
        'Taller más fácil de usar: barra de 5 pasos (Recepción, Diagnóstico, Presupuesto, Reparación y cobro, Entrega) en cada pantalla, con el paso actual resaltado.',
        'Órdenes de trabajo: cada orden muestra su situación en palabras simples y un único botón con lo que sigue ("Diagnosticar", "Hacer presupuesto", "Registrar respuesta", "Entregar al cliente"...).',
        'Órdenes de trabajo: pestañas por situación (Por diagnosticar, Esperando al cliente, En reparación, Listos para retirar...) con contador, y guía "¿Cómo funciona?".',
        'Cada pantalla explica en una frase "qué hago aquí"; se quitó la numeración técnica de los títulos y se renombró el menú (Recibir un vehículo, Órdenes de trabajo, Vehículos y su historial).',
    ],
    'v4.4.0' => [
        'Caja POS: ahora se pueden vender "servicios" (mano de obra, terceros) además de productos con stock — sin producto ni stock detrás, solo descripción y precio.',
        'Los servicios quedan detallados en la boleta/factura electrónica igual que cualquier producto, con su IVA correspondiente.',
        'Botón "Servicio" en el POS para agregar mano de obra a mano en una venta de mostrador.',
        'Orden de Trabajo: repuestos y mano de obra aprobados en el Presupuesto ahora se cobran juntos en una sola venta del POS — una boleta/factura con todo incluido, en vez de repuestos por un lado y mano de obra como ingreso de caja aparte.',
    ],
    'v4.3.0' => [
        'Módulo de Taller — Orden de Trabajo (ejecución): mecánico asignado y estados de avance (En reparación, Listo para entregar, Entregado).',
        'Los repuestos aprobados del presupuesto se cobran con el Caja POS real (mismo motor de ventas, stock y boleta/factura de siempre) y quedan vinculados a la OT.',
        'Corrección: el Diagnóstico quedaba editable en una OT ya entregada; ahora es de solo lectura desde que pasa a Presupuesto.',
    ],
    'v4.2.0' => [
        'Módulo de Taller — Diagnóstico: hallazgos del mecánico por área (Mecánica/Electricidad/Carrocería), con paso saltable para pedidos directos del cliente.',
        'Módulo de Taller — Presupuesto: desglose en Repuestos, Mano de Obra y Terceros, con tiempo de entrega estimado.',
        'Aprobación del cliente en 3 vías: Aprobado Total, Aprobado Parcial (línea por línea) o Rechazado, con impresión del presupuesto.',
    ],
    'v4.1.0' => [
        'Módulo de Taller — Checklist de Recepción: catálogo configurable de ítems (Sí/No + detalle opcional) para dejar constancia del estado del vehículo al ingresar.',
        'Módulo de Taller — Orden de Ingreso: genera el Comprobante de Custodia firmado en pantalla, con copia para el cliente y copia reducida para el parabrisas.',
        'Módulo de Taller — Ficha de Vehículos: registro de vehículos por cliente, con historial de Órdenes de Trabajo.',
        'Corrección de menú: los desplegables de navegación (Taller, Mantenedores, etc.) tenían un pequeño espacio que podía cerrar el menú antes de alcanzar a elegir una opción.',
    ],
    'v4.0.1' => [
        'Heartbeat Activo de Conectividad: Monitorización continua de red cada 5 segundos mediante ping ultraligero para conmutación inmediata a contingencia.',
        'Simulador de Modo Offline con 1 Clic: Píldora interactiva que permite alternar y probar ventas locales en IndexedDB sin desconectar cables ni routers.',
        'Blindaje Total Anti-Autofill: Protección semántica y por script para evitar que el gestor de contraseñas del navegador inyecte "admin" en la barra de escaneo.',
        'Sincronización Automática Dinámica: Detección inteligente de versión y reconexión en segundo plano con refresco de catálogo local.',
    ],
    'v4.0.0' => [
        'Arquitectura PWA (Progressive Web App): Aplicación instalable en el escritorio de Windows para operar en ventana nativa sin barras de navegador.',
        'Motor de Venta Offline Autónomo: Operación continua de la caja registradora durante cortes de energía o caídas de internet.',
        'Base de Datos Local IndexedDB: Búsqueda y escaneo instantáneo de productos, códigos alternativos, promociones y balanzas pesables en memoria local.',
        'Cola de Ventas y Comprobante Provisional: Registro persistente en disco e impresión de tickets de contingencia sin conexión.',
        'Sincronización Inteligente con el VPS: Detección automática de reconexión y carga masiva de transacciones pendientes en MySQL.',
        'Service Worker con Caché Resiliente: Capacidad de abrir y cargar la terminal de caja incluso arrancando el computador sin internet.',
    ],
    'v3.3.0' => [
        'Actualizador Rápido de Precios: Nueva pantalla especializada para cambiar precios de venta y costos mediante escaneo continuo de códigos de barra o búsqueda rápida.',
        'Cálculo de Margen Comercial en Tiempo Real: Visualización instantánea del margen de ganancia (%) con alertas de rentabilidad al modificar precios.',
        'Gestión de Packs y Códigos Secundarios: Ajuste directo de precios para Six Packs, Cajas y presentaciones alternativas desde la misma pantalla.',
        'Herramientas Masivas de Ajuste: Aplicación de porcentajes globales (+5%, +10%, etc.) y redondeo comercial chileno a decenas/centenas.',
        'Impresión de Flejes y Etiquetas de Góndola: Generación e impresión directa de etiquetas de estantería para los productos actualizados.',
        'Jerarquía Inteligente de Precios en POS: Armonización automática entre promociones Multibuy (ej. 3x$5.000) y códigos de pack sin precio fijo.',
    ],
    'v3.2.0' => [
        'Consulta de Ventas con Filtros Avanzados: Búsqueda flexible por rango de fechas (con accesos directos: Hoy, Ayer, Últimos 7 Días, Este Mes), N° de venta, Folio DTE, Nombre de Cliente o RUT.',
        'Detalle Completo de Operación: Modal interactivo con inspección profunda de cajero, turno, cliente, desglose de ítems, descuentos, impuestos (Neto / IVA 19%) y medios de pago múltiples/mixtos.',
        'Reimpresión de Ticket Térmico (80mm/58mm): Generación y reimpresión instantánea del comprobante físico con membrete del local, detalle de artículos y pagaré firmado para ventas a crédito (Fiado).',
        'Integración y Descarga de Boleta/Factura Electrónica (DTE): Botón de acceso directo e impresión limpia del PDF oficial tributario emitido ante el SII.',
        'KPIs Ejecutivos en Tiempo Real: Tarjetas con total recaudado en el período, cantidad de operaciones, ticket promedio y monto anulado.',
    ],
    'v3.1.0' => [
        'Centro Integral de Reportes y BI: Módulo analítico unificado para auditar ventas, cartera, compras, rotación de inventario y personal.',
        'Reporte de Ventas y Medios de Pago: Métricas ejecutivas (Bruto, Neto, IVA 19%, Descuentos, Ticket Promedio), medios de pago y mapa de horas peak.',
        'Cartera de Clientes y Fiados: Auditoría de cuentas por cobrar, ranking de clientes con deuda activa, porcentaje de cupo utilizado y flujo de abonos.',
        'Compras y Gastos por Proveedor: Egresos en mercadería, ranking de distribuidores por volumen facturado y detalle de recepciones.',
        'Ranking y Detector de Stock Estancado ("Huesos"): Identificación de artículos líderes y productos sin movimiento con capital inmovilizado.',
        'Valorización de Inventario: Capital total en bodega a costo de adquisición vs. retorno proyectado a precio de venta y margen potencial.',
        'Rendimiento de Cajeros y Cuadraturas: Monitoreo de recaudación por cajero y balance histórico de sobrantes/faltantes en arqueos de turno.',
        'Exportación a Excel (CSV) universal con codificación UTF-8 e impresión limpia sin cabeceras innecesarias.',
    ],
    'v3.0.0' => [
        'Múltiples Códigos de Barra por Producto: Asocia códigos alternativos (packs, latas, cambio de presentación o nuevo EAN) sin duplicar stock.',
        'Búsqueda unificada en POS y Compras: Al escanear cualquiera de los códigos alternativos o el principal se localiza de inmediato el producto unificado.',
        'Bloqueo Rápido de Pantalla de Caja (Lock Screen): Protege la terminal con un clic (🔒) o atajo rápido (Alt + L / F9) con reloj digital en tiempo real.',
        'Desbloqueo seguro por Cajero o Supervisor: Validación por contraseña del cajero titular o clave de supervisor con registro en auditoría de seguridad.',
        'Protección total de ventas en curso: La venta actual, descuentos y clientes seleccionados permanecen intactos durante el bloqueo y recargas.',
    ],
    'v2.9.4' => [
        'Rediseño operativo del POS en 3 modos integrales: Supermercado (Caja Rápida), Táctil y Clásico.',
        'Modo Supermercado: oculta tarjetas de catálogo y despliega en el centro una tabla amplia de venta con escaneo continuo.',
        'Modo Táctil: barra deslizable superior de categorías y tarjetas táctiles optimizadas para touchscreen.',
        'Modo Clásico personalizable: selector en vivo de catálogo para elegir ⭐ Más Vendidos, 🏷️ En Oferta, 📦 Todos (A-Z) o por categoría.',
        'Persistencia de modo favorito y sincronización bidireccional entre la tabla y el carrito en tiempo real.',
    ],
    'v2.9.3' => [
        'Personalización visual de la marca: selector de Modo Claro (Light) y Modo Oscuro (Dark).',
        'Paleta de 5 colores de acento corporativo: Índigo, Verde Minimarket, Naranja, Cyan y Rojo.',
        'Soporte para subir o definir el Logo de la Empresa visible en Navbar, Bienvenida y Login.',
        'Diseño dinámico de grilla POS: selector en vivo entre Modo Táctil, Modo Lista Compacta y Estándar.',
        'Nueva pantalla "Acerca de..." con la línea de tiempo interactiva de toda la historia del sistema.',
    ],
    'v2.9.2' => [
        'Autorización directa de clave de supervisor en el modal de cobro para descuentos especiales.',
        'Flujo ágil con validación inmediata y atajo directo en el teclado.',
    ],
    'v2.9.1' => [
        'Supervisión configurable en caja: autorización para anular venta en proceso y eliminar productos.',
        'Descuento máximo en caja por porcentaje (%) configurable desde Ajustes Generales.',
    ],
    'v2.9.0' => [
        'Nueva pantalla de Cotizaciones: crear presupuestos y cargarlos en la caja para cobrar.',
        'Toma de Inventario físico: contar por categoría, ver diferencias y ajustar el stock de una vez.',
        'Al pausar una venta en el POS queda como cotización, visible desde Cotizaciones.',
    ],
    'v2.8.0' => [
        'Comprobante de venta con formato para impresora térmica de 80 mm.',
        'Al vender con crédito interno se imprime un comprobante de fiado con firma del cliente.',
        'El comprobante muestra los datos del local, el desglose de pagos y el vuelto.',
    ],
    'v2.7.0' => [
        'Los avisos del POS ahora son notificaciones (toasts) que no frenan la caja.',
        'Vaciar carrito y pausar venta usan ventanas del sistema, no las del navegador.',
    ],
    'v2.6.0' => [
        'Carrito del POS rediseñado: líneas más claras y botones de cantidad más grandes.',
        'La pantalla de actualización ahora muestra las novedades reales de cada versión.',
    ],
    'v2.5.0' => [
        'Productos pesables: venta por peso leyendo el código de la balanza (PLU).',
        'Ajustes de stock con varios productos y proveedor en un mismo movimiento.',
        'Facturación electrónica (DTE) con selección de proveedor en Configuración.',
    ],
    'v2.4.0' => [
        'Nueva pestaña de Configuración para la balanza de pesaje.',
        'Impresión directa del PDF del DTE al cobrar.',
    ],
    'v2.3.0' => [
        'Canje de vales usando el número de boleta, no solo el código.',
        'Editar productos sin afectar el stock; activar/desactivar productos.',
        'Menú de navegación reorganizado.',
    ],
    'v2.2.0' => [
        'Ticket de Cambio de Mercadería (TC-) con validación de saldo para nuevas compras.',
        'Módulo de Notas de Pedido: acordar cantidad y costo con el proveedor antes de recibir.',
    ],
    'v2.1.0' => [
        'Devoluciones multi-producto en una sola transacción.',
        'Emisión y canje de Vales de Devolución como saldo a favor en tienda.',
    ],
    'v2.0.0' => [
        'Reimpresión de tickets históricos y comprobantes de ventas anteriores.',
        'Abonos de crédito con imputación automática inteligente FIFO venta por venta.',
    ],
    'v1.5.0' => [
        'Centro de Ayuda y Manual de Usuario integrado dentro del sistema.',
        'Guías operativas para aperturas de turno, arqueos y administración.',
    ],
    'v1.4.0' => [
        'Motor de Promociones: descuentos por unidad y ofertas multibuy por volumen (packs).',
        'Validación automática de promociones en tiempo real en la caja registradora.',
    ],
    'v1.3.0' => [
        'Recepción de Compras como asistente en dos fases: multi-ítem y ajuste de precios/costos.',
        'Protección CSRF en los endpoints de inventario y compras.',
    ],
    'v1.2.0' => [
        'Generación de Cierre Z fiscal al cerrar turno con clave de supervisor.',
        'Registro de movimientos de caja (retiro e ingreso de efectivo) desde el POS.',
        'Redirección automática a la caja al iniciar turno.',
    ],
    'v1.1.0' => [
        'Auditoría y fortalecimiento de seguridad: sanitización y escape contra XSS en todas las vistas.',
        'Iconografía local con Font Awesome sin dependencias de internet.',
        'Control estricto de roles (Cajeros con accesos restringidos a módulos administrativos).',
    ],
    'v1.0.0' => [
        'Lanzamiento inicial: base del Punto de Venta (POS) con carrito y catálogo de productos.',
        'Gestión de turnos de caja registradora, apertura, control de efectivo y arqueo.',
        'Control de stock, catálogo de productos y módulo de clientes para ventas a crédito.',
    ],
]);

// Cargar variables desde .env (no versionado) si existe
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

// Configuración de Conexión a MySQL
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'tallerdb');

$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
if ($dbUser === false || $dbPass === false) {
    die("<div style='font-family:sans-serif; padding:20px; color:#c0392b; background:#fadbd8; border-radius:8px; margin:20px;'>
        <h2>⚠️ Faltan credenciales de base de datos</h2>
        <p>Copia <code>.env.example</code> a <code>.env</code> y completa <code>DB_USER</code> y <code>DB_PASS</code> con las credenciales reales.</p>
    </div>");
}
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("<div style='font-family:sans-serif; padding:20px; color:#c0392b; background:#fadbd8; border-radius:8px; margin:20px;'>
                <h2>⚠️ Error de conexión a la Base de Datos</h2>
                <p><strong>Detalle:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <p>Asegúrate de que el servicio MySQL en Laragon esté encendido y que la base de datos <code>" . DB_NAME . "</code> exista.</p>
            </div>");
        }
    }
    return $pdo;
}
