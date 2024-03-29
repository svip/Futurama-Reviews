<?php

if ( !defined('REVIEWS') )
	gfRedirect();

class PageRatereview extends Page {

	protected function render ( ) {
		$this->handleRating();
	}
	
	private function handleRating() {
		$id = $_GET['id'];
		$rating = $_GET['rating'];
		
		// Check for sane input
		if ( !$id || !is_numeric($id) || $id < 1 || !$rating 
			|| !in_array($rating, array(1, -1))
			|| !gfGetAuth()->isLoggedIn() ) {
			gfRedirect();
			return;
		}
		
		$userid = gfGetAuth()->get_userdata('userid');
		
		$i = gfDBQuery("SELECT `id`
			FROM `reviewratings`
			WHERE `reviewid` = $id
				AND `userid` = $userid");
		
		if ( gfDBGetNumRows($i) > 0 ) {
			// already rated, so let's change their rating!
			
			$result = gfDBGetResult($i);
			
			gfDBQuery("UPDATE `reviewratings`
				SET `rating` = $rating
				WHERE `id` = {$result['id']}");
		} else {
			// new rating!
			
			gfDBQuery("INSERT INTO `reviewratings`
				SET `reviewid` = $id, `userid` = $userid,
					`rating` = $rating");
		}
		
		gfRedirect(gfLink('episode', array('review'=>$id), 'review-'.$id));
	}

}
