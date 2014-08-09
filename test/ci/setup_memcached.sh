#!/bin/sh
echo "Configuring memcached..."
phpenv config-add `pwd`/test/ci/memcache.ini
wait