<?php

class Output {

	public static function Panel ( ) {
		if ( gfGetAuth()->isLoggedIn()) {
			return '<a href="/">Index</a> &middot; <a href="?p=faq">FAQ</a> &middot; <a href="?p=logout">Log out</a><br />
<span class="modes">Set mode: <a href="?p=setmode&amp;mode=reviewer">Reviewer</a> &middot; <a href="?p=setmode&amp;mode=noratings">No ratings</a> &middot; <a href="?p=setmode&amp;mode=default">Default</a></span>';
		} else {
			return '<a href="/">Index</a> &middot; <a href="?p=faq">FAQ</a> &middot; <a href="?p=login">Log in</a> &middot; <a href="?p=register">Register</a><br />
<span class="modes">Set mode: <a href="?p=setmode&amp;mode=noratings">No ratings</a> &middot; <a href="?p=setmode&amp;mode=default">Default</a></span>';
		}
	}

	public static function RenderPage( $page ) {
		$template = file_get_contents('includes/template.html');
		
		echo str_replace(
			array(
				'{{CONTENT}}',
				'{{TITLE}}',
				'{{PANEL}}'
			),
			array(
				$page->getContent(),
				$page->getTitle(),
				self::Panel(),
			),
			$template
		);
	}

}
