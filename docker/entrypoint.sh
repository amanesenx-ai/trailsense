#!/bin/sh
set -e
# Listen on the port the host gives us
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
# Create tables + demo data on first boot only (never wipes data)
php /var/www/html/bin/db-init.php
exec apache2-foreground
