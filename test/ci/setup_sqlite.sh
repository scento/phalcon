#!/bin/sh
sqlite3 /tmp/phalcon_test.sqlite < "`pwd`/../cphalcon/schemas/sqlite/phalcon_test.sql"
wait