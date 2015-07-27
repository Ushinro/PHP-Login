<?php

/**
* 
*/
class Token
{
	private $_config;


	public static function generate() {
		$_config = new Config();

		return Session::put($_config->get('session.token_name'), md5(uniqid()));
	}

	public static function check($token) {
		$_config = new Config();
		
		$tokenName = $_config->get('session.token_name');

		if (Session::exists($tokenName) && $token === Session::get($tokenName)) {
			Session::delete($tokenName);

			return true;
		}

		return false;
	}
}