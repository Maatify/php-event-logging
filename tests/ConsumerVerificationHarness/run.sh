#!/usr/bin/env bash

set -euo pipefail

harness_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
package_root="$(cd -- "${harness_root}/../.." && pwd)"

for required_variable in \
  EVENT_LOGGING_HARNESS_MYSQL_DSN \
  EVENT_LOGGING_HARNESS_MYSQL_USER \
  EVENT_LOGGING_HARNESS_MYSQL_PASSWORD; do
  if [[ -z "${!required_variable:-}" ]]; then
    echo "Missing required consumer harness environment variable: ${required_variable}" >&2
    exit 1
  fi
done

for run_number in 1 2; do
  consumer_root="$(mktemp -d)"
  cleanup_consumer_root() {
    rm -rf -- "${consumer_root}"
  }
  trap cleanup_consumer_root EXIT

  sed "s|__PACKAGE_ROOT__|${package_root}|g" "${harness_root}/composer.json" > "${consumer_root}/composer.json"
  cp "${harness_root}/verify.php" "${consumer_root}/verify.php"

  composer update \
    --working-dir="${consumer_root}" \
    --no-interaction \
    --prefer-dist \
    --no-progress

  EVENT_LOGGING_HARNESS_RUN="${run_number}" \
    composer exec \
      --working-dir="${consumer_root}" \
      -- php verify.php

  cleanup_consumer_root
  trap - EXIT
done

echo "Consumer Verification Harness completed two clean runs."
