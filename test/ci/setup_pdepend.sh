#!/bin/sh
sudo apt-get install imagemagick libmagickwand-dev
sudo pecl config-set preferred_state beta
sudo pecl install imagick
sudo pecl config-set preferred_state stable

sudo pear channel-discover pear.pdepend.org
sudo pear install pdepend/PHP_Depend-beta
wait