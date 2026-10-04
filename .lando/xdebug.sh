#!/bin/bash
# Toggle Xdebug inside the appserver without a rebuild.
# Usage: lando xdebug <mode>   (e.g. debug, profile, debug,develop, off)
# Modes: https://xdebug.org/docs/all_settings#mode
set -euo pipefail

INI=/usr/local/etc/php/conf.d/zzz-lando-xdebug.ini
EXT_INI=/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
VALID_MODE='^(off|develop|coverage|debug|gcstats|profile|trace)(,(develop|coverage|debug|gcstats|profile|trace))*$'

usage() {
  echo "Usage: lando xdebug <mode>"
  echo "Valid modes: off, develop, coverage, debug, gcstats, profile, trace (comma-separate to combine)."
  echo "See https://xdebug.org/docs/all_settings#mode"
}

reload_fpm() {
  # USR2 to the master (oldest) php-fpm process triggers a graceful reload.
  pkill -o -USR2 php-fpm
}

if [ "$#" -ne 1 ]; then
  usage
  exit 1
fi

mode="$1"

if ! [[ "$mode" =~ $VALID_MODE ]]; then
  echo "Unknown Xdebug mode: '$mode'"
  usage
  exit 1
fi

if [ "$mode" = "off" ]; then
  echo "xdebug.mode = off" > "$INI"
  rm -f "$EXT_INI"
  reload_fpm
  echo "Xdebug has been turned off."
  exit 0
fi

echo "xdebug.mode = $mode" > "$INI"
if [ ! -f "$EXT_INI" ]; then
  docker-php-ext-enable xdebug
fi

if [[ "$mode" = *"profile"* ]]; then
  # Always resolve against /app: tooling runs in the container path mirroring
  # the host cwd, so a relative mkdir would land in a subdirectory.
  profiler_dir="/app/${PROFILER_OUTPUT_DIR:-profiler-output}"
  if [ ! -d "$profiler_dir" ]; then
    mkdir -p "$profiler_dir"
    chown "$LANDO_HOST_UID:$LANDO_HOST_GID" "$profiler_dir"
  fi
  echo "xdebug.output_dir = $profiler_dir" >> "$INI"
fi

reload_fpm
echo "Xdebug is loaded in '$mode' mode."
