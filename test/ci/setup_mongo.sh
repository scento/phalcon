#!/bin/sh
echo "Configuring mongoDB..."
phpenv config-add `pwd`/test/ci/mongo.ini
wait