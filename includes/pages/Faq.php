<?php

if ( !defined('REVIEWS') )
	gfRedirect();

class PageFaq extends Page {

	private $questions = array(
		"What's this?" => 'A <i>Futurama</i> review site.',
		'Cool, what else you got?' => "That's it.",
		'Seriously?' => 'Yes.  By the way, if you are looking for places to discuss <i>Futurama</i>, there already exist very decent places for this matter, so there is no reason to duplicate them.',
		"Right... but doesn't 'review sites' already exists for <i>Futurama</i>?" => "Yes, it is true that <a href=\"http://gotfuturama.com\">Can't Get Enough Futurama</a> have a review feature, but we feel that this lacks a sophisticated system to encourage better reviews, both in writing and in rating.  Our attempt is to do this better.",
		'Okay, I get it.  So how does it work?' => 'Our reviewers can pick any episode/film and begin writing a review.  However, only one review per episode per reviewer.  Each review include a number of ratings, each are present on their right side.',
		'Cool cool, how do I become a reviewer?' => "You can register an account easily, however to become an actual reviewer, you will have to contact 'Svip' on <a href=\"http://peelified.com\">PEEL</a> with an example review and your username on here.",
		"I saw this review, and I thought it wasn't very good.  Should it remain?" => "You can rate it if you have an account.  Any account can rate.",
		'Can you tell me about the modes on each page?' => "Certainly.  There is currently 3 modes available; <b>Reviewer</b>, <b>No ratings</b> and <b>Default</b>.  <b>Default</b> is, as its name implies, the default viewing mode; full ratings and full reviews.  <b>No ratings</b> removes all numeral ratings from reviews and overview and only focuses on the written content of reviews.  Some people may find the numbers distraction for someone's opinion on an episode. <b>Reviewer</b> is a mode only accessible to reviewers, in this mode, only the reviews of the reviewer appears.  This is to avoid the context of other reviews and only review the episode from one's own standpoint.",
		'What are the approval and disapproval of reviews?' => "Other users can disapprove or approve of another's review.  However, it is <em>not meant</em> to be agree and disagree with the review, it is to indicate that one think it is a well-written review or not.  If the reviewer updates his/her textual review, the approval/disapproval count will be reset.",
	);
	
	protected function render ( ) {
		$content = "<dl class=\"faq\">\n";
		
		foreach( $this->questions as $question => $answer ) {
			$content .= "<dt>$question</dt>\n<dd>$answer</dd>\n";
		}
		
		$content .= "</dl>\n";
		
		$this->content = $content;
		
		$this->title = 'Frequently asked questions';
	}

}
