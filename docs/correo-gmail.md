# Correo de la tienda con Gmail (WP Mail SMTP)

La tienda envía los correos (confirmación de pedido, aviso a la tienda, Libro de Reclamaciones) con el plugin **WP Mail SMTP**,
usando la cuenta `libreriadenver@gmail.com`. La contraseña **nunca** va en el repositorio ni en el chat: va solo en el archivo
`wp-config.php` de Hostinger.

## 1. Crear la contraseña de aplicación en Google (una sola vez)

1. Entra a <https://myaccount.google.com/security> con `libreriadenver@gmail.com`.
2. Activa la **Verificación en dos pasos** si aún no la tienes.
3. Busca **Contraseñas de aplicaciones** (en el buscador de la página), crea una llamada `Tienda DENVER` y copia las 16 letras.
   Guárdalas en un lugar seguro: Google no las vuelve a mostrar.

## 2. Ponerla en Hostinger

1. hPanel → **Sitios web** → tu sitio → **Administrador de archivos** → `public_html/wp-config.php`.
2. Pega este bloque **antes** de la línea que dice `/* That's all, stop editing! */` y cambia `LA_CONTRASEÑA_DE_APLICACIÓN`:

```php
define( 'WPMS_ON', true );
define( 'WPMS_MAILER', 'smtp' );
define( 'WPMS_SMTP_HOST', 'smtp.gmail.com' );
define( 'WPMS_SMTP_PORT', 587 );
define( 'WPMS_SSL', 'tls' );
define( 'WPMS_SMTP_AUTH', true );
define( 'WPMS_SMTP_AUTOTLS', true );
define( 'WPMS_SMTP_USER', 'libreriadenver@gmail.com' );
define( 'WPMS_SMTP_PASS', 'LA_CONTRASEÑA_DE_APLICACIÓN' );
define( 'WPMS_MAIL_FROM', 'libreriadenver@gmail.com' );
define( 'WPMS_MAIL_FROM_NAME', 'DENVER LIBRERIA' );
```

3. Guarda el archivo.

## 3. Probar

En el panel de WordPress: **WP Mail SMTP → Herramientas → Prueba de correo**. Escribe tu correo y envía. Debe llegar en segundos
(revisa también spam la primera vez).

## Notas

- Gmail permite cerca de 500 correos por día desde una cuenta normal; para una tienda pequeña alcanza.
- Si los correos no llegan, revisa **WP Mail SMTP → Herramientas → Registro de errores**.
- En tu computadora, los correos de prueba no salen a internet: llegan al buzón local http://localhost:8025 (Mailpit).
