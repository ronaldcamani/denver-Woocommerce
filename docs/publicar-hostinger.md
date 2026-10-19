# Guía para publicar la tienda DENVER en Hostinger

Plan recomendado: **WooCommerce Hosting – Unlimited** (SSL, copias diarias y WooCommerce de un clic incluidos).
Haz los pasos en orden. Todo lo que dice `así` se copia y pega.

## 0. Antes de empezar (en tu computadora)
1. Genera los ZIP del tema y del plugin:
   ```
   cd ~/Projects/denver-Woocommerce
   npm run tienda:zip
   ```
   Quedan en `salida/zip/`: `denver-storefront.zip` (tema) y `denver-configuracion.zip` (plugin).
2. Si ya tienes tu Excel final: `npm run tienda:csv -- "ruta/al/Excel.xlsx"`. Genera `salida/productos_woocommerce.csv`.
3. Ten a mano: el ZIP de Izipay y las credenciales de Mercado Pago e Izipay (modo pruebas primero).

## 1. Contratar y crear el sitio
1. Compra el plan y tu dominio (por ejemplo `libreriadenver.com`).
2. hPanel → **Sitios web → Añadir sitio web → WooCommerce**. Elige idioma **Español** y crea tu usuario administrador con una clave larga que **solo tú** guardas.
3. Cuando termine, entra a `https://tudominio.com/wp-admin`.

## 2. Activar el SSL (candado)
hPanel → **Seguridad → SSL**: debe decir *Activo*. Luego en WordPress → Ajustes → Generales, verifica que las dos URL empiecen con `https://`. Activa **Forzar HTTPS** en hPanel.

## 3. Subir tema y plugin
1. WordPress → **Apariencia → Temas → Añadir nuevo → Subir tema**: sube `storefront` (búscalo en "Añadir nuevo" e instálalo, es el tema padre) y luego `denver-storefront.zip`. **Activa denver-storefront**.
2. **Plugins → Añadir nuevo → Subir plugin**: sube `denver-configuracion.zip` y activa.

## 4. Instalar los plugins de terceros
Plugins → Añadir nuevo, busca e instala (y activa):
- **WP Mail SMTP** (correos con Gmail)
- **Mercado Pago payments for WooCommerce**
- **Izipay**: sube el ZIP que te dio Izipay (Plugins → Subir plugin)

## 5. Configuración automática de la tienda (país, soles, IGV, envíos, páginas)
Necesita acceso SSH (hPanel → **Avanzado → Acceso SSH**; actívalo y copia el comando de conexión). Si tu plan no lo muestra, pregunta al chat de Hostinger "¿tengo SSH y WP-CLI?" y avísame.
1. Sube la carpeta del proyecto `scripts/` y `contenido/` a `public_html/` con el **Administrador de archivos** (arrastra las dos carpetas).
2. Conéctate por SSH con el comando de hPanel y ejecuta:
   ```
   cd ~/domains/tudominio.com/public_html
   HOSTING=1 bash scripts/configurar.sh
   ```
3. Cuando termine, **borra** de `public_html` las carpetas `scripts/` y `contenido/`.

## 6. Correo con Gmail
Sigue `docs/correo-gmail.md` (contraseña de aplicación y bloque para `wp-config.php`). Pruébalo: WP Mail SMTP → Herramientas → **Enviar correo de prueba**.

## 7. Cargar los productos
Opción A (simple): WooCommerce → **Productos → Importar** → sube `productos_woocommerce.csv` → *Continuar* → *Ejecutar el importador*. Si da error de tiempo, divide el CSV en partes de ~500 filas.
Opción B (por SSH, para el catálogo completo): sube el CSV a `public_html/` y ejecuta:
```
wp eval "$(cat scripts/importar.php)"
```
(sube también `scripts/` otra vez para este paso y bórralo después).
Al terminar, revisa que se vean solo Navidad, Juguetes y Regalos.

## 8. Fotos de productos
Hoy todos usan la imagen genérica con el logo. Para poner la foto real: Productos → el producto → **Imagen del producto**. Usa el **SKU** (`DEN-...`) como nombre del archivo para ubicarla fácil. Para muchas fotos a la vez, súbelas a Medios y pon sus URL en la columna `Images` del CSV y reimporta con "Actualizar productos existentes".

## 9. Pagos
Sigue `docs/pagos.md`. Primero modo **pruebas** y haz una compra completa; después cambia a **producción**.
Deja activos: Yape, Pago al recoger, Mercado Pago, Izipay.

## 10. Tareas programadas (importante)
WooCommerce deja tareas pendientes tras importar productos. Para que se procesen bien:
1. hPanel → **Avanzado → Cron Jobs** → cada 5 minutos: `php ~/domains/tudominio.com/public_html/wp-cron.php`
2. Agrega en `wp-config.php` (antes de "That's all"): `define( 'DISABLE_WP_CRON', true );`

## 11. Copias de seguridad
El plan Unlimited hace copias **diarias**. Antes de cada cambio grande: hPanel → **Copias de seguridad → Generar**. Para restaurar: misma pantalla → *Restaurar*.

## 12. Limpieza y revisión final antes de anunciar
- [ ] Borra pedidos y productos de prueba, y los reclamos de prueba (Libro de Reclamaciones).
- [ ] Compra de prueba con boleta (DNI) y con factura (RUC), con Yape, Mercado Pago, Izipay y pago al recoger.
- [ ] Llega el correo al cliente y a libreriadenver@gmail.com.
- [ ] Revisa en el celular: portada, categorías, carrito y finalizar compra.
- [ ] Páginas legales revisadas por tu abogado o contador (Términos, Privacidad, Envíos y devoluciones, Libro de Reclamaciones).
- [ ] Ajustes → Lectura: *Disuadir a los motores de búsqueda* **desmarcado**.
- [ ] Define el costo del delivery en WooCommerce → Ajustes → Envío → Arequipa.
- [ ] Comprobantes: emítelos desde tu sistema SUNAT SOL usando el DNI/RUC y la razón social que aparecen en cada pedido.
