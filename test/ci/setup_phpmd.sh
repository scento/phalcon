#!/bin/sh
wget http://static.phpmd.org/php/1.1.0/phpmd.phar
chmod +x phpmd.phar
mv phpmd.phar /tmp/
wait