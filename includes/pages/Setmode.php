<?php

if ( !defined('REVIEWS') )
	gfRedirect();

class PageSetmode extends Page {
	
	protected function render ( ) {
		gfGetAuth()->setMode($_GET['mode']);
		
		gfRedirect($_SERVER['HTTP_REFERER']);
	}
	
}
