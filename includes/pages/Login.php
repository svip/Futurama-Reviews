<?php

if ( !defined('REVIEWS') )
	header('Location: ../');
	
class PageLogin extends Page {

	protected function render ( ) {
		if ( $_POST['submit-login'] ) {
			$this->handleLogin();
		} else {
			$this->loginForm();
		}
		$this->title = 'Futurama reviews login';
	}
	
	private function handleLogin() {
		$errors = array();
		
		$username = addslashes($_POST['username']);
		$password = addslashes($_POST['password']);
		
		if ( !$username )
			$errors['username'] = 'Field missing.';
		if ( !$password )
			$errors['password'] = 'Field missing.';
		
		if ( count($errors) > 0 ) {
			$this->loginForm($errors);
			return;
		}
		
		if ( gfGetAuth()->verifyLoginCombo($username, $password) ) {
			gfGetAuth()->performLogin($username, $password);
			header('Location: /');
			return;
		} else {
			$this->loginForm(array('username' => 'Username/password combination does not exist.'));
			return;
		}
	}
	
	private function loginForm($errors = array()) {
		$this->content = '<form method="post">
	<fieldset>
		<legend>Login</legend>
		<label for="username">Username:'.$this->errorMsg($errors, 'username').'</label>
		<input type="text" name="username" id="username" />
		<label for="password">Password:'.$this->errorMsg($errors, 'password').'</label>
		<input type="password" name="password" id="password" />
		<input type="submit" name="submit-login" value="Log in" />
	</fieldset>
</form>';
	}
}
