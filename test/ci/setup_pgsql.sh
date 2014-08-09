#!/bin/sh
psql -c 'CREATE DATABASE phalcon_test;' -U postgres
psql -U postgres phalcon_test -q -f "`pwd`/../cphalcon/schemas/postgresql/phalcon_test.sql"
wait