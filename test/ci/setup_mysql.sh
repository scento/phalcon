#!/bin/sh
echo "MySQL initialization..."
mysql -uroot -e 'CREATE DATABASE phalcon_test CHARSET=utf8 COLLATE=utf8_unicode_ci;'
mysql -uroot phalcon_test < "`pwd`/test/cphalcon/schemas/mysql/phalcon_test.sql"
wait