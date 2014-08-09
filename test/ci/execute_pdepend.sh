#!/bin/sh
echo "Execute pdepend..."
pdepend --summary-xml=/tmp/summary.xml `pwd`/src
echo "Finished pdepend analysis: "
cat /tmp/summary.xml
wait