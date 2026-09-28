# Taller Mecánico + POS

Sistema web para taller mecánico con venta de repuestos: flujo de órdenes de trabajo
(Recepción → Diagnóstico → Presupuesto → Reparación y cobro → Entrega), punto de venta,
caja y turnos, inventario con Kardex, compras, promociones/combos, reportes y
facturación electrónica (SII Chile).

**Stack:** PHP 8.1+ sin framework · MySQL/MariaDB (InnoDB) · JavaScript y CSS sin frameworks · PWA con venta offline.

## Instalación local (Laragon)

1. Clonar en `C:\laragon\www\tallermecanico-php`.
2. Copiar `.env.example` a `.env` y completar las credenciales de MySQL.
3. Crear la base de datos (`tallerdb` por defecto) e importar `db/schema_actual.sql`.
4. Abrir `http://localhost/tallermecanico-php/login.php`.

### Migraciones

`db/schema_actual.sql` ya contiene el esquema completo. Si se actualiza una base
existente, aplicar los archivos de `db/migrations/` en orden alfabético.
Ojo: `0016b_mantenimiento_intervalos.sql` va después de `0016` porque modifica
la tabla `estacionservicio_ot` que crea esa migración.

## Estructura

| Carpeta | Contenido |
| --- | --- |
| raíz (`*.php`) | Controladores de cada pantalla |
| `views/` | HTML de cada pantalla (`x.php` → `views/x.view.php`) |
| `api/` | Endpoints JSON |
| `includes/core/` | Sesión, roles y CSRF |
| `includes/layout/` | Menú, pie de página y piezas de UI del taller |
| `includes/dominio/` | Reglas de negocio compartidas (combos, servicios, repuestos, cierre Z) |
| `includes/integraciones/` | WhatsApp, consulta de patentes y SII |
| `config/` | Conexión (`database.php`) y versión/novedades (`version.php`) |
| `db/` | Esquema y migraciones |
| `assets/` | CSS, JS, íconos y librerías |
| `docs/` | Documentación técnica y propuestas comerciales |

Más detalle en [docs/DOCUMENTACION_TECNICA.md](docs/DOCUMENTACION_TECNICA.md).

## Versionado

La versión vive en `config/version.php` (`APP_VERSION` + `APP_CHANGELOG`).
Parche (`x.y.Z`) para arreglos y ajustes; minor (`x.Y.0`) para funciones nuevas.
