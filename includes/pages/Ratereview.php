<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageRatereview extends Page {

	function ratereview() {
		$this->handleRating();
	}
	
	private function handleRating() {
		global $DB, $auth;
		
		$id = $_GET['id'];
		$rating = $_GET['rating'];
		
		// Check for sane input
		if ( !$id || !is_numeric($id) || $id < 1 || !$rating 
			|| !in_array($rating, array(1, -1)) || !$auth->isLoggedIn() ) {
			header('Location: /');
			return;
		}
		
		$userid = $auth->get_userdata('userid');
		
		$DB->query("SELECT `id` FROM `reviewratings` WHERE `reviewid` = $id AND `userid` = $userid");
		
		if ( $DB->get_num_rows() > 0 ) {
			// already rated, so let's change their rating!
			
			$result = $DB->get_result();
			
			$DB->query("UPDATE `reviewratings` SET `rating` = $rating WHERE `id` = {$result['id']}");
		} else {
			// new rating!
			
			$DB->query("INSERT INTO `reviewratings` SET `reviewid` = $id, `userid` = $userid, `rating` = $rating");
		}
		
		header("Location: /?p=episode&review=$id#review-$id");
	}

}
