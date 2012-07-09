<?php

if ( !defined('REVIEWS') )
	gfRedirect();

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
				$table .= gfRawMsg('		<tr class="season" id="season-$1">
			<th>Season $1</th><th>{{SEASON-$1-RATING}}</th><th>{{SEASON-$1-REVIEWS}}</th>
		</tr>
',
					$result['season']
				);
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
			$table .= gfRawMsg('		<tr>
			<td>$1</td><td>$2</td><td>$3</td>
		</tr>
',
				$this->titleLinkRender($result['title'],
					$result['season'], $result['inseason'],
					$result['type'], $result['id'], $n, $endN,
					$episodesCheck),
				(($reviews==0 || $rating==null)
					?'-'
					:$this->renderPercentageRating($rating)),
				gfRawMsg('$1 review$2',
					$reviews,
					($reviews!=1?'s':'')
				)
			);
			
			if ( $result['type'] == 'f' )
				$n += 3;
			if ( $reviews > 0 && $rating != null ) {
				$seasonRatings[$result['season']][] = $rating;
				$totalRatings[] = $rating;
			}
			$seasonReviews[$result['season']] += $reviews;
			$totalReviews += $reviews;
		}
		$table .= gfRawMsg('		<tr class="season">
			<th>Total</th><th>$1</th><th>$2 reviews</th>
		</tr>
	</tbody>
</table>',
			(count($totalRatings) == 0
				?'-':$this->renderPercentageRating(
					array_sum($totalRatings)
					/count($totalRatings))
			),
			$totalReviews
		);

		$toc = "<ul>\n";
		for ( $i = 1; $i <= $seasons; $i++ ) {
			$toc .= gfRawMsg('<li><a href="#season-$1">Season $1</a></li>
', $i);
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
				array(
					'{{SEASON-'.$season.'-RATING}}',
					'{{SEASON-'.$season.'-REVIEWS}}'
				),
				array(
					$seasonRating, 
					$seasonReviews[$season].' reviews'),
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
		$format = '$2 $1';
		if ( count($episodesCheck) > 0
			&& isset($episodesCheck[$id]) ) {
			$format = '$2 * $1';
		} elseif ( count($episodesCheck) > 0 ) {
			$format = '$2 <b>$1</b>';
		} else {
			$format = '$2 $1';
		}
		$link = '';
		if ( $type == 'f' ) {
			$link = gfRawMsg('<a href="$1"><i>$2</i></a>',
				gfLink('episode', array('film'=>
					$this->filmCode($season, $inseason))),
				$title
			);
			$nth = $no . '-' . $endNo;
		} else {
			$link = gfRawMsg('<a href="$1">$2</a>',
				gfLink('episode', array('episode'=>
					$this->prodCode($season, $inseason))),
				$title
			);
			$nth = $no;
		}
		return gfRawMsg($format, $link, $nth);
	}
	
}
