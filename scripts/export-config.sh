#!/bin/bash

set -e

#------------------------------------------------------------------------------
# Optional environment variables with simple defaults.

# The glob pattern used to generate a list of config files.
: "${AZQS_CONFIG_FILES:=./**/config/**/*.yml}"

# The path to the directory containing exported config.
: "${AZQS_CONFIG_EXPORT_PATH:=config-export}"

#------------------------------------------------------------------------------
# Utility functions definitions.

errorexit () {
  echo "** $1." >&2
  exit 1
}

# Show progress on STDERR, unless explicitly quiet.
if [ -z "$UAQSRTOOLS_QUIET" ]; then
  logmessage () {
    echo "$1..." >&2
  }
  normalexit () {
    echo "$1." >&2
    exit 0
  }
else
  logmessage () {
    return
  }
  normalexit () {
    exit 0
  }
fi

#------------------------------------------------------------------------------
# Initial run-time error checking.

shopt -p |grep globstar > /dev/null \
  || errorexit "globstar shopt not available"

#------------------------------------------------------------------------------
#  Initial setup

shopt -s globstar

#------------------------------------------------------------------------------
#  Replace Quickstart config files with exported files.

echo "AZQS_CONFIG_FILES: ${AZQS_CONFIG_FILES}"
echo "AZQS_CONFIG_EXPORT_PATH: ${AZQS_CONFIG_EXPORT_PATH}"

for CONFIG_FILE in $AZQS_CONFIG_FILES
do
  if [[ ! $CONFIG_FILE =~ .*(config/schema).* ]]; then
    BASE_NAME=$(basename "$CONFIG_FILE")
    if [ -f "$AZQS_CONFIG_EXPORT_PATH"/"$BASE_NAME" ]; then
      logmessage "Updating ${CONFIG_FILE}"
      cp "$AZQS_CONFIG_EXPORT_PATH"/"$BASE_NAME" "$CONFIG_FILE"
    fi
  fi
done
