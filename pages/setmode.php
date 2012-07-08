<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class setmode extends page {
	
	function setmode() {
		global $auth;
		
		$auth->setMode($_GET['mode']);
		
		header('Location: '.$_SERVER['HTTP_REFERER']);
	}
	
}

$page = new setmode();
