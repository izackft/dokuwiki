#!/bin/bash

set -e

chmod 777 -R /var/www

/bin/sh /usr/local/apache2/bin/apachectl -d . -f /usr/local/apache2/conf/httpd.conf -e info -DFOREGROUND


