#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNNER="${APP_DIR}/scripts/run-db-backup.sh"
LOG_DIR="${APP_DIR}/runtime/logs"
CRON_MARKER="dbt-db-backup"
CRON_LINE="0 */12 * * * ${RUNNER} >> ${LOG_DIR}/db-backup.log 2>&1 # ${CRON_MARKER}"

if [[ ! -x "${RUNNER}" ]]; then
    chmod +x "${RUNNER}"
fi

mkdir -p "${LOG_DIR}"

TMP_CRON="$(mktemp)"
trap 'rm -f "${TMP_CRON}"' EXIT

crontab -l 2>/dev/null | grep -Fv "${CRON_MARKER}" > "${TMP_CRON}" || true
echo "${CRON_LINE}" >> "${TMP_CRON}"
crontab "${TMP_CRON}"

echo "Cron-задача установлена:"
echo "${CRON_LINE}"
