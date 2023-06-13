# webhook-receiver-php

This is an example integration of a script that can receive webhooks from 
[WPScan](https://wpscan.com/) and process them, written in [PHP](https://www.php.net/).

## Scenario

The idea is that we have an imaginary list of installed WordPress versions,
plugins and themes, and check if the vulnerability transmitted via the 
webhook fits into any of the version ranges of the installed software.

The imaginary list of software is found in `$imaginary_installed_software`
and the detected vulnerable software in the end in `$detected_vulnerable_software`.

With this data, you can then process it however you wish, e.g. automatically
upgrade a plugin, or send an alert to your team or a queue.

## Running the server

The simplest way of running it is using the PHP development server, but you can
use any webserver you like.

For the development server, just head into this directory on your server and run:

```bash
php -S 0.0.0.0:80
```

Then, the webhook can be received on `https://example.com/webhook.php`.

## Testing the webhook

As of now, there is no simple way to test trigger the webhook yourself.
If you are an enterprise user, please contact us, and we'll be happy to send
a few test-webhooks your way!
