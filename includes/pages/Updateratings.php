<?php

if ( !defined('REVIEWS') )
	gfRedirect();

class PageUpdateratings extends Page {
	
	protected function render ( ) {
		if ( !gfGetAuth()->isAdmin() ) {
			gfRedirect();
			return;
		}
		
		$id = $_GET['id'];
		
		if ( !is_numeric($id) || $id < 1 ) {
			gfRedirect();
			return;
		}
		
		$this->title = 'Updating ratings...';
		
		$episodeid = $id;
		
		$i = gfDBQuery("SELECT COUNT(`id`) AS amount, 
					SUM(`rating`) AS total
				FROM `reviews` 
				WHERE `episodeid` = $episodeid AND `rating` != -1");
		
		$result = gfDBGetResult($i);
		
		$episoderating = ($result['total']/$result['amount'])*10;
		
		gfDBQuery("UPDATE `episodes` 
			SET `rating` = $episoderating 
			WHERE `id` = $episodeid");
		
		$i = gfDBQuery("SELECT `season`, `inseason`, `type`
			FROM `episodes` 
			WHERE `id` = $episodeid");
		
		$result = gfDBGetResult($i);
		
		if ( $result['type'] == 'f' )
			gfRedirect(gfLink('episode',
				array('film'=>
					$this->filmCode($result['season'],
						$result['inseason']))
			));
		else
			gfRedirect(gfLink('episode',
				array('episode'=>
					$this->prodCode($result['season'],
						$result['inseason']))
			));
	}
}
