#!/bin/sh
echo "Configuring mongoDB..."
phpenv config-add `pwd`/mongo.ini
wait