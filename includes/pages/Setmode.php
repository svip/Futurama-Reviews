<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageSetmode extends Page {
	
	protected function render ( ) {
		gfGetAuth()->setMode($_GET['mode']);
		
		header('Location: '.$_SERVER['HTTP_REFERER']);
	}
	
}
