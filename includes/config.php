<?php

return [
	'db'	=> [
		'host'     => '127.0.0.1',
		'name'     => '',
		'username' => '',
		'password' => '',
		'type'     => 'mysql'
	],
	'mail'	=> [
		'host'      => '',
		'port'      => 465,
		'secure'    => 'ssl',
		'username'  => '',
		'password'  => '',
		'from'      => '',
		'from_name' => '',
		'reply_to'  => ''
	],
	'remember' => [
		'cookie_name'   => 'login_cookie',
		'cookie_expiry' => 604800	// 7 days, in seconds
	],
	'session' => [
		'session_name' => 'login_user',
		'token_name'   => 'session_token'
	]
];