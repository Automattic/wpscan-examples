#!/bin/bash

# Check if jq command line JSON processor is installed
if ! [ -x "$(command -v jq)" ]; then
	echo 'Error: jq is not installed.' >&2
	exit 1
fi

# Assume WPScan API key is set in environment variable
if [ -z "$WPSCAN_API_KEY" ]; then
	echo "Error: WPScan API key not set in environment variable" >&2
	exit 1
fi

# Check for the servers.json file
if [ ! -f "./servers.json" ]; then
	echo 'Error: servers.json cannot be found' >$2
fi

is_vulnerable() {
	local VERSION=$1
	local INTRODUCED_IN=$2
	local FIXED_IN=$3

	if [[ "$INTRODUCED_IN" == "null" ]]; then
		INTRODUCED_IN="0"
	fi

	if [[ "$FIXED_IN" == "null" ]]; then
		return 0
	elif [[ $(printf '%s\n' "$VERSION" "$INTRODUCED_IN" | sort -V | head -n 1) == "$INTRODUCED_IN" ]] && [[ $(printf '%s\n' "$VERSION" "$FIXED_IN" | sort -V | tail -n 1) == "$FIXED_IN" ]]; then
		return 0
	else
		return 1
	fi
}

# Iterate through the servers listed in the JSON file
for row in $(cat servers.json | jq -r '.[] | @base64'); do
	_jq() {
		echo ${row} | base64 --decode | jq -r ${1}
	}

	SERVER_NAME=$(_jq '.name')
	SSH_CONNECTION=$(_jq '.ssh')
	SITE_DIRECTORY=$(_jq '.site_dir')
	SSH_KEY_PATH=$(_jq '.ssh_key_path')

	# Run wp-cli commands to get list of plugins, themes, and WP version using SSH key-based authentication
	WP_VERSION=$(ssh -i $SSH_KEY_PATH ssh $SSH_CONNECTION "wp core version --path=$SITE_DIRECTORY")
	PLUGINS=$(ssh -i $SSH_KEY_PATH ssh $SSH_CONNECTION "wp plugin list --path=$SITE_DIRECTORY --fields=name,version --format=json")
	THEMES=$(ssh -i $SSH_KEY_PATH ssh $SSH_CONNECTION "wp theme list --path=$SITE_DIRECTORY --fields=name,version --format=json")

	REPORT="\n*******************\n$SERVER_NAME Analysis Start\n\n"

	REPORT+="WordPress Version: $WP_VERSION\n"
	WP_RESULT=$(curl -s -H "Authorization: Token token=${WPSCAN_API_KEY}" https://wpscan.com/api/v3/wordpresses/${WP_VERSION//./})
	WP_VULNERABILITIES=$(echo "$WP_RESULT" |  jq --arg wv "$WP_VERSION" '.[$wv].vulnerabilities')

	# Check WordPress version and report vulnerabilities
	if [[ "$WP_VULNERABILITIES" != "null" ]]; then
		for vuln in $(echo "$WP_VULNERABILITIES" | jq -r '.[] | @base64'); do
			_jq() {
				echo ${vuln} | base64 --decode | jq -r ${1}
			}

			# Use the _jq function to extract the data
			FIXED_IN=$(_jq '.fixed_in')
			INTRODUCED_IN=$(_jq '.introduced_in')

			if is_vulnerable "$WP_VERSION" "$INTRODUCED_IN" "$FIXED_IN"; then
				TITLE=$(_jq '.title')
				REPORT+="Vulnerability found: $TITLE\n"
			fi
		done
	fi

	REPORT+="\n"

	# Iterate through Plugins and report vulnerabilities
	for plugin in $(echo "$PLUGINS" | jq -c '.[]'); do
		PLUGIN_NAME=$(echo "$plugin" | jq -r '.name')
		PLUGIN_VERSION=$(echo "$plugin" | jq -r '.version')

		REPORT+="Plugin: $PLUGIN_NAME v$PLUGIN_VERSION\n"

		# Query the WPScan API for vulnerabilities for this plugin
		PLUGIN_RESULT=$(curl -s -H "Authorization: Token token=${WPSCAN_API_KEY}" https://wpscan.com/api/v3/plugins/${PLUGIN_NAME})

		# Parse the plugin's vulnerabilities
		PLUGIN_VULNERABILITIES=$(echo "$PLUGIN_RESULT" | jq --arg pn "$PLUGIN_NAME" '.[$pn].vulnerabilities')

		# If plugin vulnerabilities is not null then loop through and compare versions
		if [[ "$PLUGIN_VULNERABILITIES" != "null" ]]; then

			for vuln in $(echo "$PLUGIN_VULNERABILITIES" | jq -r '.[] | @base64'); do
				_jq() {
					echo ${vuln} | base64 --decode | jq -r ${1}
				}

				# Use the _jq function to extract the data
				FIXED_IN=$(_jq '.fixed_in')

				if is_vulnerable "$PLUGIN_VERSION" "$INTRODUCED_IN" "$FIXED_IN"; then
					TITLE=$(_jq '.title')
					REPORT+="Vulnerability found: $TITLE\n"
				fi
			done
		fi
	done

	REPORT+="\n"

	# Iterate through Themes and report vulnerabilities
	for theme in $(echo "$THEMES" | jq -c '.[]'); do
		THEME_NAME=$(echo "$theme" | jq -r '.name')
		THEME_VERSION=$(echo "$theme" | jq -r '.version')

		REPORT+="Theme: $THEME_NAME v$THEME_VERSION\n"

		# Query the WPScan API for vulnerabilities for this theme
		THEME_RESULT=$(curl -s -H "Authorization: Token token=${WPSCAN_API_KEY}" https://wpscan.com/api/v3/themes/${THEME_NAME})

		# Parse the theme's vulnerabilities
		THEME_VULNERABILITIES=$(echo "$THEME_RESULT" | jq --arg tn "$THEME_NAME" '.[$tn].vulnerabilities')

		# If theme vulnerabilities is not null then loop through and compare versions
		if [[ "$THEME_VULNERABILITIES" != "null" ]]; then

			for vuln in $(echo "$THEME_VULNERABILITIES" | jq -r '.[] | @base64'); do
				_jq() {
					echo ${vuln} | base64 --decode | jq -r ${1}
				}

				# Use the _jq function to extract the data
				FIXED_IN=$(_jq '.fixed_in')

				if is_vulnerable "$THEME_VERSION" "$INTRODUCED_IN" "$FIXED_IN"; then
					TITLE=$(_jq '.title')
					REPORT+="Vulnerability found: $TITLE\n"
				fi
			done
		fi
	done

	REPORT+="\n$SERVER_NAME Analysis End\n*******************\n"

	# Add to overall summary
	SITE_REPORT+=("$REPORT")
done

# Print overall summary

for SUMMARY in "${SITE_REPORT[@]}"; do
	echo -e "$SUMMARY"
done
