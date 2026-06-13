#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-$(command -v php)}"

if [[ -z "${PHP_BIN}" ]]; then
    echo "PHP CLI не найден."
    exit 1
fi

cd "${APP_DIR}"
"${PHP_BIN}" yii db-backup/index
