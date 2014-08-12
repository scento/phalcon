#!/bin/sh
echo "phpmd results: "
php `pwd`/vendor/bin/phpmd `pwd`/src text codesize,unusedcode,naming,design
wait