<?php

abstract class Page {
	protected $content;
	protected $title;
	protected $ratingColours = array(
		0 => '#ff0000',
		1 => '#e60e00',
		2 => '#e21700',
		3 => '#d32b00',
		4 => '#ca6500',
		5 => '#c49c00',
		6 => '#648900',
		7 => '#46a800',
		8 => '#00dd00',
		9 => '#00dd00'
	);
	
	public function __construct ( ) {
		$this->render();
	}
	
	abstract protected function render();
	
	function getContent() {
		return $this->content;
	}
	
	function getTitle() {
		return $this->title;
	}
	
	protected function timeStamp($time) {
		return date("j F Y", $time);
	}
	
	protected function renderPercentageRating($rating) {
		if ( $rating >= 0 ) {
			$colour = $this->ratingColours[0];
			$bold = true;
		} 
		if ( $rating > 10 ) {
			$colour = $this->ratingColours[1];
			$bold = true;
		}
		$bold = false;
		if ( $rating > 20 )
			$colour = $this->ratingColours[2];
		if ( $rating > 30 )
			$colour = $this->ratingColours[3];
		if ( $rating > 40 )
			$colour = $this->ratingColours[4];
		if ( $rating > 50 )
			$colour = $this->ratingColours[5];
		if ( $rating > 60 )
			$colour = $this->ratingColours[6];
		if ( $rating > 70 )
			$colour = $this->ratingColours[7];
		if ( $rating > 80 )
			$colour = $this->ratingColours[8];
		if ( $rating > 90 ) {
			$colour = $this->ratingColours[9];
			$bold = true;
		}
		return gfRawMsg('<span style="color:$1;$2">$3%</span>',
			$colour,
			($bold?' font-weight:bold;':''),
			round($rating,0)
		);
	}
	
	protected function renderRating($rating) {
		if ( $rating == -1 )
			return 'No opinion/I don\'t know.';
		if ( $rating >= 0 ) {
			$colour = $this->ratingColours[0];
			$bold = true;
		} 
		if ( $rating > 1 ) {
			$colour = $this->ratingColours[1];
			$bold = true;
		}
		if ( $rating > 2 )
			$colour = $this->ratingColours[2];
		$bold = false;
		if ( $rating > 3 )
			$colour = $this->ratingColours[3];
		if ( $rating > 4 )
			$colour = $this->ratingColours[4];
		if ( $rating > 5 )
			$colour = $this->ratingColours[5];
		if ( $rating > 6 )
			$colour = $this->ratingColours[6];
		if ( $rating > 7 )
			$colour = $this->ratingColours[7];
		if ( $rating > 8 )
			$colour = $this->ratingColours[8];
		if ( $rating > 9 ) {
			$colour = $this->ratingColours[9];
			$bold = true;
		}
		return gfRawMsg('<span style="color:$1;$2">$3</span>',
			$colour,
			($bold?' font-weight:bold;':''),
			$rating
		);
	}
	
	protected function errorMsg($errors, $msg) {
		if ( isset($errors[$msg]) )
			return gfRawMsg(' <span class="error">$1</span>',
				$errors[$msg]
			);
		return '';
	}
	
	protected function valueData($dataArray, $id) {
		$data = null;
		if ( isset ( $dataArray[$id] ) )
			$data = stripslashes($dataArray[$id]);
		if ( $data != null )
			return ' value="'.$data.'"';
		return '';
	}
	
	protected function postData($dataArray, $id) {
		if ( isset ( $dataArray[$id] ) )
			return stripslashes($dataArray[$id]);
		if ( isset($_POST[$id]) )
			return stripslashes($_POST[$id]);
		return '';
	}
	
	protected function radioChecked($dataArray, $id, $checkAgainst,
			$isDefault=false) {
		$data = $this->postData($dataArray, $id);
		if ( $data == '' ) {
			if ( $isDefault )
				return ' checked="true"';
			else
				return '';
		}
		if ( $data == $checkAgainst )
			return ' checked="true"';
		else
			return '';
	}
	
	protected function reviewForm($errors = array(), $data = array(),
			$mode = 'create') {
		$submitType = 'submit-review';
		$legend = 'Create a review';
		if ( $mode == 'edit' ) {
			$submitType = 'submit-editreview';
			$legend = 'Edit a review';
		}
		return gfRawMsg('<form method="post">
	<fieldset class="review">
		<legend>$1</legend>
		<fieldset>
			<legend>The review</legend>
			<label for="content">Textual:$2</label>
			<textarea cols="50" rows="15" name="content" id="content">$3</textarea>
			<input type="submit" name="$4" value="Submit" />
		</fieldset>
		<fieldset class="ratings">
			<legend>Ratings</legend>
			<div class="ratingcontainer">
			<div>
			<label for="rating-overall-1">Overall:$5</label>
			$6
			</div>
			$7
			</div>
		</fieldset>
	</fieldset>
</form>',
			$legend,
			$this->errorMsg($errors, 'content'),
			$this->postData($data, 'content'),
			$submitType,
			$this->errorMsg($errors, 'rating-overall'),
			$this->radio('rating-overall', 1, 10, null, $data),
			$this->otherFormRatings($errors, $data)
		);
	}
	
	protected function radio ( $id, $start, $end, $nullText, 
			$data=array() ) {
		$div = '<div class="radiobuttons">';
		for ( $i=$start; $i<=$end; $i++ ) {
			$div .= gfRawMsg('<input type="radio" name="$1" id="$1-$2" value="$2"$3 /> <label for="$1-$2">$2</label> ',
				$id, $i,
				$this->radioChecked($data, $id, $i)
			);
			if ( $end/2 == $i )
				$div .= '<br />';
		}
		$div .= gfRawMsg('<br /><input type="radio" name="$1" id="$1-false" value="false"$2 /> <label for="$1-false">$3</label>',
			$id,
			$this->radioChecked($data, $id, 'false',
				($nullText == null)),
			"No opinion/don't know"
		);
		if ( $nullText != null ) {
			$div .= gfRawMsg('<hr /><input type="radio" name="$1" id="$1-null" value="null"$2 /> <label for="$1-null">$3</label>',
				$id,
				$this->radioChecked($data, $id, 'null',
					($nullText != null)),
				$nullText
			);
		}
		$div .= '</div>';
		return $div;
	}
	
	protected function otherFormRatings ( $errors = array(),
			$data = array() ) {
		$i = gfDBQuery("SELECT * FROM `ratingtypes`");
		
		$content = '';
		$types = '';
		
		while ( $result = gfDBGetResult($i) ) {
			$content .= gfRawMsg('			<div>
				<label for="ratingtype-$1-1">$2:$3</label>
				$4
				</div>
',
				$result['id'], $result['name'],
				$this->errorMsg($errors, 'ratingtype-'.$result['id']),
				$this->radio('ratingtype-'.$result['id'], 1, 10,
					$result['nullstring'], $data)
			);
			$types .= '|'.$result['id'];
		}
		$content .= gfRawMsg('<input type="hidden" name="typesofratings" value="$1" />',
			$types
		);
		
		return $content;
	}
	
	protected function reviewEditLink($id, $userid) {
		if ( gfGetAuth()->get_userdata('userid') != $userid )
			return '';
		
		return gfRawMsg('
	<p class="editlink"><a href="$1">Edit your review</a></p>',
			gfLink('editreview', array('id'=>$id))
		);
	}
	
	protected function renderContent($content) {
		$content = stripslashes($content);
		
		return '<p>'.preg_replace("@\n.?\n@is", "</p>\n<p>", $content).'</p>';
	}
	
	protected function prodCode ( $season, $inseason ) {
		if ( $inseason < 10 )
			$inseason = '0'.$inseason;
		return $season.'ACV'.$inseason;
	}
	
	protected function filmCode ( $season, $inseason ) {
		return $season.'-'.(floor($inseason/4)+1);
	}
}
