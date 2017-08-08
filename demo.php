<?php

/**
 * demo.php - Script to compare Redis response times
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

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

// Configuration values
require_once('config.php');

$instance_ip = file_get_contents('http://169.254.169.254/latest/meta-data/public-ipv4');
$messages = null;

?>
<html lang="en" ng-app="myModule">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Latency test of hybrid architectures with Amazon ElastiCache</title>
	<link rel="stylesheet" href="bootstrap-3.3.7/css/bootstrap.min.css" />
	<script src="angular-1.6.5/angular.min.js"></script>
	<script src="angular-1.6.5/angular-sanitize.min.js"></script>
	
	<script type="text/javascript">
	var myApp = angular.module('myModule', ['ngSanitize']).controller('myController', function($scope, $http) {
		// defaults
		var debug = true;
		var runScript = '<?php echo sprintf("http://%s/run.php", $instance_ip); ?>';
		var messages = '';

		$scope.mysqlEndpoint = '<?php echo $mysql_endpoint; ?>';
		$scope.mysqlPort = '<?php echo $mysql_port; ?>';
		$scope.mysqlUsername = '<?php echo $mysql_username; ?>';
		$scope.mysqlDatabase = '<?php echo $mysql_database; ?>';
		$scope.query = 'SHOW DATABASES';
		// uncomment to add support for external Redis server
		//$scope.redisEndpoint = '<?php echo $redis_endpoint; ?>';
		//$scope.redisPort = '<?php echo $redis_port; ?>';
		$scope.sendButton = false;
		
		$scope.clearCache = function() {
			$http({
				method: 'GET',
				url: runScript
			}).then(function successCallback(response){
				$scope.messages = '<div class="alert alert-' + response.data.status + '">' + response.data.messages.join("\n") + '</div>';
			}, function errorCallback(response){
				$scope.messages = '<div class="alert alert-' + response.data.status + '">' + response.data.messages.join("\n") + '</div>';
			});
		}
		
		$scope.processData = function() {
			var data = {};
			data.mysqlEndpoint = $scope.mysqlEndpoint;
			data.mysqlPort = $scope.mysqlPort;
			data.mysqlUsername = $scope.mysqlUsername;
			data.mysqlPassword = $scope.mysqlPassword;
			data.mysqlDatabase = $scope.mysqlDatabase;
			data.query = $scope.query;
			// uncomment to add support for external Redis server
			//data.redisEndpoint = $scope.redisEndpoint;
			//data.redisPort = $scope.redisPort;
			$scope.sendButton = true;

			$http({
				method: 'POST',
				url: runScript,
				data: data
			}).then(function successCallback(response){
				var r = response.data;
				if (r.status == 'success') {
					if (r.origin == 'cache') {
						r.origin = 'USING CACHE';
					}

					var buf = 'Total time (' + r.origin + '): ' + r.time + " seconds\n";
					buf += r.data.join("\n");
					$scope.results = buf;
				} else if (r.status == 'error') {
					r.status = 'danger';
				}

				$scope.messages = '<div class="alert alert-' + r.status + '">' + r.messages.join("\n") + '</div>';
				$scope.sendButton = false;
			}, function errorCallback(response){
				$scope.messages = '<div class="alert alert-' + r.status + '">' + 'Something went wrong calling run.php, please check your webserver logs' + '</div>';
				$scope.sendButton = false;
			});
		}
	});
	</script>
</head>

<body>
  <br />
  <div class="container" ng-controller="myController">
	<div class="jumbotron container">
		<h2>Latency reduction of hybrid architectures with Amazon ElastiCache<br/>
			<small>Demo script to measure response time from database and cache</small></h2>
		<br/>

		<form class="form-horizontal">
			<div class="form-group">
				<label class="control-label col-sm-2">Instance Address:</label>
				<div class="col-sm-6"><label class="control-label"><?php echo $instance_ip; ?></label><br /> <span class="text-danger">Allow access in your database's firewall to this address</span></div>
			</div>
<!-- Uncomment to add support for external Redis server
			<div class="form-group">
				<label class="control-label col-sm-2">Amazon ElastiCache:</label>
				<div class="col-sm-6"><label class="control-label">{{ redisEndpoint }}:{{ redisPort }}</label></div>
			</div>
-->
			<div class="form-group">
				<label for="mysqlEndpoint" class="control-label col-sm-2">MySQL address:</label>
				<div class="col-sm-6"><input class="form-control" type="text" id="mysqlEndpoint" ng-model="mysqlEndpoint" placeholder="Database address (hostname or IP)"><span id="mysqlPort" ng-model="mysqlPort"></div>
			</div>
			<div class="form-group">
				<label for="mysqlDatabase" class="control-label col-sm-2">Database Name:</label>
				<div class="col-sm-4"><input class="form-control" type="text" id="mysqlDatabase" ng-model="mysqlDatabase" placeholder="Database's name">
				</div>
			</div>
			<div class="form-group">
				<label for="mysqlUsername" class="control-label col-sm-2">MySQL Username:</label>
				<div class="col-sm-4"><input class="form-control" type="text" id="mysqlUsername" ng-model="mysqlUsername" placeholder="Database's connection username"></div>
			</div>
			<div class="form-group">
				<label for="mysqlPassword" class="control-label col-sm-2">MySQL Password:</label>
				<div class="col-sm-4"><input class="form-control" type="password" id="mysqlPassword" ng-model="mysqlPassword" placeholder="Database's connection password"> <span class="text-warning">Your database's password will NEVER be store</span>
				</div>
			</div>
			<div class="form-group">
				<label for="query" class="control-label col-sm-2">Query:</label>
				<div class="col-sm-6"><input class="form-control" type="text" id="query" ng-model="query" placeholder="SHOW DATABASES" > <i>For security, only SELECT, SHOW, DESCRIBE and EXPLAIN will be allowed</i>
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-10 text-center">
					<span class="col-sm-2">&nbsp;</span>
					<span class="col-sm-4">
						<button type="button" class="btn btn-primary" ng-click="processData()" ng-disabled="sendButton">Test Query</button> &nbsp;&nbsp;&nbsp;&nbsp;
					</span>
					<span class="col-sm-4">
						<button type="button" class="btn btn-default" ng-click="clearCache()" ng-disabled="clearButton">Clear Cache</button>
					</span>
				</div>
			</div>
			
			<div>&nbsp;</div>

			<div class="form-group">
                                <label for="results" class="control-label col-sm-2">Results:</label>
                                <div class="col-sm-6">
<div ng-bind-html="messages"></div>
<textarea id="results" name="results" ng-model="results" class="form-control" rows="10" disabled></textarea>
				</div>
                        </div>
		</form>

	</div>
  </div>
</body>

</html>
