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
yum -y install httpd24 php70 php70-pecl-redis php70-mysqlnd
service httpd start
chkconfig httpd on


# setup demo page
wget https://s3.amazonaws.com/agleon-scripts/elasticache/elasticache-hybrid-architecture-demo.zip
unzip elasticache-hybrid-architecture-demo.zip
cd elasticache-hybrid-architecture-demo
sed -e "s/{ELASTICACHE_ENDPOINT}/${elasticache_endpoint}/g" \
    -e "s/{ELASTICACHE_PORT}/${elasticache_port}/g" \
	-e "s/{MYSQL_ENDPOINT}/${mysql_endpoint}/g" \
	-e "s/{MYSQL_USERNAME}/${mysql_username}/g" \
	-e "s/{MYSQL_DATABASE}/${mysql_database}/g" \
	config_template.php > config.php
mv * /var/www/html/
chmod 0644 /var/www/html/demo.php