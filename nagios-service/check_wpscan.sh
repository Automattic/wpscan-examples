#!/bin/bash

# Hostname argument
hostname="$1"

# API key argument
api_key="$2"

# Execute WPScan and capture the output
wpscan_output=$(wpscan --url $hostname --api-token $api_key --format json)

return_code="$?"

# Check if WPScan command executed successfully
if [ $return_code -eq 1 ]; then
  echo "WPScan command failed with the following output:"
  echo $wpscan_output
  exit 1
fi

vulnerabilities_count=$(echo $wpscan_output | jq '[.. | .vulnerabilities? // empty] | flatten | length')

if [ "$vulnerabilities_count" -gt 0 ]; then
  echo "CRITICAL: $vulnerabilities_count vulnerabilities found."
  exit 2
else
  echo "OK: No vulnerabilities found."
  exit 0
fi
