#!/bin/sh
echo "phpmd results: "
phpmd `pwd`/src text codesize,unusedcode,naming,design
wait