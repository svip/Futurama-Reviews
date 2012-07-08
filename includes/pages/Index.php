<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageIndex extends Page {
	
	protected function render ( ) {
		$this->makeIndex();
	}
	
	private function makeIndex() {
		$episodesCheck = array();
		
		if ( gfGetAuth()->isLoggedIn() ) {
			$userid = gfGetAuth()->get_userdata('userid');
			$i = gfDBQuery("SELECT `episodeid`, `rating`
				FROM `reviews`
				WHERE `userid` = $userid");
			while ( $result = gfDBGetResult($i) ) {
				$episodesCheck[$result['episodeid']] = $result['rating'];
			}
		}
		
		$i = gfDBQuery("SELECT *
			FROM `episodes`
			ORDER BY `season`, `type` DESC, `inseason`");
		$table = '{{TOC}}
<table cellspacing="1">
	<thead>
		<tr>
			<th>Episode</th><th>Overall rating</th><th>Reviews</th>
		</tr>
	</thead>
	<tbody>
';
		$curseason = 0;
		$seasonRatings = array();
		$seasonReviews = array();
		
		$totalRatings = array();
		$totalReviews = 0;
		
		$seasons = 0;
		
		$n = 0;
		
		while ( $result = gfDBGetResult($i) ) {
			$n++;
			if ( $result['season'] > $curseason ) {
				$curseason = $result['season'];
				$table .= '		<tr class="season" id="season-'.$result['season'].'">
			<th>Season '.$result['season'].'</th><th>{{SEASON-'.$result['season'].'-RATING}}</th><th>{{SEASON-'.$result['season'].'-REVIEWS}}</th>
		</tr>
';
				$seasonRatings[$result['season']] = array();
				$seasonReviews[$result['season']] = 0;
				$seasons++;
			}
			if ( gfGetAuth()->getMode() == 'reviewer' ) {
				if ( count($episodesCheck) > 0 && isset($episodesCheck[$result['id']]) ) {
					$reviews = 1;
				} else {
					$reviews = 0;
				}
				if ( isset($episodesCheck[$result['id']]) )
					$rating = $episodesCheck[$result['id']]*10;
				else
					$rating = 0;
			} elseif ( gfGetAuth()->getMode() == 'noratings' ) {
				$reviews = $result['reviews'];
				$rating = null;
			} else {
				$reviews = $result['reviews'];
				$rating = $result['rating'];
			}
			$endN = $n;
			if ( $result['type'] == 'f' )
				$endN = $n+3;
			$table .= '		<tr>
			<td>'.$this->titleLinkRender($result['title'], $result['season'], $result['inseason'], $result['type'], $result['id'], $n, $endN, $episodesCheck).'</td><td>'.(($reviews==0 || $rating==null)?'-':$this->renderPercentageRating($rating)).'</td><td>'.$reviews.' review'.($reviews!=1?'s':'').'</td>
		</tr>
';
			
			if ( $result['type'] == 'f' )
				$n += 3;
			if ( $reviews > 0 && $rating != null ) {
				$seasonRatings[$result['season']][] = $rating;
				$totalRatings[] = $rating;
			}
			$seasonReviews[$result['season']] += $reviews;
			$totalReviews += $reviews;
		}
		$table .= '		<tr class="season">
			<th>Total</th><th>'.(count($totalRatings)==0?'-':$this->renderPercentageRating(array_sum($totalRatings)/count($totalRatings))).'</th><th>'.$totalReviews.' reviews</th>
		</tr>
	</tbody>
</table>';

		$toc = "<ul>\n";
		for ( $i = 1; $i <= $seasons; $i++ ) {
			$toc .= '<li><a href="#season-'.$i.'">Season '.$i."</a></li>\n";
		}
		$toc .= "</ul>\n";
		$table = str_replace('{{TOC}}', $toc, $table);
		
		foreach ( $seasonRatings as $season => $r ) {
			if ( count($seasonRatings[$season]) > 0 )
				$seasonRating = $this->renderPercentageRating(
					array_sum($seasonRatings[$season])
					/count($seasonRatings[$season])
				);
			else
				$seasonRating = '-';
			$table = str_replace(
				array('{{SEASON-'.$season.'-RATING}}', '{{SEASON-'.$season.'-REVIEWS}}'),
				array($seasonRating, $seasonReviews[$season].' reviews'),
				$table
			);
		}
		
		if ( gfGetAuth()->isLoggedIn() ) {
			$table .= '<p>* Episodes/films you have all ready reviewed.</p>';
		}
		
		$this->content = $table;
		$this->title = 'Futurama reviews';
	
	}

	private function titleLinkRender($title, $season, $inseason,
			$type, $id, $no, $endNo, $episodesCheck) {
		$format = '{{NTH}} {{LINK}}';
		if ( count($episodesCheck) > 0
			&& isset($episodesCheck[$id]) ) {
			$format = '{{NTH}} * {{LINK}}';
		} elseif ( count($episodesCheck) > 0 ) {
			$format = '{{NTH}} <b>{{LINK}}</b>';
		} else {
			$format = '{{NTH}} {{LINK}}';
		}
		$link = '';
		if ( $type == 'f' ) {
			$link = '<a href="?p=episode&amp;film='.
				$season.'-'.(floor($inseason/4)+1).
				'"><i>'.$title.'</i></a>';
			$nth = $no . '-' . $endNo;
		} else {
			if ( $inseason < 10 )
				$inseason = '0'.$inseason;
			$id = $season.'ACV'.$inseason;
			$link = '<a href="?p=episode&amp;episode='.
				$id.'">'.$title.'</a>';
			$nth = $no;
		}
		return str_replace(
			array(
				'{{LINK}}',
				'{{NTH}}',
			),
			array(
				$link,
				$nth,
			),
			$format
		);
	}
	
}
