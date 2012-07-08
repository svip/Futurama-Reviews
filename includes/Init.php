<?php

require('includes/GlobalVariables.php')

require('config.php');

require('includes/Database.php');

require('includes/GlobalFunctions.php');

require('includes/Authentication.php');

$page = $_REQUEST['p'];

require('includes/Page.php');

if ( !file_exists('includes/pages/'.ucfirst($page).'.php') )
	$page = 'index';

require ('includes/pages/'.ucfirst($page).'.php');

$className = 'Page'.ucfirst($page);

$page = new $className();

require('includes/Output.php');

Output::RenderPage($page);
