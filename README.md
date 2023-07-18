# wpscan-examples

This repository holds a list of examples of how to integrate [WPScan](https://wpscan.com/) data into your application or workflow.

## Table of projects

| Project                                          | Language   | Description                                                                                                                                                                |
|--------------------------------------------------|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [webhook-receiver-php](webhook-receiver-php)     | PHP        | This is an example integration of a script that can receive webhooks from [WPScan](https://wpscan.com/) and process them, written in [PHP](https://www.php.net/).          |
| [webhook-receiver-ruby](webhook-receiver-ruby)   | Ruby       | Same as the PHP example, but written in [Ruby](https://www.ruby-lang.org/).                                                                                                |
| [multiple-sites-scanner](multiple-sites-scanner) | Bash       | This project is a script for automating WPScan across multiple servers. It uses `wp-cli` to collect WordPress data and then checks for vulnerabilities via the WPScan API. |
| [nagios-service](nagios-servce)                  | Bash       | This project is a script for automating WPScan on a Nagios configuration. It uses the WPScan CLI to monitor external hosts from your Nagios server.                        |
| [github-action](github-action)                   | PHP / YAML | This project is GitHub action that can be run to check version controlled themes and plugins for vulnerable versions automatically.                                        |
