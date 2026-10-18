#!/usr/bin/env bash
# Importa salida/productos_woocommerce.csv en la tienda local.
set -euo pipefail
cd "$(dirname "$0")/.."
if [ -z "${DOCKER_HOST:-}" ] && [ -S "/run/user/$(id -u)/podman/podman.sock" ]; then
  export DOCKER_HOST="unix:///run/user/$(id -u)/podman/podman.sock"
fi
CSV="${1:-salida/productos_woocommerce.csv}"
[ -f "$CSV" ] || { echo "Falta $CSV. Ejecuta: npm run tienda:csv -- \"ruta/al/Excel.xlsx\""; exit 1; }
docker compose cp "$CSV" wordpress:/var/www/html/productos_woocommerce.csv 2>/dev/null
docker compose exec -T wordpress chmod 644 /var/www/html/productos_woocommerce.csv
docker compose --profile cli run --rm -T cli eval "$(cat scripts/importar.php)" 2>&1 | grep -v -e nodocker -e '^\s*$' -e 'Container' -e 'compose provider'
docker compose --profile cli run --rm -T cli wc product list --user=1 --per_page=1 --fields=id >/dev/null 2>&1 || true
echo "✔ Importación terminada. Revisa http://localhost:8890/wp-admin/edit.php?post_type=product"
