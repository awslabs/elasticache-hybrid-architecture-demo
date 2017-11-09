<?php

/**
 * run.php - Script to compare Redis vs raw database response times
 * Copyright 2017 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0/
 *
 * or in the "license" file accompanying this file. This file is distributed 
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either 
 * express or implied. See the License for the specific language governing 
 * permissions and limitations under the License.
 */

require_once('config.php');

// Time counter
$start_time = microtime();


function jsonResponse($messages, $status='success', $data=null, $origin=null) {
    global $start_time;

    $time = microtime() - $start_time;

    if (!is_array($messages))
    $messages = array($messages);

    $object = array(
        'status' => $status,
        'messages' => $messages,
        'origin' => $origin,
        'data' => $data,
        'time' => $time
    );

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($object);
    exit;
}

function validateQuery($query) {
    // Allowed statements: select, show, describe, explain
    // explicitly forbidden statements
    if (preg_match('/(alter|create|drop|rename|truncate|insert|update|delete|grant|modify|set|commit|rollback|call|do|handler|load|replace|start|stop|lock|savepoint|release|xa|purge|reset|change|prepare|execute|deallocate|begin|declare|revoke|analyze|check|checksum|optimize|repair|install|uninstall|binlog|cache|flush|kill|shutdown|use|help)\s/i', $query)) {
        return false;
    } elseif (strpos($query, ';') !== false) { // avoid multi-statements
        return false;
    } else {
        return true;
    }
}

function loadSample($db) {
	$query = "DROP TABLE crimes";
	$res = $db->query($query);

	$query = "
CREATE TABLE crimes (
    report_date DATE NOT NULL,
	report_no INT NOT NULL,
	occurred_date DATE NOT NULL,
	occurred_time INT,
	area_code SMALLINT,
	area_name VARCHAR(64),
	rd INT,
	crime_code INT,
	crime_code_desc VARCHAR(128),
	status_code VARCHAR(16),
	status_desc VARCHAR(64),
	location VARCHAR(128),
	cross_street VARCHAR(128),
	geolocation VARCHAR(32)
)";

	$res = $db->query($query);
	
	$query = "LOAD DATA LOCAL INFILE '/var/www/html/crimes-2012-2015.csv' INTO TABLE crimes FIELDS TERMINATED BY ',' ENCLOSED BY '\"' IGNORE 1 LINES (@report_date, report_no, @occurred_date, occurred_time, area_code, area_name, rd, crime_code, crime_code_desc, status_code, status_desc, location, cross_street, geolocation) SET report_date = str_to_date(@report_date, '%m/%d/%Y'), occurred_date = str_to_date(@occurred_date, '%m/%d/%Y')";
	$res = $db->query($query);
	
	jsonResponse("Sample table 'crimes' created, now you can run queries such as 'SELECT * FROM crimes LIMIT 20' to compare results with cache and without cache");
}


// Check for allowed usage
$allow_methods = array('POST', 'GET');
if (!isset($_SERVER['REQUEST_METHOD'])
	|| !in_array($_SERVER['REQUEST_METHOD'], $allow_methods))
	jsonResponse('Method not supported', 'error');


// Process queries
if (strcmp($_SERVER['REQUEST_METHOD'], 'POST') == false) {
	$postdata = file_get_contents("php://input");
	$post = json_decode($postdata);

	$action = (isset($post->action)) ? trim($post->action) : null;
	$mysql_endpoint = (isset($post->mysqlEndpoint)) ? trim($post->mysqlEndpoint) : null;
	$mysql_port = (isset($post->mysqlPort)) ? trim($post->mysqlPort) : null;
	$mysql_database = (isset($post->mysqlDatabase)) ? trim($post->mysqlDatabase) : null;
	$mysql_username = (isset($post->mysqlUsername)) ? trim($post->mysqlUsername) : null;
	$mysql_password = (isset($post->mysqlPassword)) ? trim($post->mysqlPassword) : null;
	$query = (isset($post->query)) ? trim($post->query) : null;
	// uncomment to add support for external or different Redis server
	//$redis_endpoint =  (isset($_POST['redisEndpoint'])) ? trim($_POST['redisEndpoint']) : null;
	//$redis_port = (isset($_POST['redisPort'])) ? trim($_POST['redisPort']) : null;

	if (!$mysql_endpoint
    	|| !$mysql_port
		|| !$mysql_username
		|| !$mysql_password
    	|| !$mysql_database) {
    	jsonResponse('All database parameters are required, aborting', 'error');
		// validations
	}
	
	if (strcmp($action, 'loadsample') == false) {
		$db = new mysqli($mysql_endpoint, $mysql_username, $mysql_password, $mysql_database);
		if ($db->connect_error)
	    	jsonResponse('Error connecting to database', 'error');
		loadSample($db);
	} elseif (strcmp($action, 'query') == false) {
		// uncomment to add support for external or different Redis server
		//} elseif (!isset($_POST['redisEndpoint'])
		//    || !isset($_POST['redisPort'])) {
		//    jsonResponse('Redis connection values missing, aborting', 'error');
		if (!validateQuery($query)) {
    		jsonResponse('Only SELECT, SHOW, DESCRIBE and EXPLAIN statements are allowed. Semicolon is forbidden as well', 'error');
		}

		// Redis object creation
		$redis = new Redis();
		if (!$redis->connect($redis_endpoint, $redis_port)) {
			jsonResponse("Error connecting to Redis, please check your settings", 'error');
		}

		// Database query and cache key
		$query_key = hash("sha256", $query);

		// Verify the cache before run the query on database
		$query_result = null;
		$cache_result = $redis->get($query_key);
		if ($cache_result !== false) {
    		// if result is found in cache, return result from cache
			jsonResponse('Loading data from AMAZON ELASTICACHE - REDIS', 'success', unserialize($cache_result), 'cache');
		} else {
    		// Run query against database
			$db = new mysqli($mysql_endpoint, $mysql_username, $mysql_password, $mysql_database);
			if ($db->connect_error)
        		jsonResponse('Error connecting to database', 'error');

			$res = $db->query($query);
			$query_result = $res->fetch_all();
			$value = serialize($query_result);

			// Save result into cache
			$redis->set($query_key, $value);

			$db->close();
			jsonResponse('Results loaded directly from database', 'success', $query_result, 'database');
		}
	}

	jsonResponse(sprintf("Invalid action %s", $action), 'error');
} elseif (strcmp($_SERVER['REQUEST_METHOD'], 'GET') == false) {
	$redis = new Redis();
	if (!$redis->connect($redis_endpoint, $redis_port)) {
		jsonResponse('Error connecting to Redis, please check your settings', 'error');
	}
	if (!$redis->flushAll()) {
		jsonResponse('Error flusing cache', 'error');
	}
	jsonResponse('Redis cache was successful flushed', 'success', null, 'redis');
}

jsonResponse('Invalid request', 'error');