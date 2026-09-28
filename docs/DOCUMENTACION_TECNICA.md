# 📘 Documentación Técnica - Sistema Taller Mecánico + POS
**Versión:** 5.4.1  
**Arquitectura:** PHP 8.1+ / MySQL (InnoDB) / Vanilla JavaScript / CSS Custom Theme  

> El sistema nació como el POS de minimarket (v1 a v3) y evolucionó al taller mecánico con venta de repuestos (v4 en adelante). Las secciones de ventas, caja e inventario siguen vigentes; el flujo de taller (Recepción → Diagnóstico → Presupuesto → Reparación y cobro → Entrega) se agregó encima.

---

## 1. Propósito del Sistema
El sistema es una solución integral de **Punto de Venta (POS), Gestión Comercial, Inventario y Facturación Electrónica** diseñada específicamente para el comercio minorista chileno (minimarkets, botillerías, almacenes y distribuidoras de abarrotes).

Permite administrar el ciclo completo del negocio:
* Venta ágil en caja mediante escáner de código de barras y balanza digital.
* Control estricto de turnos de cajero, arqueos ciegos y movimientos de efectivo.
* Manejo de inventario con múltiples códigos de barra por producto (packs, cajas, unidades).
* Precios diferenciados, promociones automáticas (Multibuy, descuentos, ofertas).
* Crédito a clientes de confianza (Fiados) y sistema de fidelización por puntos.
* Emisión de Boleta y Factura Electrónica conforme a las normativas del SII (Chile).

---

## 2. Stack Tecnológico

| Capa | Tecnología / Herramienta | Descripción |
| :--- | :--- | :--- |
| **Backend** | **PHP 8.1+** (Nativo / Procedural estructurado) | Sin dependencias de frameworks pesados, optimizado para alto rendimiento y baja latencia en Apache/Nginx. |
| **Base de Datos** | **MySQL 5.7 / 8.0 / MariaDB** | Motor **InnoDB**, codificación utf8mb4_unicode_ci, integridad referencial con claves foráneas y transacciones ACID. |
| **Conexión DB** | **PDO (PHP Data Objects)** | Configurado en modo estricto de excepciones (ATTR_ERRMODE => ERRMODE_EXCEPTION) y consultas preparadas obligatorias. |
| **Frontend** | **HTML5 Semántico + JavaScript Vanilla (ES6+)** | Sin frameworks como React/Vue; manipulación directa del DOM para máxima velocidad y compatibilidad en navegadores POS. |
| **Diseño / Estilos** | **CSS3 Personalizado (CSS Variables)** | Soporte nativo para modo oscuro/claro, diseño responsivo, adaptado a monitores táctiles y pantallas de 1024px o superiores. |
| **Iconografía / Gráficos** | **FontAwesome 6 + Chart.js** | Visualización de KPIs ejecutivos, gráficos de ventas y mapa de horas peak. |
| **Seguridad** | **BCrypt + Tokens CSRF + Auth Session** | Encriptación de contraseñas de alta seguridad, protección contra ataques CSRF en todas las APIs POST, y aislamiento de sesiones de cajero. |

---

## 3. Estructura del Proyecto

```
tallermecanico-php/
├── *.php                      # Controladores de página: validan permisos, consultan y cargan su vista
├── views/                     # Plantillas HTML de cada página (x.php → views/x.view.php)
│   └── partials/              # Fragmentos reutilizables (ej. totales del presupuesto)
├── api/                       # Endpoints JSON que consumen las pantallas vía fetch
│
├── includes/
│   ├── core/                  # auth.php: sesión, roles, CSRF y helpers base (lo cargan todas las páginas)
│   ├── layout/                # header.php, footer.php, taller_ui.php (menú, pie y piezas de UI del taller)
│   ├── dominio/               # Reglas del negocio compartidas entre páginas y APIs
│   │   ├── promociones_combos.php   # Combos y ofertas (mismo cálculo en Caja y Presupuesto)
│   │   ├── servicios.php            # Catálogo de servicios / mano de obra
│   │   ├── busqueda_repuestos.php   # Búsqueda de repuestos por nombre, marca o N° de parte
│   │   ├── repuestos_aprendizaje.php# Compatibilidad aprendida de repuestos por modelo
│   │   └── fiscal.php               # Cierre Z
│   └── integraciones/         # Servicios externos
│       ├── whatsapp_helper.php
│       ├── vehiculo_api_helper.php  # Consulta de patentes
│       └── sii/                     # Facturación electrónica (drivers Mock y OpenFactura)
│
├── config/
│   ├── database.php           # Lectura de .env y conexión PDO (incluye version.php)
│   └── version.php            # APP_VERSION y APP_CHANGELOG (novedades por versión)
│
├── db/
│   ├── schema_actual.sql      # Esquema completo vigente (instalación nueva)
│   └── migrations/            # Cambios de esquema en orden (ver README.md para el orden)
│
├── assets/                    # css/, js/, icons/, vendor/ (FontAwesome) y docs/ (DTE de ejemplo)
├── docs/                      # Esta documentación y propuestas comerciales (docs/propuestas/)
├── uploads/                   # Archivos subidos (no versionado)
├── manifest.json, service-worker.js   # PWA y venta offline
└── .env.example               # Plantilla de credenciales de base de datos
```

### Dónde va cada cosa nueva
- **Pantalla nueva:** `nombre.php` en la raíz + `views/nombre.view.php`, y su enlace en `includes/layout/header.php`.
- **Endpoint JSON:** `api/nombre.php` (carga `config/database.php` y `includes/core/auth.php`).
- **Regla de negocio usada en más de un lugar:** `includes/dominio/`.
- **Conexión con un servicio externo:** `includes/integraciones/`.
- **Cambio de base de datos:** nueva migración `db/migrations/NNNN_descripcion.sql` y actualizar `db/schema_actual.sql`.
- **Versión:** subir `APP_VERSION` y agregar sus novedades en `config/version.php`.


---

## 4. Lógica de Negocio y Algoritmos Críticos

### A. Núcleo de Ventas (api/registrar_venta.php)
1. **Transaccionalidad Estricta:** Todo el proceso de venta corre dentro de una transacción BEGIN TRANSACTION ... COMMIT con bloqueos pesimistas SELECT ... FOR UPDATE sobre los productos vendidos para evitar colisiones de stock concurrente.
2. **Validación de Turno:** La venta se rechaza si el cajero no tiene un turno con estado 'Abierto' en la tabla turnos.
3. **Múltiples Medios de Pago:** Soporta pagos simples y mixtos (Efectivo, Tarjeta Debito, Tarjeta Credito, Transferencia, Credito Interno / Fiado, Puntos, Vale Devolucion).
4. **Descuento de Stock por Factor:**
   * Si se vende un producto individual: unidadesFisicas = cantidad.
   * Si se vende un Six Pack o Caja (código alternativo): unidadesFisicas = cantidad * factor. Se descuenta el inventario real de la unidad base.
5. **Generación de Deuda / Puntos:**
   * Si se paga con crédito: se registra en cuentascorrientes y se suma al saldo deudor del cliente.
   * Si la venta suma puntos: se actualiza el acumulador del cliente registrado.

### B. Múltiples Códigos de Barra y Jerarquía de Precios
Un mismo producto físico (ProductoID) puede tener múltiples códigos asociados en la tabla productoscodigos:
* **Código Principal:** En la tabla productos (ej. Código EAN de la lata individual).
* **Códigos Secundarios / Packs:** En productoscodigos con su propio factor multiplicador y precio opcional:
  * **Regla 1:** Si el código alternativo tiene un precio fijo explícito (PrecioVenta > 0), se aplica ese precio.
  * **Regla 2:** Si existe una promoción activa de tipo MULTIBUY (ej. 3x.000) y la cantidad del pack calza con el múltiplo de la promo, se aplica automáticamente el precio promocional.
  * **Regla 3:** Si no tiene precio fijo ni promo, el precio unitario se multiplica por el factor de unidades.

### C. Integración con Balanzas Electrónicas (Códigos EAN-13)
En assets/js/pos.js se implementa la decodificación en tiempo real de etiquetas generadas por balanzas pesables:
* Prefijo configurable (por defecto 20).
* Estructura estándar EAN-13: 20 [PLU de 4 o 5 dígitos] [Peso o Importe en gramos/pesos] [Dígito Verificador].
* Si el producto está marcado como EsPesable = TRUE, el sistema divide automáticamente el peso leído por 1.000 para obtener los kilos exactos y calcula el total instantáneamente.

### D. Sistema de Supervisión y Bloqueo (Lock Screen)
Para evitar fraudes o errores en caja:
* **Cancelación de Venta y Eliminación de Ítems:** Pueden configurarse para exigir la contraseña de un usuario con rol 'Supervisor' o 'Administrador' (api/autorizar_supervisor.php).
* **Límite de Descuento:** Si el cajero intenta hacer un descuento superior al porcentaje permitido en configuraciones (POS_DESCUENTO_MAX_PORC), la venta se bloquea hasta que un supervisor ingrese su clave.
* **Bloqueo Rápido de Pantalla:** Atajo Alt + L o F9: oculta los datos de la venta y bloquea la terminal manteniendo intacto el carrito en memoria. Se desbloquea con el PIN del cajero o cualquier supervisor.

### E. Integración Tributaria DTE (SII Chile)
El sistema está desacoplado para conectarse con el motor tributario (sii-boleta en http://localhost/sii-boleta/api/emitir.php):
* Genera el payload con RutEmisor, detalle de líneas, montos netos e IVA 19%.
* Recibe el TrackID, número de Folio oficial y la cadena del Timbre Electrónico DTE (**TED**) para renderizar el código de barras bidimensional **PDF417** en el ticket térmico de 58mm u 80mm.

---

## 5. Esquema de Base de Datos (Tablas Clave)

```sql
-- Productos e Inventario
productos (ProductoID, CodigoBarras, Nombre, PrecioVenta, CostoCompra, Stock, StockMinimo, EsPesable, CodigoPLU, CategoriaID, Activo)
productoscodigos (CodigoID, ProductoID, CodigoBarras, Descripcion, Cantidad, PrecioVenta)
categorias (CategoriaID, Nombre, Activo)
promociones (PromocionID, ProductoID, Tipo, CantidadMinima, DescuentoPorcentaje, PrecioOferta, FechaInicio, FechaFin, Activa)

-- Ventas y Transacciones
ventas (VentaID, TurnoID, UsuarioID, ClienteID, FechaVenta, Subtotal, Descuento, Total, MetodoPago, TipoDocumento, FolioDTE, Estado)
detalleventas (DetalleID, VentaID, ProductoID, Cantidad, PrecioUnitario, Subtotal, CostoHistorico)
pagosventas (PagoID, VentaID, Metodo, Monto)

-- Turnos y Caja
turnos (TurnoID, UsuarioID, FechaApertura, FechaCierre, MontoApertura, MontoCierreReal, Estado, TotalVentasEfectivo, ...)
movimientoscaja (MovimientoID, TurnoID, Tipo, Monto, Motivo, Fecha)

-- Clientes y Créditos
clientes (ClienteID, Nombre, RutCuerpo, RutDv, Telefono, LimiteCredito, SaldoDeudor, PuntosAcumulados, Activo)
cuentascorrientes (MovimientoID, ClienteID, VentaID, Tipo, Monto, SaldoResultante, Fecha)

-- Ajustes y Vales
valescanjes (ValeID, Codigo, Monto, Estado, VentaOrigenID, FechaEmision)
ajustesstock (AjusteID, ProductoID, Tipo, Cantidad, Motivo, Fecha, UsuarioID)
configuraciones (Clave, Valor, Descripcion)
```

---

## 6. Consideraciones para Desarrollos Futuros (PWA Offline)
Al evolucionar hacia una **Progressive Web App (PWA) con venta offline**:
1. **IndexedDB:** Debe replicar la estructura de productos y productoscodigos para permitir la búsqueda instantánea sin conexión.
2. **Cola de Sincronización:** Las ventas offline deben estructurarse con la misma firma que espera api/registrar_venta.php y sincronizarse en lote mediante un nuevo endpoint idempotente (api/sincronizar_offline.php).
3. **Turno de Caja Local:** El número de turno activo debe persistir en la sesión del navegador para que el cajero pueda seguir vendiendo bajo el turno que ya abrió con conexión.
