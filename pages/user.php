<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class user extends page {

	function user() {
		$this->makePage();
	}
	
	private function makePage() {
		global $DB;
		
		$id = $_GET['id'];
		
		if ( !is_numeric($id) || $id <= 0 ) {
			header('Location: /');
			return;
		}
		
		$DB->query("SELECT * FROM `users` WHERE `id` = $id");
		
		$result = $DB->get_result();
		
		$this->title = $result['username'];
		
		$this->content = $this->getReviews($result['id']);
	}
	
	private function getReviews($userid) {
		global $DB;
		
		$DB->query("SELECT r.*, e.`title`
			FROM `reviews` r
				JOIN `episodes` e
					ON e.`id` = r.`episodeid`
			WHERE r.`userid` = $userid
			ORDER BY e.`season`, e.`type` DESC, e.`inseason`");		
		
		$content = '';
		
		while ( $result = $DB->get_result() ) {
			$content .= '<div class="review" id="review-'.$result['id'].'">'.$this->reviewEditLink($result['id'], $result['userid']).'
	<p class="info">Written for <a href="?p=episode&amp;review='.$result['id'].'" style="font-weight: bold;">'.$result['title'].'</a> on '.$this->timeStamp($result['date']).'.</p>
	<table class="ratings" cellspacing="1">
		<tr>
			<th>Overall rating:</th><td>'.$this->renderRating($result['rating']).'</td>
		</tr>
';
			$DB->query("SELECT r.*, t.`name` FROM `ratings` r JOIN `ratingtypes` t ON r.`ratingtype` = t.`id` WHERE r.`reviewid` = ".$result['id']." ORDER BY r.`ratingtype`", 10);
			
			while ( $rating = $DB->get_result(10) ) {
				$content .= '		<tr>
			<th>'.$rating['name'].':</th><td>'.$this->renderRating($rating['rating']).'</td>
		</tr>
';
			}
			$content .= '	</table>
	<div class="content">'.$this->renderContent($result['content']).'</div>
	<div class="clear"></div>
</div>
';
		}
		
		return $content;
	}

}

$page = new user();
