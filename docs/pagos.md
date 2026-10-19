# Pagos de la tienda DENVER

| Medio | Cómo está | Qué haces tú |
|---|---|---|
| Yape con código | Hecho (plugin `denver-configuracion`) | Revisar tu app y pasar el pedido a "Procesando" |
| Pago al recoger | Activado, solo si el cliente elige "Recojo en tienda" | Nada |
| Mercado Pago | Plugin oficial instalado, **desactivado** hasta que pongas tus claves | Ver abajo |
| Izipay | Plugin oficial (ZIP que te da Izipay) | Ver abajo |

Las claves **nunca** van en el chat ni en el repositorio: se pegan solo en el panel de WordPress.

## Mercado Pago
1. En tu cuenta de Mercado Pago: Tus negocios → Configuración → Credenciales.
2. Copia la Public Key y el Access Token de **pruebas**.
3. En WordPress: WooCommerce → Ajustes → Pagos → Mercado Pago → Configurar. Pega las credenciales de pruebas.
4. Haz una compra de prueba con las tarjetas de prueba de Mercado Pago.
5. Para vender de verdad, repite con las credenciales de **producción** y activa.
6. Deja desactivado el "Yape" de Mercado Pago: ya tienes tu propio Yape con código.

## Izipay
1. Pídele a Izipay: **Código de comercio**, **clave de pruebas** y **clave de producción**, y el ZIP del plugin de WooCommerce.
2. WordPress: Plugins → Añadir nuevo → Subir plugin → elige el ZIP → Activar.
3. WooCommerce → Ajustes → Pagos → Izipay → pega Código de comercio y clave de pruebas.
4. Compra de prueba con las tarjetas de prueba de Izipay.
5. Para vender de verdad, cambia a las claves de producción.
