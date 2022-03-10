#!/bin/bash

if [ "$#" -ne 1 ]; then
  echo "extension=imagick" > /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini
  pkill -o -USR2 php-fpm
  echo "Imagick Enabled"
else
  mode="$1"
  echo "" > /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini
  pkill -o -USR2 php-fpm
  echo "Imagick Disabled"
fi
