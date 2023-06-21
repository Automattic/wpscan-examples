# webhook-receiver-ruby

This is an example integration of a script that can receive webhooks from 
[WPScan](https://wpscan.com/) and process them, written in [Ruby](https://www.ruby-lang.org/).

## Scenario

The idea is that we have an imaginary list of installed WordPress versions,
plugins and themes, and check if the vulnerability transmitted via the 
webhook fits into any of the version ranges of the installed software.

The imaginary list of software is found in `imaginary_installed_software`
and the detected vulnerable software in the end in `detected_vulnerable_software`.

With this data, you can then process it however you wish, e.g. automatically
upgrade a plugin, or send an alert to your team or a queue.

## Running the server

We're using [sinatra](https://sinatrarb.com/), so first you need to install it:

```bash
gem install sinatra
```

Then, run the server:

```bash
ruby webhook.rb
```

Then, the webhook can be received on `https://example.com:4567/webhook`.

## Testing the webhook

As of now, there is no simple way to test trigger the webhook yourself.
If you are an enterprise user, please contact us, and we'll be happy to send
a few test-webhooks your way!
