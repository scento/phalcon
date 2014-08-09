#!/bin/sh
echo "phploc analysis:"
php /tmp/phploc.phar `pwd`/src
wait