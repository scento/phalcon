#!/bin/sh
echo "phploc analysis:"
php `pwd`/vendor/bin/phploc `pwd`/src
wait