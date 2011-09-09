#!/bin/sh
cd $(dirname $0)
git submodule update --init
git submodule foreach git pull origin master
cd vendor/silex ; php silex.phar check ; php silex.phar update ; cd ../../