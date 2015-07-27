<?php

/**
* A helper class that can help reduce overhead.
* If the password hashing/verifying method changes,
*	it will only need to be changed here.
*/
class Hash
{
	public static function create($suppliedPassword) {
		return password_hash($suppliedPassword, PASSWORD_DEFAULT);
	}

	public static function check($suppliedPassword, $hash) {
		return password_verify($suppliedPassword, $hash);
	}

	public static function needsRehash($hash) {
		return password_needs_rehash($hash, PASSWORD_DEFAULT);
	}

	public static function make($string, $salt = '') {
		return hash('sha256', $string . $salt);
	}

	public static function unique() {
		return self::make(uniqid());
	}
}