bpush is designed to run on a machine with LAMP.

# System Requirements

 - PHP 5.5 or later
 - MySQL 5.5 or later
 - Redis 3.0 or later
 - NodeJS 5.0 or later
 - supervisord 3.0 or later
 - Java 1.8 or later
 - SSL certification and dedicated domain

## PHP Extensions

Your system must be installed following php extensions for running bpush.

 - pdo
 - pdo_mysql
 - mysqlnd
 - json
 - mbstring
 - dom
 - curl
 - intl
 - gmp
 - opcache (recommended)


##  Apache Configuration

 - Enable mod_rewrite module.
 - Set AllowOverride directive to "All".

# Google Account

## Google Api Console

Visit Google API Console for setting push notifications.
URL: https://console.developers.google.com/

1. If you don't have project create it.
2. Display Dashboard page and enable Google Cloud Messaging.
3. Display Credentials page and create API Key.

## Google Cloud Platform

Visit Google Cloud Platform and confirm your Project Number displayed beside of Project ID.
URL: https://console.cloud.google.com/

# AWS

1. Create AWS Account.
2. Create an user who can use AWS SNS by AWS IAM(Identity and Access Management).
3. Visit AWS SNS(Simple Notification Service) page and create an Application for GCM. And please preserve an ARN.

# Installation

1. Run init.sh

```
./scripts/init.sh
```

2. Create config.php

Change a file name from 'config.php.template' to 'config.php' and edit it.

3. Generate files required by system.

``
php scripts/generate_init_files.php
```

4. Create database tables.

Execute DDLs located in schema/*.sql on your database to create tables.

5. cron

Configure a cron for running these two files.

 - scripts/send.php (each minute)
 - scripts/watch_rss_feed.php (by 5 minutes)

6. Setting a background process.

`scripts/notifier.php` is background worker that is run by PHP client mode.
Please set up a process monitoring tool to run this program as a daemon.

