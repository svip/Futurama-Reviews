<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageSetmode extends Page {
	
	protected function render ( ) {
		global $auth;
		
		$auth->setMode($_GET['mode']);
		
		header('Location: '.$_SERVER['HTTP_REFERER']);
	}
	
}
