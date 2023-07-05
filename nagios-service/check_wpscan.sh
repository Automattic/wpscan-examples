#!/bin/bash

# Hostname argument
hostname="$1"

# API key argument
api_key="$2"

# Execute WPScan and capture the output
wpscan_output=$(wpscan --url $1 --api-token $api_key --format json)

return_code="$?"

# Check if WPScan command executed successfully
if [ $return_code -eq 1 ]; then
  echo "WPScan command failed with the following output:"
  echo $wpscan_output
  exit 1
fi

if [ "$return_code" -gt 1 ]; then
  echo "CRITICAL: $return_code vulnerabilities found."
  exit 2
else
  echo "OK: No vulnerabilities found."
  exit 0
fi
