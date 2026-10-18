#!/usr/bin/env bash
# Configuración inicial de la tienda local: Perú, soles, IGV 18 % incluido y envíos.
# Se puede ejecutar varias veces sin duplicar nada.
set -euo pipefail

cd "$(dirname "$0")/.."
if [ -z "${DOCKER_HOST:-}" ] && [ -S "/run/user/$(id -u)/podman/podman.sock" ]; then
  export DOCKER_HOST="unix:///run/user/$(id -u)/podman/podman.sock"
fi
wp() { docker compose --profile cli run --rm -T cli "$@" 2>/dev/null; }

# Clave del administrador local: se genera una vez y queda en .env.local (no se sube a git).
[ -f .env.local ] || echo "ADMIN_PASS=$(head -c 12 /dev/urandom | base64 | tr -d '/+=')" > .env.local
. ./.env.local

if ! wp core is-installed; then
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

echo "✔ Tienda configurada. Abre http://localhost:8890 (usuario admin, clave en .env.local)"
