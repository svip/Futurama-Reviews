<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageUpdateratings extends Page {
	
	protected function render ( ) {
		global $auth;
		
		if ( !$auth->isAdmin() ) {
			header('Location: /');
			return;
		}
		
		$id = $_GET['id'];
		
		if ( !is_numeric($id) || $id < 1 ) {
			header('Location: /');
			return;
		}
		
		$this->id = $id;
		
		$this->title = 'Updating ratings...';
		
		$this->render();
	}
	
	protected function render ( ) {
		global $DB, $auth;
		
		$episodeid = $this->id;
		
		$DB->query("SELECT COUNT(`id`) AS amount, 
					SUM(`rating`) AS total
				FROM `reviews` 
				WHERE `episodeid` = $episodeid AND `rating` != -1");
		
		$result = $DB->get_result();
		
		$episoderating = ($result['total']/$result['amount'])*10;
		
		$DB->query("UPDATE `episodes` 
			SET `rating` = $episoderating 
			WHERE `id` = $episodeid");
		
		$DB->query("SELECT `season`, `inseason`, `type`
			FROM `episodes` 
			WHERE `id` = $episodeid");
		
		$result = $DB->get_result();
		
		$inseason = $result['inseason'];
		if ( $inseason < 10 )
			$inseason = '0'.$inseason;
		
		if ( $result['type'] == 'f' )
			header('Location: ?p=episode&film='.$this->filmCode($result['season'], $inseason));
		else
			header('Location: ?p=episode&episode='.$this->prodCode($result['season'], $inseason));
	}
}
