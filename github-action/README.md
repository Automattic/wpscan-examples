## Setup
- Copy the `wpscan.yml` file to the `.github/workflows` folder of your repo.
- Copy the `check-wpscan.php` file to the root of your repo.
- Create the following secret variables on Github:
    - `WP_CONTENT_PATH`: Should contain the path to the wp-content on the repository.
    - `WPSCAN_TOKEN`: Should contain your WPScan token.
- Next time you push to the main branch the action will check if the plugins and themes inside `wp-content` are on a vulnerable version.
