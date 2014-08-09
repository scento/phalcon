#!/bin/sh
echo "Execute pdepend..."
php /tmp/pdepend.phar --summary-xml=/tmp/summary.xml `pwd`/src
echo "Finished pdepend analysis: "
cat /tmp/summary.xml
wait