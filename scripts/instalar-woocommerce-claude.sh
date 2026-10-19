#!/usr/bin/env bash
# Instala en la tienda LOCAL el plugin "WooCommerce for Claude" (el que conecta las skills de Claude con la tienda).
# Solo para desarrollo: el código se descarga en .herramientas/ (fuera de git) y se monta en el contenedor.
set -euo pipefail
cd "$(dirname "$0")/.."
if [ -z "${DOCKER_HOST:-}" ] && [ -S "/run/user/$(id -u)/podman/podman.sock" ]; then
  export DOCKER_HOST="unix:///run/user/$(id -u)/podman/podman.sock"
fi
SRC=.herramientas/wc-claude
mkdir -p .herramientas
if [ -d "$SRC/.git" ]; then git -C "$SRC" pull -q; else git clone -q --depth 1 https://github.com/woocommerce/woocommerce-claude.git "$SRC"; fi
# El plugin necesita instalar su dependencia con Composer (corre en un contenedor, no hace falta instalar nada).
docker run --rm -v "$PWD/$SRC:/app" -w /app/plugins/woocommerce-for-claude docker.io/library/composer:2 \
  install --no-dev --no-interaction --prefer-dist --ignore-platform-reqs -q
docker compose up -d wordpress >/dev/null 2>&1
docker compose --profile cli run --rm -T cli plugin activate woocommerce-claude
echo "✔ WooCommerce for Claude instalado. Endpoint MCP: http://localhost:8890/wp-json/woocommerce-claude/mcp"
