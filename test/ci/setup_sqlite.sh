#!/bin/sh
echo "SQLite initialization...."
sqlite3 /tmp/phalcon_test.sqlite < "`pwd`/../cphalcon/schemas/sqlite/phalcon_test.sql"
wait