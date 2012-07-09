<?php

class Output {

	public static function CreatePanel ( ) {
		$menu = array (
			array('Index', gfLink()),
			array('FAQ', gfLink('faq'))
		);		
		if ( gfGetAuth()->isLoggedIn()) {
			$menu[] = array('Log out', gfLink('logout'));
			$modes = array(
				array('Reviewer',
					gfLink('setmode', array('mode'=>'reviewer'))),
				array('No ratings',
					gfLink('setmode', array('mode'=>'noratings'))),
				array('Default',
					gfLink('setmode', array('mode'=>'default')))
			);
		} else {
			$menu[] = array('Log in', gfLink('login'));
			$menu[] = array('Register', gfLink('register'));
			$modes = array(
				array('No ratings',
					gfLink('setmode', array('mode'=>'noratings'))),
				array('Default',
					gfLink('setmode', array('mode'=>'default')))
			);
		}
		$tmp = '';
		foreach ( $menu as $item ) {
			if ( $tmp != '' )
				$tmp .= ' &middot; ';
			$tmp .= gfRawMsg('<a href="$1">$2</a>',
				$item[1], $item[0]
			);
		}
		$span = '';
		foreach ( $modes as $item ) {
			if ( $span != '' )
				$span .= ' &middot; ';
			$span .= gfRawMsg('<a href="$1">$2</a>',
				$item[1], $item[0]
			);
		}
		return gfRawMsg('$1<br />
<span class="modes">Set mode: $2</span>',
			$tmp, $span
		);
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
				self::CreatePanel(),
			),
			$template
		);
	}

}
