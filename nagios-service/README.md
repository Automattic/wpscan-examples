## Setup
- Run nagios using docker: `docker run --name nagios4 -p 0.0.0.0:8080:80 jasonrivers/nagios:latest`
- Open a shell session on your container and install ruby and wpscan: `docker exec -it nagios4 /bin/sh -c "apt update && apt install -y ruby-full && apt install -y sudo && gem install wpscan"`
- Setup sudo for the nagios user by adding the following to your `/etc/sudoers` file:
    `nagios ALL=(ALL) NOPASSWD: /opt/nagios/libexec/check_wpscan.sh`
- Increase the `service_check_timeout` on your `nagios.cfg` file to something like 120 seconds so there's enough time for WPScan to run.
- Copy the `check_wpscan.sh` script into the `libexec` folder of your nagios installation
- Make sure the script has execution permissions with `chmod +x check_wpscan.sh`
- Add the `check-wpscan` command by copying the contents of `commands.cfg` into your `commands.cfg` file.
- Add a host definition file like in the sample `wordpress.cfg` file and make sure it's being included in your main `nagios.cfg` file
