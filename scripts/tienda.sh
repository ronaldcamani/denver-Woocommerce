#!/usr/bin/env bash
# Uso: scripts/tienda.sh start | stop | reset
# Usa Podman o Docker, el que tengas.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -z "${DOCKER_HOST:-}" ] && [ -S "/run/user/$(id -u)/podman/podman.sock" ]; then
  export DOCKER_HOST="unix:///run/user/$(id -u)/podman/podman.sock"
fi

case "${1:-}" in
  start)
    docker compose up -d --wait db wordpress
    # WordPress tarda unos segundos en copiar sus archivos la primera vez.
    until curl -s -o /dev/null http://localhost:8890/wp-admin/install.php; do sleep 2; done
    bash scripts/configurar.sh
    ;;
  stop)  docker compose stop ;;
  reset) docker compose down -v ;;
  *) echo "Uso: $0 start|stop|reset"; exit 1 ;;
esac
