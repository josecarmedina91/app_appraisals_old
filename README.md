# App Appraisals

Aplicación PHP para gestión de inspecciones y generación de reportes PDF.

## Requisitos
- PHP 7.4+ (o superior)
- Composer
- Servidor web (Apache/Nginx)

## Configuración
1. Instalar dependencias:
   ```bash
   composer install
   ```
2. Configurar conexión a base de datos:
   - Copiar `config/db_connect.sample.php` a `config/db_connect.php`.
   - Editar `config/db_connect.php` con tus credenciales reales.
3. Permisos de escritura (si el servidor necesita generar archivos):
   - `export_pdf/`
   - `export_cloud/`

## Seguridad y datos
- Los siguientes directorios/archivos se excluyen del repositorio por contener datos sensibles o generados:
  - `config/db_connect.php`
  - `backup_db/`
  - `export_pdf/`
  - `export_cloud/`
  - `img/photo_gallery/`
  - `vendor/`

Para producción/entornos nuevos, instala dependencias con `composer install` y provee tus propias credenciales.

## Estructura
- `form_export/`: scripts de exportación y generación de PDF.
- `component/`: componentes PHP reutilizables.
- `css/`, `js/`: recursos estáticos.
- `vendor/`: dependencias instaladas por Composer (ignorado en git).

## Desarrollo
- Rama principal: `main`.
- Flujo estándar: PRs hacia `main`.

## Instalación rápida
Consulta la guía completa en `INSTALL.md`. Resumen:
1. `composer install`
2. Copia `config/db_connect.sample.php` a `config/db_connect.php` y pon tus credenciales.
3. Crea directorios ignorados: `export_pdf/`, `export_cloud/`, `img/photo_gallery/` y asigna permisos.
4. Levanta el servidor: `php -S localhost:8000` y abre `http://localhost:8000/login_index.html`.

## Notas
- Si necesitas subir `vendor/` por alguna restricción del servidor, actualiza `.gitignore` y ten en cuenta el tamaño del repositorio.