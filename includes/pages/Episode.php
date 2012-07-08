<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageEpisode extends Page {

	private $type = 'e';
	private $postErrors = array();
	private $id = null;
	private $ratings = array();
	
	private $navigationFormat = '<table class="infobox" cellspacing="1">
	<tr>
		<th>Previous</th><th>Next</th>
	</tr>
	<tr>
		<td>{{PREVIOUS}}</td><td>{{NEXT}}</td>
	</tr>
</table>';
	private $episodeFormat = '<table class="infobox" cellspacing="1">
	<tr>
		<th>Production number</th><td>{{PRODUCTIONNUMBER}}</td>
	</tr>
	<tr>
		<th>Reviews written</th><td>{{REVIEWS}}</td>
	</tr>
	<tr>
		<th>Overall rating{{UPDATERATING}}</th><td>{{RATING}}</td>
	</tr>
	{{MISCRATINGS}}
</table>';
	private $filmFormat = '<table class="infobox" cellspacing="1">
	<tr>
		<th>Film number</th><td>{{FILM}}</td>
	</tr>
	<tr>
		<th>Reviews written</th><td>{{REVIEWS}}</td>
	</tr>
	<tr>
		<th>Overall rating{{UPDATERATING}}</th><td>{{RATING}}</td>
	</tr>
	{{MISCRATINGS}}
</table>';

	protected function render ( ) {
		if ( $_POST['submit-review'] ) {
			$this->handleReviewPost();
		}
		$this->makePage();
	}
	
	private function getEpisodeId() {
		global $DB;
		
		$this->type = 'e';
		
		if ( $this->id != null )
			return $this->id;
		
		$episode = $_GET['episode'];
		if ( !$episode )
			return null;
		
		$episode = explode('ACV', $episode);
		
		if ( count($episode) != 2 )
			return null;
			
		if ( !is_numeric($episode[0]) || !is_numeric($episode[1]) )
			return null;
		
		$season = $episode[0]+0;
		$inseason = $episode[1]+0;
		
		$DB->query("SELECT `id` FROM `episodes` WHERE `season` = $season AND `inseason` = $inseason");
		
		if ( $DB->get_num_rows() === 0 )
			return null;
		
		$result = $DB->get_result();
		
		$this->id = $result['id'];
		
		return $result['id'];
	}
	
	private function getFilmId() {
		global $DB;
		
		$this->type = 'f';
		
		if ( $this->id != null )
			return $this->id;
		
		$film = $_GET['film'];
		if ( !$film )
			return null;
			
		$film = explode('-', $film);
		
		if ( count($film) != 2 )
			return null;
			
		if ( !is_numeric($film[0]) || !is_numeric($film[1]) )
			return null;
			
		$season = $film[0]+0;
		$inseason = ($film[1]-1)*4+1;
		
		$DB->query("SELECT `id` FROM `episodes` WHERE `season` = $season AND `inseason` = $inseason");
		
		if ( $DB->get_num_rows() === 0 )
			return null;
		
		$result = $DB->get_result();
		
		$this->id = $result['id'];
		
		return $result['id'];
	}
	
	private function getIdFromReview() {
		global $DB;
		
		$reviewid = $_GET['review'];
		
		if ( $this->id != null )
			return $this->id;
		
		if ( !is_numeric($reviewid) || $reviewid <= 0 )
			return null;
		
		$DB->query("SELECT e.`type`, e.`id` 
			FROM `reviews` r 
				JOIN `episodes` e 
					ON e.`id` = r.`episodeid`
			WHERE r.`id` = $reviewid");
		
		if ( $DB->get_num_rows() === 0 )
			return null;
		
		$result = $DB->get_result();
		
		$this->type = $result['type'];
		
		$this->id = $result['id'];
		
		return $result['id'];
	}
	
	private function getId() {
		$id = null;
		if ( isset($_GET['episode']) )
			return $this->getEpisodeId();
		elseif ( isset($_GET['film']) )
			return $this->getFilmId();
		elseif ( isset($_GET['review']) )
			return $this->getIdFromReview();
		if ( $id == null )
			return null;
	}
	
	
	private function handleReviewPost() {
		global $DB, $auth;
		
		$id = $this->getId();
		if ( $id == null ) {
			header('Location: /');
			return;
		}
			
		if ( !$auth->isReviewer() || $this->haveWrittenReview($id, $auth->get_userdata('userid') ) )
			return;
		
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
		
		$userid = $auth->get_userdata('userid');
		
		$DB->query("INSERT INTO `reviews` SET `episodeid` = $id, `userid` = $userid,
			`content` = '$content', `rating` = $overallrating, `date` = ".time());
		
		$reviewid = $DB->get_insert_id();
		
		foreach ( $ratings as $ratingtype => $rating ) {
			if ( $rating === null || $rating === false )
				continue;
			if ( $rating === false )
				$rating = -1;
			$DB->query("INSERT INTO `ratings`
				SET `reviewid` = $reviewid,
					`ratingtype` = $ratingtype, `rating` = $rating");
		}
		
		$DB->query("SELECT COUNT(`id`) AS amount, SUM(`rating`) AS total
				FROM `reviews` WHERE `episodeid` = $id AND `rating` != -1");
		
		$result = $DB->get_result();
		
		$episoderating = ($result['total']/$result['amount'])*10;
		
		$DB->query("UPDATE `episodes` 
				SET `rating` = $episoderating,
					`reviews` = `reviews`+1
				WHERE `id` = $id");
	}
	
	private function createNavigation ( $episodeid ) {
		global $DB;
		
		$DB->query("SELECT `inseason`, `season`, `type`
			FROM `episodes`
			WHERE `id` = $episodeid");
		
		$result = $DB->get_result();
		$season = $result['season'];
		$inseason = $result['inseason'];
		$type = $result['type'];
		
		$pinseason = ($type=='f'?$inseason-4:$inseason-1);
		$ninseason = ($type=='f'?$inseason+4:$inseason+1);
		
		$DB->query("SELECT `season`, `inseason`, `title`, `type`
			FROM `episodes`
			WHERE
				CASE WHEN $inseason = 1
				THEN `season` = $season-1
					AND `inseason` < 100
				ELSE `season` = $season
					AND `inseason` = $pinseason
				END
			ORDER BY `inseason` DESC
			LIMIT 1");
		
		$previous = '-';
		
		if ( $DB->get_num_rows() > 0 ) {
			$result = $DB->get_result();
			if ( $result['type'] == 'e' )
				$previous = '<a href="/?p=episode&amp;episode='.$this->prodCode($result['season'], $result['inseason']).'">'.$result['title'].'</a>';
			else
				$previous = '<a href="/?p=episode&amp;film='.$this->filmCode($result['season'], $result['inseason']).'"><i>'.$result['title'].'</i></a>';
		}
		
		$DB->query("SELECT `season`, `inseason`, `title`, `type`
			FROM `episodes`
			WHERE
				CASE WHEN EXISTS(SELECT `id`
					FROM `episodes`
					WHERE `season` = $season
						AND `inseason` = $ninseason
				)
				THEN `season` = $season
					AND `inseason` = $ninseason
				ELSE `season` = $season+1
					AND `inseason` = 1
				END");
		
		$next = '-';
		
		if ( $DB->get_num_rows() > 0 ) {
			$result = $DB->get_result();
			if ( $result['type'] == 'e' )
				$next = '<a href="/?p=episode&amp;episode='.$this->prodCode($result['season'], $result['inseason']).'">'.$result['title'].'</a>';
			else
				$next = '<a href="/?p=episode&amp;film='.$this->filmCode($result['season'], $result['inseason']).'"><i>'.$result['title'].'</i></a>';
		}
		
		return str_replace (
			array (
				'{{PREVIOUS}}',
				'{{NEXT}}',
			),
			array (
				$previous,
				$next,
			),
			$this->navigationFormat
		);
	}
	
	protected function prodCode ( $season, $inseason ) {
		if ( $inseason < 10 )
			$inseason = '0'.$inseason;
		return $season.'ACV'.$inseason;
	}
	
	protected function filmCode ( $season, $inseason ) {
		return $season.'-'.(floor($inseason/4)+1);
	}
	
	private function makePage() {
		global $DB, $auth;
		
		$id = $this->getId();
		if ( $id == null ) {
			header('Location: /');
			return;
		}
		
		$DB->query("SELECT * FROM `episodes` WHERE `id` = $id");
		
		if ( $DB->get_num_rows() === 0 ) {
			header('Location: /');
			return;		
		}
		
		$result = $DB->get_result();
		
		$this->title = $result['title'];
		
		$content = '';
		
		$content .= $this->createNavigation($id);
		
		if ( $this->type == 'e' ) {
			$inseason = $result['inseason'];
			if ( $inseason < 10 )
				$inseason = '0'.$inseason;
			$content .= str_replace(
				array(
					'{{PRODUCTIONNUMBER}}',
					'{{REVIEWS}}',
					'{{RATING}}',
					'{{UPDATERATING}}',
				),
				array(
					$result['season'].'ACV'.$inseason,
					$result['reviews'],
					$this->renderPercentageRating($result['rating']),
					($auth->isAdmin()
						?' (<a href="./?p=updateratings&amp;id='.$id.'">Update</a>)'
						:''
					),
				),
				$this->episodeFormat
			);
		} else {
			$t = explode('-', $_GET['film']);
			$content .= str_replace(
				array(
					'{{FILM}}',
					'{{REVIEWS}}',
					'{{RATING}}',
					'{{UPDATERATING}}',
				),
				array(
					$t[1],
					$result['reviews'],
					$this->renderPercentageRating($result['rating']),
					($auth->isAdmin()
						?' (<a href="./?p=updateratings&amp;id='.$id.'">Update</a>)'
						:''
					),
				),
				$this->filmFormat
			);
		}
		
		$content .= $this->getReviews($id);
		
		$ratingscontent = '';
		
		foreach($this->ratings as $ratingtype) {
			$ratingscontent .= '	<tr>
		<th>'.$ratingtype['name'].'</th><td>'.$this->renderPercentageRating((array_sum($ratingtype['ratings'])/count($ratingtype['ratings']))*10).'</td>
	</tr>
';
		}
		
		$content = str_replace('{{MISCRATINGS}}', $ratingscontent, $content);
		
		if ( $auth->isLoggedIn() && $auth->isReviewer() && !$this->haveWrittenReview($id, $auth->get_userdata('userid')) ) {
			$content .= $this->reviewForm($this->postErrors);
		}
		
		$this->content = $content;
	}
	
	private function haveWrittenReview($episodeid, $userid) {
		global $DB;
		
		if ( is_null($episodeid) || is_null($userid) )
			return false;
		
		$DB->query("SELECT `id` FROM `reviews` WHERE `userid` = $userid AND `episodeid` = $episodeid");
		
		if ( $DB->get_num_rows()>0 )
			return true;
		
		return false;
	}
	
	private function getReviews($id) {
		global $DB, $auth;
		
		if ( $auth->getMode() == 'reviewer' ) {
			$userid = $auth->get_userdata('userid');
			$DB->query("SELECT r.*, u.`username` 
					FROM `reviews` r 
						JOIN `users` u 
							ON r.`userid` = u.`id` 
					WHERE r.`episodeid` = $id AND r.`userid` = $userid 
					ORDER BY r.`date`");
		} else {		
			$DB->query("SELECT r.*, u.`username` FROM `reviews` r JOIN `users` u ON r.`userid` = u.`id` WHERE r.`episodeid` = $id ORDER BY r.`date`");
		}
		
		$content = '';
		
		$ratings = array();
		
		while ( $result = $DB->get_result() ) {
			$content .= '<div class="review" id="review-'.$result['id'].'">'.$this->reviewEditLink($result['id'], $result['userid']).'
	<p class="info">Written by <a href="?p=user&amp;id='.$result['userid'].'" style="font-weight: bold;">'.$result['username'].'</a> on '.$this->timeStamp($result['date']).'.</p>
	<table class="ratings" cellspacing="1">
		<tr>
			<th>Overall rating:</th><td>'.$this->renderRating($result['rating']).'</td>
		</tr>
';
			$DB->query("SELECT r.*, t.`name`
				FROM `ratings` r
					JOIN `ratingtypes` t
						ON r.`ratingtype` = t.`id`
				WHERE r.`reviewid` = ".$result['id']."
				ORDER BY r.`ratingtype`", 10);
			
			while ( $rating = $DB->get_result(10) ) {
				$content .= '		<tr>
			<th>'.$rating['name'].':</th><td>'.$this->renderRating($rating['rating']).'</td>
		</tr>
';
				if ( !isset($ratings[$rating['ratingtype']]) )
					$ratings[$rating['ratingtype']] = array(
						'name'    => $rating['name'],
						'ratings' => array()
					);
				$ratings[$rating['ratingtype']]['ratings'][] = $rating['rating'];
			}
			
			$DB->query("SELECT `rating` FROM `reviewratings` WHERE `reviewid` = ".$result['id'], 10);
			$reviewratings = array(1 => 0, -1 => 0);
			$canrate = false;
			if ( $auth->isLoggedin() )
				$canrate = true;
			while ( $reviewrating = $DB->get_result(10) )
				$reviewratings[$reviewrating['rating']]++;
			$content .= '	</table>
	<div class="content">'.$this->renderContent($result['content']).'</div>
	<div class="reviewrating"><span class="rating-approval">'.$reviewratings[1].' approves</span> and <span class="rating-disapproval">'.$reviewratings[-1].' disapproves</span> of this review'.($canrate?' (<a href="?p=ratereview&amp;id='.$result['id'].'&amp;rating=1">Approve</a> &middot; <a href="?p=ratereview&amp;id='.$result['id'].'&amp;rating=-1">Disapprove</a>)':'').'</div>
	<div class="clear"></div>
</div>
';
		}
		
		$this->ratings = $ratings;
		
		return $content;
	}
}
