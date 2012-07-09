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
		<td>$1</td><td>$2</td>
	</tr>
</table>';
	private $episodeFormat = '<table class="infobox" cellspacing="1">
	<tr>
		<th>Production number</th><td>$1</td>
	</tr>
	<tr>
		<th>Reviews written</th><td>$2</td>
	</tr>
	<tr>
		<th>Overall rating$4</th><td>$3</td>
	</tr>
	{{MISCRATINGS}}
</table>';
	private $filmFormat = '<table class="infobox" cellspacing="1">
	<tr>
		<th>Film number</th><td>$1</td>
	</tr>
	<tr>
		<th>Reviews written</th><td>$2</td>
	</tr>
	<tr>
		<th>Overall rating$4</th><td>$3</td>
	</tr>
	{{MISCRATINGS}}
</table>';

	protected function render ( ) {
		if ( isset($_POST['submit-review']) ) {
			$this->handleReviewPost();
		}
		$this->makePage();
	}
	
	private function getEpisodeId() {
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
		
		$i = gfDBQuery("SELECT `id`
			FROM `episodes`
			WHERE `season` = $season
				AND `inseason` = $inseason");
		
		if ( gfDBGetNumRows($i) === 0 )
			return null;
		
		$result = gfDBGetResult($i);
		
		$this->id = $result['id'];
		
		return $result['id'];
	}
	
	private function getFilmId() {
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
		
		$i = gfDBQuery("SELECT `id`
			FROM `episodes`
			WHERE `season` = $season
				AND `inseason` = $inseason");
		
		if ( gfDBGetNumRows($i) === 0 )
			return null;
		
		$result = gfDBGetResult($i);
		
		$this->id = $result['id'];
		
		return $result['id'];
	}
	
	private function getIdFromReview() {
		$reviewid = $_GET['review'];
		
		if ( $this->id != null )
			return $this->id;
		
		if ( !is_numeric($reviewid) || $reviewid <= 0 )
			return null;
		
		$i = gfDBQuery("SELECT e.`type`, e.`id` 
			FROM `reviews` r 
				JOIN `episodes` e 
					ON e.`id` = r.`episodeid`
			WHERE r.`id` = $reviewid");
		
		if ( gfDBGetNumRows($i) === 0 )
			return null;
		
		$result = gfDBGetResult($i);
		
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
		$id = $this->getId();
		if ( $id == null ) {
			gfRedirect(gfLink());
			return;
		}
			
		if ( !gfGetAuth()->isReviewer() || $this->haveWrittenReview($id, gfGetAuth()->get_userdata('userid') ) )
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
		
		$userid = gfGetAuth()->get_userdata('userid');
		
		$i = gfDBQuery("INSERT INTO `reviews`
			SET `episodeid` = $id, `userid` = $userid,
			`content` = '$content', `rating` = $overallrating,
			`date` = ".time());
		
		$reviewid = gfDBGetInsertId($i);
		
		foreach ( $ratings as $ratingtype => $rating ) {
			if ( $rating === null || $rating === false )
				continue;
			if ( $rating === false )
				$rating = -1;
			gfDBQuery("INSERT INTO `ratings`
				SET `reviewid` = $reviewid,
					`ratingtype` = $ratingtype, `rating` = $rating");
		}
		
		$i = gfDBQuery("SELECT COUNT(`id`) AS amount,
				SUM(`rating`) AS total
			FROM `reviews`
			WHERE `episodeid` = $id 
				AND `rating` != -1");
		
		$result = gfDBGetResult($i);
		
		$episoderating = ($result['total']/$result['amount'])*10;
		
		gfDBQuery("UPDATE `episodes` 
				SET `rating` = $episoderating,
					`reviews` = `reviews`+1
				WHERE `id` = $id");
	}
	
	private function createNavigation ( $episodeid ) {
		$i = gfDBQuery("SELECT `inseason`, `season`, `type`
			FROM `episodes`
			WHERE `id` = $episodeid");
		
		$result = gfDBGetResult($i);
		$season = $result['season'];
		$inseason = $result['inseason'];
		$type = $result['type'];
		
		$pinseason = ($type=='f'?$inseason-4:$inseason-1);
		$ninseason = ($type=='f'?$inseason+4:$inseason+1);
		
		$i = gfDBQuery("SELECT `season`, `inseason`, `title`, `type`
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
		
		if ( gfDBGetNumRows($i) > 0 ) {
			$result = gfDBGetResult($i);
			if ( $result['type'] == 'e' )
				$previous = gfRawMsg('<a href="$1">$2</a>',
					gfLink('episode',
						array('episode'=>$this->prodCode($result['season'], $result['inseason']))
					),
					$result['title']
				);
			else
				$previous = gfRawMsg('<a href="$1">$2</a>',
					gfLink('episode',
						array('film'=>$this->filmCode($result['season'], $result['inseason']))
					),
					$result['title']
				);
		}
		
		$i = gfDBQuery("SELECT `season`, `inseason`, `title`, `type`
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
		
		if ( gfDBGetNumRows($i) > 0 ) {
			$result = gfDBGetResult($i);
			if ( $result['type'] == 'e' )
				$next = gfRawMsg('<a href="$1">$2</a>',
					gfLink('episode',
						array('episode'=>$this->prodCode($result['season'], $result['inseason']))
					),
					$result['title']
				);
			else
				$next = gfRawMsg('<a href="$1">$2</a>',
					gfLink('episode',
						array('film'=>$this->filmCode($result['season'], $result['inseason']))
					),
					$result['title']
				);
		}
		
		return gfRawMsg($this->navigationFormat, $previous, $next);
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
		$id = $this->getId();
		if ( $id == null ) {
			gfRedirect(gfLink());
			return;
		}
		
		$i = gfDBQuery("SELECT * FROM `episodes` WHERE `id` = $id");
		
		if ( gfDBGetNumRows($i) === 0 ) {
			gfRedirect(gfLink());
			return;
		}
		
		$result = gfDBGetResult($i);
		
		$this->title = $result['title'];
		
		$content = '';
		
		$content .= $this->createNavigation($id);
		
		if ( $this->type == 'e' ) {
			$inseason = $result['inseason'];
			if ( $inseason < 10 )
				$inseason = '0'.$inseason;
			$content .= gfRawMsg($this->episodeFormat,
				$result['season'].'ACV'.$inseason,
				$result['reviews'],
				$this->renderPercentageRating($result['rating']),
				(gfGetAuth()->isAdmin()
					?gfRawMsg(' (<a href="$1">Update</a>)',
						gfLink('updateratings',
							array('id'=>$id)
						)
					)
					:''
				)
			);
		} else {
			$t = explode('-', $_GET['film']);
			$content .= gfRawMsg($this->filmFormat,
				$t[1],
				$result['reviews'],
				$this->renderPercentageRating($result['rating']),
				(gfGetAuth()->isAdmin()
					?gfRawMsg(' (<a href="$1">Update</a>)',
						gfLink('updateratings',
							array('id'=>$id)
						)
					)
					:''
				)
			);
		}
		
		$content .= $this->getReviews($id);
		
		$ratingscontent = '';
		
		foreach($this->ratings as $ratingtype) {
			$ratingscontent .= gfRawMsg('	<tr>
		<th>$1</th><td>$2</td>
	</tr>
',
				$ratingtype['name'],
				$this->renderPercentageRating(
					(array_sum($ratingtype['ratings'])
						/count($ratingtype['ratings']))*10)
			);
		}
		
		$content = str_replace('{{MISCRATINGS}}', $ratingscontent, $content);
		
		if ( gfGetAuth()->isLoggedIn() && gfGetAuth()->isReviewer() && !$this->haveWrittenReview($id, gfGetAuth()->get_userdata('userid')) ) {
			$content .= $this->reviewForm($this->postErrors);
		}
		
		$this->content = $content;
	}
	
	private function haveWrittenReview($episodeid, $userid) {
		if ( is_null($episodeid) || is_null($userid) )
			return false;
		
		$i = gfDBQuery("SELECT `id` FROM `reviews` WHERE `userid` = $userid AND `episodeid` = $episodeid");
		
		if ( gfDBGetNumRows($i)>0 )
			return true;
		
		return false;
	}
	
	private function getReviews($id) {
		if ( gfGetAuth()->getMode() == 'reviewer' ) {
			$userid = gfGetAuth()->get_userdata('userid');
			$i = gfDBQuery("SELECT r.*, u.`username` 
					FROM `reviews` r 
						JOIN `users` u 
							ON r.`userid` = u.`id` 
					WHERE r.`episodeid` = $id AND r.`userid` = $userid 
					ORDER BY r.`date`");
		} else {		
			$i = gfDBQuery("SELECT r.*, u.`username`
				FROM `reviews` r
					JOIN `users` u
						ON r.`userid` = u.`id`
				WHERE r.`episodeid` = $id
				ORDER BY r.`date`");
		}
		
		$content = '';
		
		$ratings = array();
		
		while ( $result = gfDBGetResult($i) ) {
			$content .= gfRawMsg('<div class="review" id="review-$1">$2
	<p class="info">Written by <a href="$3" style="font-weight: bold;">$4</a> on $5.</p>
	<table class="ratings" cellspacing="1">
		<tr>
			<th>Overall rating:</th><td>$6</td>
		</tr>
',
				$result['id'],
				$this->reviewEditLink($result['id'],
					$result['userid']),
				gfLink('user', array('id'=>$result['userid'])),
				$result['username'],
				$this->timeStamp($result['date']),
				$this->renderRating($result['rating'])
			);
			$j = gfDBQuery("SELECT r.*, t.`name`
				FROM `ratings` r
					JOIN `ratingtypes` t
						ON r.`ratingtype` = t.`id`
				WHERE r.`reviewid` = ".$result['id']."
				ORDER BY r.`ratingtype`");
			
			while ( $rating = gfDBGetResult($j) ) {
				$content .= gfRawMsg('		<tr>
			<th>$1:</th><td>$2</td>
		</tr>
',
					$rating['name'],
					$this->renderRating($rating['rating'])
				);
				if ( !isset($ratings[$rating['ratingtype']]) )
					$ratings[$rating['ratingtype']] = array(
						'name'    => $rating['name'],
						'ratings' => array()
					);
				$ratings[$rating['ratingtype']]['ratings'][] = $rating['rating'];
			}
			
			$j = gfDBQuery("SELECT `rating`
				FROM `reviewratings`
				WHERE `reviewid` = ".$result['id']);
			$reviewratings = array(1 => 0, -1 => 0);
			$canrate = false;
			if ( gfGetAuth()->isLoggedin() )
				$canrate = true;
			while ( $reviewrating = gfDBGetResult($j) )
				$reviewratings[$reviewrating['rating']]++;
			$content .= gfRawMsg('	</table>
	<div class="content">$1</div>
	<div class="reviewrating"><span class="rating-approval">$2 approves</span> and <span class="rating-disapproval">$3 disapproves</span> of this review$4</div>
	<div class="clear"></div>
</div>
',
				$this->renderContent($result['content']),
				$reviewratings[1], $reviewratings[-1],
				($canrate
					?gfRawMsg(' (<a href="$1">Approve</a> &middot; <a href="$2">Disapprove</a>)',
						gfLink('ratereview',
							array('id'=>$result['id'],
								'rating'=>'1')),
						gfLink('ratereview',
							array('id'=>$result['id'],
								'rating'=>'-1'))
					)
					:''
				)
			);
		}
		
		$this->ratings = $ratings;
		
		return $content;
	}
}
