<?php

function gfGetPageName ( ) {
	return isset($_GET['p'])?$_GET['p']:'index';
}

function gfMsg ( ) {
	global $messages, $backupmessages;
	$args = func_get_args();
	if ( !isset($messages[$args[0]]) ) {
		if ( !isset($backupmessages[$args[0]]) ) {
			return "&lt;{$args[0]}&gt;";
		}
		$msg = $backupmessages[$args[0]];
	} else {
		$msg = $messages[$args[0]];
	}
	foreach ( $args as $i => $arg ) {
		if ( $i == 0 ) continue;
		while ( preg_match('@\$'.$i.'([^0-9]|$)@s', $msg) )
			$msg = preg_replace( '@\$'.$i.'([^0-9]|$)@s', "$arg$1", $msg );
	}
	return $msg;
}

function gfRawMsg ( ) {
	$args = func_get_args();
	$msg = $args[0];
	foreach ( $args as $i => $arg ) {
		if ( $i == 0 ) continue;
		while ( preg_match('@\$'.$i.'([^0-9]|$)@s', $msg) )
			$msg = preg_replace( '@\$'.$i.'([^0-9]|$)@s', "$arg$1", $msg );
	}
	return $msg;
}

/**
 * Generate a relative link.  All parameter are optional.
 *
 * @param $page The page
 * @param $subpages An array for the query.
 * @param $bookmark The HTML bookmark.
 * @param $raw Whether to render & as & or false for &amp;
 * @return Link.
 */
function gfLink ( $page=null, $subpages=null, $bookmark=null,
		$raw=false ) {
	//global $UsePathInfo, $SiteLocation;
	global $SiteLocation;
	
	$query = '';
	
	if ( is_null($page) ) {
		$link = '';
	} else {
		$link = 'p='.$page;
		if ( !is_null($subpages) ) {
			foreach ( $subpages as $var => $value ) {
				if ( $query != '' ) $query .= '&amp;';
				$query .= "$var=$value";
			}
		}
	}
	
	if ( isset($_GET['uselang']) ) {
		if ( $query != '' ) $query .= '&amp;';
		$query .= "uselang={$_GET['uselang']}";
	}
	if ( $query != '' )
		$link = "?$link&amp;$query";
	elseif ( $link != '' )
		$link = "?$link";
	
	if ( $raw )
		$link = str_replace('&amp;', '&', $link);
	
	return preg_replace('@\/+@i', '/', $SiteLocation.$link).(!is_null($bookmark)?'#'.$bookmark:'');
}

/**
 * Generate a full link.  All parameter are optional.
 *
 * @param $page The page
 * @param $baselink The baselink (e.g. /admin/)
 * @param $subpages An array for the query.
 * @param $bookmark The HTML bookmark.
 * @param $raw Whether to render & as & or false for &amp;
 * @return Link.
 */
function gfFullLink ( $page=null, $subpages=null, $bookmark=null,
		$raw=false ) {
	global $SiteDomain;
	
	$link = gfLink($page, $baselink, $subpages, $bookmark, $raw);
	
	return gfRawMsg('http://$1$2', $SiteDomain, $link);
}

function gfRedirect ( $url=null ) {
	if ( is_null($url) )
		$url = gfLink();
	$url = preg_replace('/Location\: ?/i', '', $url);
	$url = str_replace('&amp;', '&', $url);
	header ( 'Location: ' . $url );
}

function gfLinkRaw ( $subpages=null, $bookmark=null ) {
	return str_replace ( '&amp;', '&', gfLink ( $subpages, $bookmark ) );
}

function gfTestFlag ( $flag, $flags ) {
	return ($flag & $flags) == $flag;
}

function gfGetAuth ( ) {
	return Authentication::get();
}

function gfValueSanitise ( $string ) {
	return str_replace(
		array('&', '"', "'"),
		array('&amp;', '&quot;', '&#39;'),
		$string
	);
}

function gfGetDB ( ) {
	global $DB, $DbHost, $DbName, $DbUser, $DbPass;
	if ( is_null($DB) )
		$DB = new Database($DbHost, $DbName, $DbUser, $DbPass);
	return $DB;
}

function gfDBQuery ( $query, $split=false ) {
	global $DatabaseQuery, $DatabasePrefix;
	$DB = gfGetDB();
	$i = $DatabaseQuery;
	/*
	// only add the prefix to table names, table names *never*
	// has underscores in them, while fieldnames *always* has
	// underscores in them.
	$query = preg_replace('@`([a-z]+?)`@is', "`$DatabasePrefix\\1`", $query);
	*/
	if ( $split ) {
		$queries = explode(";--end\n", $query);
		foreach ( $queries as $subquery ) {
			if ( trim($subquery) === '')
				continue;
			if ( $DB->query ( $subquery, $i ) === false )
				return false;
		}
	} else {
		if ( $DB->query ( $query, $i ) === false )
			return false;
	}
	$DatabaseQuery++;
	return $i;
}

/**
 * Sanitise a variable for DB queries.
 *
 * @param $variable To be sanitised.
 * @param $shouldBeNumeric Whether the variable *must* be numeric, returns 0
 *                         if it is set and the variable is not numeric.
 * @return Sanitised variable, safe to insert in a DB query.
 */
function gfDBSanitise ( $variable, $shouldBeNumeric=false ) {
	if ( is_numeric ( $variable ) ) {
		return $variable;
	} elseif ( $shouldBeNumeric ) {
		return 0;
	}
	while ( $variable != stripslashes($variable) )
		$variable = stripslashes($variable);
	return gfGetDB()->escape_string(trim($variable));
}

function gfDBGetResult ( $i ) {
	global $DB;
	return $DB->get_result($i);
}

function gfDBGetNumRows ( $i ) {
	global $DB;
	return $DB->get_num_rows($i);
}

function gfDBGetInsertId ( $i ) {
	global $DB;
	return $DB->get_insert_id($i);
}
