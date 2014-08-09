#!/bin/sh
sudo apt-get -qq install beanstalkd
echo 'DEAMON_OPTS="-l 127.0.0.1 -p 11300"' | sudo tee -a /etc/default/beanstalkd > /dev/null
echo 'START=yes' | sudo tee -a /etc/default/beanstalkd > /dev/null
sudo service beanstalkd restart
wait