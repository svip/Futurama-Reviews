<?php

define('REVIEWS', true);

require('config.php');

require('mysql.php');

$DB = new db_driver($dbHost, $dbName, $dbUser, $dbPass);

$DB->connect();

$DB->query("SET NAMES UTF8");

require('auth.php');

$page = $_REQUEST['p'];

require('page.php');

if ( file_exists('pages/'.$page.'.php') ) {
	require ('pages/'.$page.'.php');
} else {
	require ('pages/index.php');
}

require('output.php');

$output->outputPage ( $page );
