<?php

class auth {

	private $verifiedLogins = array();
	private $loggedIn = false;
	private $reviewer = false;
	private $admin = false;
	private $mode = 'default';
	private $acceptedModes = array(
		'default', 'noratings', 'reviewer'
	);
	private $userData = array(
		'username' => '',
		'userid'   => 0,
	);
	
	function auth() {
		$this->loggedIn = $this->checkCookieLogin();
		$this->mode = $this->getSetMode();
	}
	
	function get_userdata($data) {
		if ( $this->isLoggedIn() )
			return $this->userData[$data];
		return null;
	}
	
	function getMode () {
		return $this->mode;
	}
	
	function isReviewer() {
		return $this->reviewer;
	}
	
	function isAdmin() {
		return $this->admin;
	}
	
	private function getSetMode() {
		$mode = $_COOKIE['reviews-mode'];
		
		if ( !in_array ( $mode, $this->acceptedModes ) ) {
			return 'default';
		}
		
		return $mode;
	}
	
	private function checkCookieLogin() {
		global $DB;
		
		$username = $_COOKIE['reviews-username'];
		$password = $_COOKIE['reviews-password'];
		
		if(!$username || !$password)
			return false;
		
		if(!$this->verifyLoginCombo($username, $password, true))
			return false;
		
		$DB->query("SELECT `username`, `id`, `reviewer`, `admin`
			FROM `users`
			WHERE LOWER(`username`) = '".strtolower($username)."'");
		
		$result = $DB->get_result();
		
		$this->userData['username'] = $result['username'];
		$this->userData['userid'] = $result['id'];
		$this->reviewer = ($result['reviewer']==1);
		$this->admin = ($result['admin']==1);
		
		return true;
	}
	
	function setMode($mode) {
		if ( !in_array($mode, $this->acceptedModes) ) {
			return;
		}
		
		setcookie('reviews-mode', $mode, time()+365*24*60*60);
	}
	
	function isLoggedIn() {
		return $this->loggedIn;
	}	

	function verifyLoginCombo($username, $password, $passwordIsMd5 = false) {
		global $DB;
		
		if ( !$passwordIsMd5 )
			$password = md5($password);
		
		if ( isset($this->verifiedLogins[$username][$password]) )
			return $this->verifiedLogins[$username][$password];
		
		$DB->query("SELECT `password` FROM `users` WHERE LOWER(`username`) = '".strtolower($username)."'");
		
		if ( $DB->get_num_rows() != 1 ) {
			$this->verifiedLogins[$username] = array($password => false);
			return false;
		}
		
		$result = $DB->get_result();
		
		if ( $password != $result['password'] ) {
			$this->verifiedLogins[$username] = array($password => false);
			return false;
		}
		
		$this->verifiedLogins[$username] = array($password => true);
		return true;
	}
	
	function performLogin($username, $password, $passwordIsMd5 = false) {
		if ( !$this->verifyLoginCombo($username, $password, $passwordIsMd5) )
			return false;
		
		if ( !$passwordIsMd5 )
			$password = md5($password);
		
		setcookie('reviews-username', $username, time()+365*24*60*60);
		setcookie('reviews-password', $password, time()+365*24*60*60);
		
		return true;
	}

}

$auth = new auth();
