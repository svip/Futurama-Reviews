<?php

if ( !defined('REVIEWS') )
	header('Location: ../');

class PageRegister extends Page {

	private $postErrors = array();
	private $postData = array();

	protected function render ( ) {
		if ( $_POST['submit-register'] ) {
			$this->handleRegisterPost();
		}
		$this->content = $this->createForm($this->postErrors, $this->postData);
		$this->title = 'Register';
	}
	
	private function hasPostErrors() {
		return (count($this->postErrors)>0);
	}
	
	private function handleRegisterPost() {
		$username = addslashes($_POST['username']);
		$password1 = $_POST['password1'];
		$password2 = $_POST['password2'];
		$captcha = $_POST['captcha'];
		
		$this->postData = array (
			'username' => $username,
			'captcha' => $captcha,
		);
		
		if ( !$username )
			$this->postErrors['username'] = 'Missing field.';
		
		if ( !$password1 )
			$this->postErrors['password1'] = 'Missing field.';
		
		if ( !$password2 )
			$this->postErrors['password2'] = 'Missing field.';
		
		if ( !$captcha )
			$this->postErrors['captcha'] = 'Missing field.';
		
		if ( $this->hasPostErrors() )
			return;
			
		if ( $captcha != 'ye' )
			$this->postErrors['captcha'] = 'Terrible human impression, robot.  Or should I say "beep beep beep beep"?';
		
		if ( $password1 != $password2 )
			$this->postErrors['password1'] = 'Passwords do not match.';
		
		if ( $this->hasPostErrors() )
			return;
		
		$i = gfDBQuery("SELECT `id` FROM `users` WHERE LOWER(`username`) = '".strtolower($username)."'");
		
		if ( gfDBGetNumRows($i) > 0 )
			$this->postErrors['username'] = 'Username already taken.';
		
		if ( $this->hasPostErrors() )
			return;
		
		$password = md5($password1);
		
		gfDBQuery("INSERT INTO `users`
				SET	`username` = '$username', `password` = '$password',
					`joindate` = ".time().", `reviewer` = 0, `reviewsdone` = 0");
		
		if ( !gfGetAuth()->verifyLoginCombo($username, $password, true) )
			$this->postErrors['system'] = 'An error occurred during registration.';
		
		if ( $this->hasPostErrors() )
			return;
		
		gfGetAuth()->performLogin($username, $password, true);
		
		header("Location: /");
	}
	
	private function createForm($errors = array(), $data = array()) {
		return '<form method="post">
	<fieldset>
		<legend>Register a new account'.$this->errorMsg($errors, 'system').'</legend>
		<label for="username">Desired username:'.$this->errorMsg($errors, 'username').'</label>
		<input type="text" name="username" id="username"'.$this->valueData($data, 'username').' />
		<label for="password1">Desired password:'.$this->errorMsg($errors, 'password1').'</label>
		<input type="password" name="password1" id="password1" />
		<label for="password2">Retype password:'.$this->errorMsg($errors, 'password2').'</label>
		<input type="password" name="password2" id="password2" /><!--
		<label for="email">E-mail:'.$this->errorMsg($errors, 'email').'</label>
		<input type="text" name="email" id="email" />-->
		<label for="captcha">Write \'ye\' in the following input field to verify your humanity:'.$this->errorMsg($errors, 'captcha').'</label>
		<input type="text" name="captcha" id="captcha"'.$this->valueData($data, 'captcha').' />
		<input type="submit" name="submit-register" value="Register" />
	</fieldset>
</form>';
		
	}
	
}
