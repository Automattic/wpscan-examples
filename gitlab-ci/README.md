# WPScan GitLab CI Setup

This GitLab CI pipeline is designed to automatically scan WordPress plugins and themes for vulnerabilities using WPScan.

## Setup

### Option 1: Direct Integration

1. **Copy the `wpscan.yml` file to the `.gitlab-ci.yml` file in the root of your repo.**

2. **Copy the `check-wpscan-v2.php` file to the root of your repo.**

3. **Create the following CI/CD variables in your GitLab project settings:**

    - **`WP_CONTENT_PATH`:** Should contain the path to the `wp-content` directory in the repository.  
      - Example: `${CI_PROJECT_DIR}/wp-content`

    - **`WPSCAN_API_TOKEN`:** Should contain your WPScan API token to authenticate with WPScan.

    - **`WEBHOOK_URL` (optional):** URL to send the scan results. If not set, webhook notifications will be skipped.

    > **Note:** It is recommended to set these variables as **Protected Variables** within your GitLab project to ensure they are not exposed.

### Option 2: Template Integration
If you prefer not to directly add the files to your repository, you can instead use this project as a template that another repository would pull in as an include config in the GitLab CI setup.

#### How to Use the Template
> Uncomment Line 12 in `wpscan.yml` and modify script location path

1. Reference the pipeline template in your project's `.gitlab-ci.yml` file:

    ```yaml
    include:
      - project: "group/project" # Path to project containing the WPScan template
        file: "/wpscan.yml"
        ref: main
    ```

2. Ensure the same CI/CD variables (`WP_CONTENT_PATH`, `WPSCAN_API_TOKEN`, `WEBHOOK_URL`) are set as described in Option 1.

_The including repository could set their own unique CI/CD variables per repository, allowing for flexibility in how each project configures the scan._


## Triggering the Scan

- The scan will automatically be triggered on every push or merge request to the repository.

## How it Works

- The pipeline stage will execute the WPScan script if any changes are detected.
- It will download dependencies using Composer and then run the `check-wpscan-v2.php` script.
- The script will:
  - Scan for vulnerabilities in plugins and themes.
  - Collect data from the WPScan API.
  - Optionally send results to the configured webhook URL.
- If vulnerabilities are detected, they will be printed in a simplified JSON format to the pipeline log.
