#!/bin/bash
sudo docker run --rm --name geophp8-cs-fixer -v $(pwd):/data ghcr.io/php-cs-fixer/php-cs-fixer:3-php8.1 fix /data