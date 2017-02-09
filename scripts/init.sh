#!/bin/bash -eu
#
# initialize project.
#

# set current directory to parent of this file.
curdir=$(cd $(dirname $0) && pwd)
cd $curdir/..

# check dependencies.
if [ -z "$(which php)" ];then
  echo "php client is not installed or not exists in PATH." >&2
  exit 1
fi

if [ -z "$(which npm)" ];then
  echo "npm is not installed or not exists in PATH." >&2
  exit 1
fi

if [ -z "$(which java)" ];then
  echo "java command is not installed or not exists in PATH." >&2
  exit 1
fi

php composer.phar self-update
php composer.phar install
if [ -z "$(which gulp)" ];then
  npm install -g gulp
fi
if [ -z "$(which bower)" ];then
  npm install -g bower
fi
npm install
bower install
gulp sass closure

if [ ! -f vapid_keys.php ];then
  php scripts/generate_vapid_keys.php
fi

if [ ! -d logs ];then
  mkdir logs
  chmod 777 logs
  touch logs/app.log
  chmod 777 logs/app.log
fi

if [ ! -d public/siteicons ];then
  mkdir public/siteicons
  chmod 777 public/siteicons
fi

if [ ! -d cache ];then
  mkdir cache
  chmod 777 cache
fi 

