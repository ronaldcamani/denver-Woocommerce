# Prompt: tienda DENVER en WooCommerce

Quiero que armes la tienda en línea de mi negocio con **WordPress + WooCommerce**, lista para publicar en un hosting compartido (Hostinger, plan WordPress) para la campaña de Navidad 2026. Trabaja en este repositorio, que está vacío. Escríbeme siempre en español, con pasos que pueda copiar y pegar. Yo no soy programador.

## 1. El negocio

- Empresa: DENVER S.A.C., RUC 20610817433.
- Dirección: Mz. 32 Lote 04, P.J. El Porvenir, Miraflores, Arequipa, Perú.
- Correo: <libreriadenver@gmail.com>.
- WhatsApp: 902 022 295.
- Marca visible: "DENVER LIBRERIA".
- Rubro: útiles escolares y de oficina, al por mayor y menor. Las campañas fuertes son la escolar (enero a marzo) y Navidad (noviembre y diciembre).
- Referencia de estilo: <https://tailoy.com.pe>.

## 2. Qué debe hacer la tienda

1. **Solo campaña de Navidad al lanzar.** Se publican las categorías Navidad, Juguetes y Regalos. El resto del catálogo se importa en borrador, para activarlo en la campaña escolar.
2. **Precios en soles con IGV incluido (18 %).** Algunos productos vienen como exonerados en el Excel.
3. **Envíos**, solo dentro del Perú:
   - Delivery en Arequipa: costo negociable al interno, entrega de 12 a 24 horas.
   - Recojo en tienda: gratis.
4. **Pagos:**
   - Yape con codigo de aprovacion (Muestra instruccione).
   - Tarjeta con **Izipay** (plugin oficial). Déjalo instalado y explícame qué datos pedirle a Izipay. **Mercado Pago** queda como respaldo.
5. **Boleta o factura en el checkout.** Usa campos de checkout de bloques de WooCommerce:
   - El cliente elige boleta o factura. Es obligatorio.
   - **Boleta:** pide DNI de 8 dígitos o carné de extranjería (9 a 12 caracteres alfanuméricos).
   - **Factura:** pide RUC de 11 dígitos que empiece con 10, 15, 17 o 20, y razón social. Ambos son obligatorios.
   - Usa mensajes de error claros en español, por ejemplo "Para factura escribe un RUC de 11 dígitos."
   - Muestra los datos en el pedido del panel y en los correos.
   - Emito los comprobantes con sistema de facturacion en SUNAT SOL.
6. **Correos.** El cliente recibe la confirmación con las instrucciones de pago y la tienda recibe un aviso. Usa SMTP de Gmail con contraseña de aplicación (plugin WP Mail SMTP). Nunca me pidas la contraseña en el chat; dime dónde ponerla.
7. **Páginas legales**, enlazadas en el pie de página bajo una columna "Ayuda":
   - Términos y condiciones;
   - Política de privacidad (Ley 29733);
   - Envíos, cambios y devoluciones: 7 días para fallas o error de producto, y reembolso por el mismo medio de pago.
     Redáctalas con los datos de arriba y dime que un abogado o contador las revise.
8. **Libro de Reclamaciones virtual** (Ley 29571, INDECOPI):
   - formulario con hoja numerada;
   - copia por correo al consumidor y a la tienda;
   - respuesta desde el panel en un plazo máximo de 15 días hábiles.
     Usa un plugin peruano confiable o uno propio sencillo. Pon el enlace en el pie de página.
9. **Botón flotante de WhatsApp**

## 3. Diseño

- Colores de marca: azul marino `#0A1B5C`, azul eléctrico `#1F6BFF` y amarillo `#FFE11F`.
- Usa un tema hijo liviano (Storefront, Kadence o Blocksy). Sin page builders pesados.
- Debe verse bien en celular. La mayoría compra desde el teléfono.
- Portada:
  - banner de Navidad;
  - categorías de la campaña;
  - productos destacados;
  - franja de servicios (copias, impresiones, anillados);
  - franja de confianza (envíos en Arequipa, Yape/Plin, boleta o factura).
- Toda la tienda en español de Perú, con precios en formato "S/ 12.90".

## 4. Catálogo desde Excel

Mi sistema de ventas exporta un Excel (ReporteProductos.xlsx) con las columnas `NOMBRE`, `PRECIO`, `PRECIO SIN IGV`, `UN. MEDIDA`, `STOCK` y `CAT. PRODUCTO` (GRAVADO o EXONERADO). Haz un script que lo convierta a CSV de importación de WooCommerce con estas reglas:

- **Limpieza:**
  - nombre en formato título, con "de", "con", "y", "para" y similares en minúscula, y unidades como ml, gr, kg o cm también en minúscula;
  - quita espacios dobles;
  - descarta precios menores a S/ 0.10 o mayores a S/ 5,000, porque son marcadores del sistema;
  - une los productos con nombre repetido;
  - genera un SKU estable desde el nombre.
- **Stock:** si viene vacío o negativo, el producto se vende sin control de stock. Un producto de campaña con stock contado en 0 queda oculto.
- **Categoría por palabras del nombre**, sin tildes ni mayúsculas. Gana la primera regla que coincide, en este orden:
  1. Navidad (navidad, navideño, papá noel, grinch, reno, árbol, pino, esfera, guirnalda, nacimiento, pesebre, serie de luces, papel o bolsa de regalo, panetonera…);
  2. Mochilas y Cartucheras;
  3. Juguetes;
  4. Regalos;
  5. lo demás va a "Otros".
- **Publicación:** se publican Navidad, Juguetes y Regalos; todo lo demás queda en borrador.
- Antes de importar, muéstrame un resumen: cuántos productos se publican, cuántos quedan en borrador, cuántos se descartan y por qué, y cuántos van a cada categoría.
- No tengo fotos todavía. Usa una imagen genérica con el logo y deja claro cómo subir las fotos después.

## 5. Cómo trabajar

- Para probar en local usa `@wordpress/env` (wp-env) con Docker, en un puerto libre (por ejemplo 8890)
- Deja comandos simples en `package.json`: `npm run tienda:start`, `npm run tienda:stop`, `npm run tienda:csv -- "ruta/al/Excel.xlsx"` y `npm run tienda:importar`.
- Lo propio va en el repositorio: tema hijo, un plugin `denver-configuracion` con los campos de comprobante, las reglas de pago y los ajustes, el script del Excel y un script de configuración inicial con WP-CLI. Moneda, país, IGV, envíos, páginas y menús deben quedar listos con un solo comando.
- **Para publicar**, escribe una guía paso a paso para Hostinger:
  - subir el tema y el plugin como ZIP;
  - instalar los plugins de terceros;
  - importar el CSV;
  - activar el SSL;
  - configurar el correo;
  - hacer respaldos.
- Nunca subas claves ni contraseñas al repositorio ni me las pidas en el chat. Si necesitas una clave de API de WooCommerce, dime cómo crearla y dónde ponerla.
- Trabaja en ramas con pull requests pequeños. Prueba cada cosa antes de decirme que está lista y muéstrame capturas del checkout y del panel.

## 6. Orden sugerido

1. Entorno local, tema hijo con la marca y configuración básica: Perú, soles, IGV y envíos.
2. Script del Excel e importación del catálogo de Navidad.
3. Checkout: boleta o factura, Yape/Plin y pago al recoger.
4. Correos, páginas legales y Libro de Reclamaciones.
5. Izipay y Mercado Pago.
6. Guía de publicación en Hostinger.
