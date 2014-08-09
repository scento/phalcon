#!/bin/sh
echo "phpmd results: "
php /tmp/phpmd.phar `pwd`/src text codesize,unusedcode,naming,design
wait