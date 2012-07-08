<?php

require('includes/GlobalVariables.php');

require('config.php');

if ( $Debug ) {
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

require('includes/Database.php');

require('includes/GlobalFunctions.php');

require('includes/Authentication.php');

$page = gfGetPageName();

require('includes/Page.php');

if ( !file_exists('includes/pages/'.ucfirst($page).'.php') )
	$page = 'index';

require ('includes/pages/'.ucfirst($page).'.php');

$className = 'Page'.ucfirst($page);

$page = new $className();

require('includes/Output.php');

Output::RenderPage($page);
