#!/bin/sh
#
# bootstrap.sh - Script to setup an Amazon ElastiCache environment for PHP
# Copyright 2017 Amazon.com, Inc. or its affiliates. All Rights Reserved.
#
# Licensed under the Apache License, Version 2.0 (the "License").
# You may not use this file except in compliance with the License.
# A copy of the License is located at
#
# http://aws.amazon.com/apache2.0/
#
# or in the "license" file accompanying this file. This file is distributed 
# on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either 
# express or implied. See the License for the specific language governing 
# permissions and limitations under the License.
#

if [ "$#" -lt 5 ]; then
	echo "Usage: $0 <elasticache_endpoint> <elasticache_port> <mysql_endpoint> <mysql_username> <mysql_database>"
	exit 1
fi

elasticache_endpoint="$1"
elasticache_port="$2"
mysql_endpoint="$3"
mysql_username="$4"
mysql_database="$5"


# setup instance
sleep 10
yum -y install httpd24 php70 php70-pecl-redis php70-mysqlnd unzip


# prepare php application
git clone https://github.com/awslabs/elasticache-hybrid-architecture-demo
cd elasticache-hybrid-architecture-demo
sed -e "s/{ELASTICACHE_ENDPOINT}/${elasticache_endpoint}/g" \
    -e "s/{ELASTICACHE_PORT}/${elasticache_port}/g" \
	-e "s/{MYSQL_ENDPOINT}/${mysql_endpoint}/g" \
	-e "s/{MYSQL_USERNAME}/${mysql_username}/g" \
	-e "s/{MYSQL_DATABASE}/${mysql_database}/g" \
	config_template.php > config.php

# prepare sample data
unzip sample-dataset-crimes-2012-2015.csv.zip
mv sample-dataset-crimes-2012-2015.csv crimes-2012-2015.csv

# move to document root
mv * /var/www/html/
chmod 0644 /var/www/html/demo.php


# enable http service
service httpd start
chkconfig httpd on
