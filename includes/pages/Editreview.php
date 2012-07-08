<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageEditreview extends Page {

	private $id = 0;
	private $postErrors = array();
	
	protected function render ( ) {
		$id = $_GET['id'];
		
		if ( !is_numeric($id) || $id < 1 ) {
			header('Location: /');
			return;
		}
		
		$this->id = $id;
		
		if ( isset($_POST['submit-editreview']) ) {
			$this->handleEditReview();
		}
		$this->createForm();
		
		$this->title = 'Edit review';
	}
	
	private function handleEditReview() {
		$id = $this->id;
		
		$i = gfDBQuery("SELECT * FROM `reviews` WHERE `id` = $id");
		
		$result = gfDBGetResult($i);
		
		$episodeid = $result['episodeid'];
		
		if ( $result['userid'] != gfGetAuth()->get_userdata('userid') ) {
			header('Location: /');
			return;
		}
		
		$content = addslashes($_POST['content']);
		$overallrating = $_POST['rating-overall'];
		
		if ( !$content ) 
			$this->postErrors['content'] = 'Missing field.';
		if ( !$overallrating || (!is_numeric($overallrating) && $overallrating !== false ) )
			$this->postErrors['rating-overall'] = 'Missing field.';
		
		$ratingtypes = explode('|', $_POST['typesofratings']);
		$ratings = array();
		foreach( $ratingtypes as $ratingtype) {
			if ( !is_numeric($ratingtype) )
				continue;
			$ratings[$ratingtype] = $_POST['ratingtype-'.$ratingtype];
			if ( !$ratings[$ratingtype] || (!is_numeric($rating[$ratingtype]) && $rating[$ratingtype] !== false && $rating[$ratingtype] !== null ) )
				$this->postErrors['ratingtype-'.$ratingtype] = 'Missing field.';
		}
		
		if ( count($this->postErrors)>0 )
			return;
		
		$userid = gfGetAuth()->get_userdata('userid');
		
		gfDBQuery("UPDATE `reviews` 
				SET `content` = '$content', `rating` = $overallrating
				WHERE `id` = $id");
		
		foreach ( $ratings as $ratingtype => $rating ) {
			if ( $rating === null || $rating === 'null'
				|| $rating === false || $rating === 'false' ) {
				gfDBQuery("DELETE FROM `ratings` 
					WHERE `ratingtype` = $ratingtype
						AND `reviewid` = $id");
				continue;
			}
			if ( $rating === false )
				$rating = -1;
			
			$i = gfDBQuery("SELECT `id` FROM `ratings` 
					WHERE `ratingtype` = $ratingtype
						AND `reviewid` = $id");
			
			if ( gfDBGetNumRows($i) == 1 )
				gfDBQuery("UPDATE `ratings` 
					SET `rating` = $rating 
					WHERE `ratingtype` = $ratingtype
						AND `reviewid` = $id");
			else
				gfDBQuery("INSERT INTO `ratings`
					SET `rating` = $rating, 
						`ratingtype` = $ratingtype,
						`reviewid` = $id");
		}
		
		$i = gfDBQuery("SELECT COUNT(`id`) AS amount, 
					SUM(`rating`) AS total
				FROM `reviews` 
				WHERE `episodeid` = $episodeid AND `rating` != -1");
		
		$result = gfDBGetResult($i);
		
		$episoderating = ($result['total']/$result['amount'])*10;
		
		gfDBQuery("UPDATE `episodes` 
			SET `rating` = $episoderating 
			WHERE `id` = $episodeid");
		
		gfDBQuery("SELECT `season`, `inseason`, `type`
			FROM `episodes` 
			WHERE `id` = $episodeid");
		
		$result = gfDBGetResult($i);
		
		$inseason = $result['inseason'];
		if ( $inseason < 10 )
			$inseason = '0'.$inseason;
		
		if ( $result['type'] == 'f' )
			header('Location: ?p=episode&film='.$this->filmCode($result['season'], $inseason).'#review-'.$id);
		else
			header('Location: ?p=episode&episode='.$this->prodCode($result['season'], $inseason).'#review-'.$id);
	}
	
	private function createForm() {
		$id = $this->id;
		
		$i = gfDBQuery("SELECT * FROM `reviews` WHERE `id` = $id");
		
		$result = gfDBGetResult($i);
		
		if ( $result['userid'] != gfGetAuth()->get_userdata('userid') ) {
			header('Location: /');
			return;
		}
		
		$data = array (
			'content' => $result['content'],
			'rating-overall' => $result['rating']
		);
		
		$i = gfDBQuery("SELECT * FROM `ratings` WHERE `reviewid` = $id");
		
		while ( $result = gfDBGetResult($i) ) {
			$data['ratingtype-'.$result['ratingtype']] = $result['rating'];
		}
		
		$this->content = $this->reviewForm(array(), $data, 'edit');
	}
}
