<?php

if ( !defined('REVIEWS') )
	gfRedirect();

class PageUser extends Page {

	protected function render ( ) {
		$this->makePage();
	}
	
	private function makePage() {
		$id = $_GET['id'];
		
		if ( !is_numeric($id) || $id <= 0 ) {
			gfRedirect();
			return;
		}
		
		$i = gfDBQuery("SELECT * FROM `users` WHERE `id` = $id");
		
		$result = gfDBGetResult($i);
		
		$this->title = $result['username'];
		
		$this->content = $this->getReviews($result['id']);
	}
	
	private function getReviews($userid) {
		$i = gfDBQuery("SELECT r.*, e.`title`
			FROM `reviews` r
				JOIN `episodes` e
					ON e.`id` = r.`episodeid`
			WHERE r.`userid` = $userid
			ORDER BY e.`season`, e.`type` DESC, e.`inseason`");
		
		$content = '';
		
		while ( $result = gfDBGetResult($i) ) {
			$content .= gfRawMsg('<div class="review" id="review-$1">$2
	<p class="info">Written for <a href="$3" style="font-weight: bold;">$4</a> on $5.</p>
	<table class="ratings" cellspacing="1">
		<tr>
			<th>Overall rating:</th><td>$6</td>
		</tr>
',
				$result['id'],
				$this->reviewEditLink($result['id'], $result['userid']),
				gfLink('episode', array('review'=>$result['id'])),
				$result['title'],
				$this->timeStamp($result['date']),
				$this->renderRating($result['rating'])
			);
			$j = gfDBQuery("SELECT r.*, t.`name`
				FROM `ratings` r
					JOIN `ratingtypes` t
						ON r.`ratingtype` = t.`id`
				WHERE r.`reviewid` = {$result['id']}
				ORDER BY r.`ratingtype`");
			
			while ( $rating = gfDBGetResult($j) ) {
				$content .= gfRawMsg('		<tr>
			<th>$1:</th><td>$2</td>
		</tr>
',
					$rating['name'],
					$this->renderRating($rating['rating'])
				);
			}
			$content .= gfRawMsg('	</table>
	<div class="content">$1</div>
	<div class="clear"></div>
</div>
',
				$this->renderContent($result['content'])
			);
		}
		
		return $content;
	}

}
