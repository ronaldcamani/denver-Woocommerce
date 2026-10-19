#!/usr/bin/env bash
# Configuración inicial de la tienda local: Perú, soles, IGV 18 % incluido y envíos.
# Se puede ejecutar varias veces sin duplicar nada.
set -euo pipefail

# En Hostinger (por SSH, dentro de la carpeta del sitio) se usa:  HOSTING=1 bash configurar.sh
cd "$(dirname "$0")/.."
HOSTING="${HOSTING:-0}"
if [ "$HOSTING" != "1" ] && [ -z "${DOCKER_HOST:-}" ] && [ -S "/run/user/$(id -u)/podman/podman.sock" ]; then
  export DOCKER_HOST="unix:///run/user/$(id -u)/podman/podman.sock"
fi
if [ "$HOSTING" = "1" ]; then
  wp() { command wp "$@" 2>/dev/null; }
else
  wp() { docker compose --profile cli run --rm -T cli "$@" 2>/dev/null; }
  # Clave del administrador local: se genera una vez y queda en .env.local (no se sube a git).
  [ -f .env.local ] || echo "ADMIN_PASS=$(head -c 12 /dev/urandom | base64 | tr -d '/+=')" > .env.local
  . ./.env.local
fi

if [ "$HOSTING" != "1" ] && ! wp core is-installed; then
  echo "→ Instalando WordPress"
  wp core install --url=http://localhost:8890 --title="DENVER LIBRERIA" \
    --admin_user=admin --admin_password="$ADMIN_PASS" --admin_email=libreriadenver@gmail.com --skip-email
fi
wp plugin is-installed woocommerce || wp plugin install woocommerce --activate
wp theme is-installed storefront || wp theme install storefront

echo "→ Idioma y zona horaria"
wp language core install es_PE --activate >/dev/null 2>&1 || wp language core install es_ES --activate
wp option update timezone_string "America/Lima"
wp option update blogname "DENVER LIBRERIA"
wp option update blogdescription "Útiles escolares y de oficina - Arequipa"

echo "→ Plugin y tema"
wp plugin activate woocommerce denver-configuracion
wp theme activate denver-storefront

echo "→ Traducciones al español de Perú"
# WooCommerce y Storefront no publican es_PE: se instala es_ES y se copia con nombre es_PE.
wp language plugin install woocommerce es_ES >/dev/null 2>&1 || true
wp language theme install storefront es_ES >/dev/null 2>&1 || true
COPIAR_ES='
  for d in plugins themes; do
    cd "$1/wp-content/languages/$d" 2>/dev/null || continue
    for f in *-es_ES*; do cp -f "$f" "${f/es_ES/es_PE}"; done
  done'
if [ "$HOSTING" = "1" ]; then
  bash -c "$COPIAR_ES" _ "$(command wp eval 'echo WP_CONTENT_DIR;' | sed 's#/wp-content$##')"
else
  docker compose exec -T wordpress bash -c "$COPIAR_ES" _ /var/www/html 2>/dev/null
  docker compose exec -T wordpress chown -R www-data:www-data /var/www/html/wp-content/languages 2>/dev/null
fi

echo "→ País, moneda y formato"
wp option update woocommerce_default_country "PE:ARE"
wp option update woocommerce_allowed_countries specific
wp option update woocommerce_specific_allowed_countries '["PE"]' --format=json
wp option update woocommerce_ship_to_countries specific
wp option update woocommerce_specific_ship_to_countries '["PE"]' --format=json
wp option update woocommerce_currency PEN
wp option update woocommerce_currency_pos left_space
wp option update woocommerce_price_decimal_sep "."
wp option update woocommerce_price_thousand_sep ","
wp option update woocommerce_price_num_decimals 2

echo "→ Unidades y dirección de la tienda"
wp option update woocommerce_weight_unit kg
wp option update woocommerce_dimension_unit cm
wp option update woocommerce_store_address "Mz. 32 Lote 04, P.J. El Porvenir"
wp option update woocommerce_store_city "Miraflores, Arequipa"

echo "→ IGV 18 % incluido en los precios"
wp option update woocommerce_calc_taxes yes
wp option update woocommerce_prices_include_tax yes
wp option update woocommerce_tax_based_on base
wp option update woocommerce_tax_display_shop incl
wp option update woocommerce_tax_display_cart incl

if [ "$(wp db query "SELECT COUNT(*) FROM wp_woocommerce_tax_rates WHERE tax_rate_name='IGV'" --skip-column-names)" = "0" ]; then
  wp db query "INSERT INTO wp_woocommerce_tax_rates (tax_rate_country,tax_rate_state,tax_rate,tax_rate_name,tax_rate_priority,tax_rate_compound,tax_rate_shipping,tax_rate_order,tax_rate_class) VALUES ('PE','','18.0000','IGV',1,0,0,1,'')"
fi
# Clase "Exonerado": productos sin IGV.
wp wc tax_class create --name="Exonerado" --user=1 >/dev/null 2>&1 || true
if [ "$(wp db query "SELECT COUNT(*) FROM wp_woocommerce_tax_rates WHERE tax_rate_class='exonerado'" --skip-column-names)" = "0" ]; then
  wp db query "INSERT INTO wp_woocommerce_tax_rates (tax_rate_country,tax_rate_state,tax_rate,tax_rate_name,tax_rate_priority,tax_rate_compound,tax_rate_shipping,tax_rate_order,tax_rate_class) VALUES ('PE','','0.0000','Exonerado',1,0,0,1,'exonerado')"
fi

echo "→ Envíos (solo Perú)"
wp eval "$(cat scripts/envios.php)"

echo "→ Pagos"
wp option update woocommerce_denver_yape_settings '{"enabled":"yes","title":"Yape","description":"Paga con Yape y escribe el código de aprobación.","numero":"902022295","titular":"DENVER S.A.C."}' --format=json

# Pago al recoger (solo con recojo en tienda) y Mercado Pago (las claves las pone la dueña en el panel)
wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Pago al recoger en tienda","description":"Paga en efectivo cuando recojas tu pedido en la tienda.","instructions":"Te avisaremos por WhatsApp cuando tu pedido esté listo para recoger. Paga en efectivo al retirarlo.","enable_for_methods":["local_pickup"],"enable_for_virtual":"no"}' --format=json
wp plugin is-installed woocommerce-mercadopago || wp plugin install woocommerce-mercadopago
wp plugin activate woocommerce-mercadopago >/dev/null 2>&1 || true

echo "→ Portada, páginas y menú"
for borrar in hello-world sample-page privacy-policy refund_returns; do
  wp post delete $(wp post list --post_type=post,page --name="$borrar" --post_status=any --format=ids) --force >/dev/null 2>&1 || true
done
wp comment delete $(wp comment list --format=ids) --force >/dev/null 2>&1 || true
INICIO=$(wp post list --post_type=page --name=inicio --format=ids)
if [ -z "$INICIO" ]; then
  INICIO=$(wp post create --post_type=page --post_status=publish --post_title="Inicio" --post_name=inicio --porcelain)
fi
wp option update show_on_front page
wp option update page_on_front "$INICIO"
for par in shop:tienda cart:carrito checkout:finalizar-compra myaccount:mi-cuenta; do
  wp post update "$(wp option get woocommerce_${par%%:*}_page_id)" --post_name="${par##*:}" >/dev/null
done
for par in shop:Tienda cart:Carrito checkout:"Finalizar compra" myaccount:"Mi cuenta"; do
  wp post update "$(wp option get woocommerce_${par%%:*}_page_id)" --post_title="${par##*:}" >/dev/null
done
wp option update permalink_structure "/%postname%/"
wp rewrite flush >/dev/null
wp menu delete "Principal" >/dev/null 2>&1 || true
if true; then
  TIENDA=$(wp option get woocommerce_shop_page_id); CUENTA=$(wp option get woocommerce_myaccount_page_id)
  wp menu create "Principal" >/dev/null
  wp menu item add-post "Principal" "$INICIO" --title="Inicio" >/dev/null
  wp menu item add-post "Principal" "$TIENDA" --title="Tienda" >/dev/null
  wp menu item add-post "Principal" "$CUENTA" --title="Mi cuenta" >/dev/null
fi
wp menu location assign "Principal" primary >/dev/null

echo "→ Correos (WP Mail SMTP + diseño de los correos de WooCommerce)"
wp plugin is-installed wp-mail-smtp || wp plugin install wp-mail-smtp
wp plugin activate wp-mail-smtp >/dev/null 2>&1 || true
wp option update woocommerce_email_from_name "DENVER LIBRERIA"
wp option update woocommerce_email_from_address "libreriadenver@gmail.com"
wp option update woocommerce_email_footer_text "DENVER S.A.C. · RUC 20610817433 · Mz. 32 Lote 04, P.J. El Porvenir, Miraflores, Arequipa · WhatsApp 902 022 295"
wp option update woocommerce_email_base_color "#1F6BFF"
wp option update woocommerce_email_background_color "#F4F6FC"
wp option update woocommerce_email_body_background_color "#FFFFFF"
wp option update woocommerce_email_text_color "#0A1B5C"
# Aviso de nuevo pedido a la tienda.
wp option patch update woocommerce_new_order_settings recipient "libreriadenver@gmail.com" >/dev/null 2>&1 \
  || wp option update woocommerce_new_order_settings '{"enabled":"yes","recipient":"libreriadenver@gmail.com"}' --format=json

echo "→ Páginas legales y Libro de Reclamaciones"
# Se crean si no existen. Para volver a cargar el texto desde contenido/: FORZAR_CONTENIDO=1 npm run tienda:configurar
pagina() { # slug título archivo
  local id; id=$(wp post list --post_type=page --name="$1" --post_status=any --format=ids)
  if [ -z "$id" ]; then
    id=$(wp post create --post_type=page --post_status=publish --post_title="$2" --post_name="$1" --porcelain)
    FORZAR_CONTENIDO=1
  fi
  if [ "${FORZAR_CONTENIDO:-0}" = "1" ]; then
    wp post update "$id" --post_content="$(cat "contenido/$3")" >/dev/null
  fi
  echo "$id"
}
P_TERMINOS=$(pagina terminos-y-condiciones "Términos y condiciones" terminos-y-condiciones.html)
P_PRIVAC=$(pagina politica-de-privacidad "Política de privacidad" politica-de-privacidad.html)
P_ENVIOS=$(pagina envios-cambios-y-devoluciones "Envíos, cambios y devoluciones" envios-cambios-y-devoluciones.html)
P_LIBRO=$(pagina libro-de-reclamaciones "Libro de Reclamaciones" libro-de-reclamaciones.html)
wp option update woocommerce_terms_page_id "$P_TERMINOS"
wp option update wp_page_for_privacy_policy "$P_PRIVAC"

if [ "$HOSTING" = "1" ]; then echo "✔ Tienda configurada."; else echo "✔ Tienda configurada. Abre http://localhost:8890 (usuario admin, clave en .env.local)"; fi
