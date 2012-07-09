<?php

if ( !defined('REVIEWS') )
	gfRedirect();

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
		
		gfRedirect();
	}
	
	private function createForm($errors = array(), $data = array()) {
		return gfRawMsg('<form method="post">
	<fieldset>
		<legend>Register a new account$1</legend>
		<label for="username">Desired username:$2</label>
		<input type="text" name="username" id="username"$3 />
		<label for="password1">Desired password:$4</label>
		<input type="password" name="password1" id="password1" />
		<label for="password2">Retype password:$5</label>
		<input type="password" name="password2" id="password2" /><!--
		<label for="email">E-mail:$6</label>
		<input type="text" name="email" id="email" />-->
		<label for="captcha">Write \'ye\' in the following input field to verify your humanity:$7</label>
		<input type="text" name="captcha" id="captcha"$8 />
		<input type="submit" name="submit-register" value="Register" />
	</fieldset>
</form>',
			$this->errorMsg($errors, 'system'),
			$this->errorMsg($errors, 'username'),
				$this->valueData($data, 'username'),
			$this->errorMsg($errors, 'password1'),
			$this->errorMsg($errors, 'password2'),
			$this->errorMsg($errors, 'email'),
			$this->errorMsg($errors, 'captcha'),
			$this->valueData($data, 'captcha')
		);
	}
	
}
