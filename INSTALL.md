# Guía de Instalación y Puesta en Marcha

Esta guía explica cómo clonar, instalar dependencias, configurar la base de datos y arrancar la aplicación.

## Requisitos
- PHP 7.4+ (recomendado 8.x)
- Composer
- Servidor web: Apache o Nginx (para producción) o servidor embebido de PHP (para desarrollo)
- Extensiones PHP:
  - Obligatoria: pdo_mysql
  - Recomendadas: mbstring, gd (imágenes/DOMPDF), intl (opcional)

## 1) Clonar el repositorio
```bash
# Con HTTPS
git clone https://github.com/josecarmedina91/app_appraisals_old
cd app_appraisals_old
```

## 2) Instalar dependencias
```bash
composer install
```
Esto descargará las librerías dentro de `vendor/`.

## 3) Configurar la conexión a la base de datos
1. Copiar el archivo de ejemplo y editar credenciales reales:
   ```bash
   cp config/db_connect.sample.php config/db_connect.php
   ```
2. Abrir `config/db_connect.php` y ajustar:
   - `$host` (ej. `localhost`)
   - `$dbname` (nombre de tu base de datos)
   - `$username` y `$password`

## 4) Crear la base de datos y el esquema
- Crea una base MySQL/MariaDB con el nombre definido en `$dbname`.
- Importa tu archivo `.sql` de esquema/tablas si lo tienes disponible.
- Ejemplo (si tienes el archivo `schema.sql`):
  ```bash
  mysql -u TU_USUARIO -p TU_BASE_DE_DATOS < schema.sql
  ```

> Nota: Por seguridad, los backups y datos sensibles no se incluyen en el repositorio.

## 5) Crear directorios ignorados y asignar permisos
Como estos directorios están en `.gitignore`, debes crearlos manualmente si la app genera archivos:
```bash
mkdir -p export_pdf
mkdir -p export_cloud
mkdir -p img/photo_gallery
```
Asigna permisos de escritura al usuario del servidor web (ajusta según tu entorno):
```bash
chmod -R 775 export_pdf export_cloud img/photo_gallery
# En algunos entornos, puede ser necesario 777 (no recomendado en producción):
# chmod -R 777 export_pdf export_cloud img/photo_gallery
```

## 6) Arrancar la aplicación
### Opción A: Servidor embebido de PHP (desarrollo)
```bash
php -S localhost:8000
```
Luego abre en el navegador:
```
http://localhost:8000/login_index.html
```

### Opción B: Apache (producción)
- Habilita `mod_rewrite`.
- Configura el VirtualHost apuntando al directorio del proyecto.
- Ejemplo (ajusta rutas según tu servidor):
```
<VirtualHost *:80>
    ServerName tu-dominio.com
    DocumentRoot /var/www/app_appraisals_old

    <Directory /var/www/app_appraisals_old>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/app_appraisals_error.log
    CustomLog ${APACHE_LOG_DIR}/app_appraisals_access.log combined
</VirtualHost>
```

### Opción C: Nginx (producción)
- Configura el `root` y pasa `.php` a `php-fpm`.
- Asegúrate de permitir acceso a recursos estáticos (css, js, img).

## 7) Comprobaciones y resolución de problemas
- Error de conexión a la DB: revisa `config/db_connect.php`, credenciales y que el servicio MySQL esté activo.
- Falta `pdo_mysql`: instala/activa la extensión en tu PHP.
- Problemas de permisos al generar PDFs/imagenes: revisa permisos de `export_pdf`, `export_cloud`, `img/photo_gallery`.
- Rutas/rewrites: verifica que `.htaccess` esté activo y que `mod_rewrite` esté habilitado en Apache.

## 8) Buenas prácticas
- No subas credenciales reales al repositorio.
- Mantén `vendor/` ignorado y usa `composer install` en cada entorno.
- Considera variables de entorno o un archivo de configuración por entorno para producción.

## 9) Opcional: Docker Compose
Si quieres, se puede agregar un `docker-compose.yml` con servicios (Apache + PHP + MySQL) para un arranque con un comando. Solicítalo si lo necesitas y lo incluimos.

---
Cualquier duda o mejora que desees, abre un issue en el repositorio o ponte en contacto.