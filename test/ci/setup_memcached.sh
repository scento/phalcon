#!/bin/sh
echo "Configuring memcached..."
phpenv config-add `pwd`/memcache.ini
wait