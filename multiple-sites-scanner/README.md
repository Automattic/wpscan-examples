# WPScan Automation Script

This script is designed to automate WordPress vulnerability scanning using WPScan for multiple servers. It reads server details from a JSON file, runs WP-CLI on each server to collect data, and then sends it to the WPScan API to check for vulnerabilities.

## Prerequisites

- `jq` for JSON parsing.
- Password-less SSH authentication set up for all the servers you want to check.
- `wp-cli` available on all the servers.
- `curl` to make requests to the WPScan API.
- A WPScan account with an API key.

## Installation

1. Clone this repository to your local machine:

    ```bash
    git clone <repository_url>
    ```

2. Change into the directory:

    ```bash
    cd <directory_name>
    ```

3. Make the script executable:

    ```bash
    chmod +x multiple-sites-scanner.sh
    ```

## Usage

1. Set your WPScan API key as an environment variable:

    ```bash
    export WPSCAN_API_KEY=your_api_key
    ```

2. Create a `servers.json` file in the same directory with your server information. Use the following format:

    ```json
    [
      {
        "name": "Server1",
        "ssh": "user@server1",
        "site_dir": "/path/to/wp/install/on/server1",
        "ssh_key_path": "~/.ssh/id_rsa"
      }
    ]
    ```
    Replace the `ssh`, `site_dir`, and `ssh_key_path` values with your actual SSH connection strings, WordPress installation directories, and the actual path to the ssh key.

3. Run the script:

    ```bash
    ./multiple-sites-scanner.sh
    ```

    The script will output a summary of the WordPress version, plugins, and themes for each server, and the results from the WPScan API.

## Notes

The WPScan API call in this script is only a placeholder. You will need to replace it with an actual API call as per the WPScan API's documentation.

Use caution when executing scripts in your production environment. This script has not been thoroughly tested and may need adjustments to fit your specific requirements.

## License

This project is licensed under the terms of the MIT license.
