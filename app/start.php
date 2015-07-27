<?php
session_start();

ini_set('display_errors', 'On');	// Show errors for this app and not globally

define('APP_ROOT', __DIR__);
define('VIEW_ROOT', APP_ROOT . '/views');
define('BASE_URL', 'http://127.0.0.1/PHP-Login');



spl_autoload_register(function ($class) {
	require_once 'app/Project/' . str_replace('\\', '/', $class) . '.php';
});


require_once 'functions.php';
require_once 'vendor/PHPMailer/PHPMailerAutoload.php';


$config = new Config();
$db = Database::getInstance();

// User logged out but asked to be remembered
if (Cookie::exists($config->get('remember.cookie_name'))
	&& !Session::exists($config->get('session.session_name'))) {
	// User asked to be remember
	$cookieToken = Cookie::get($config->get('remember.cookie_name'));
	$tokenCheck = Database::getInstance()->get('users_session', ['token', '=', $cookieToken]);

	if ($tokenCheck->count()) {
		$user = new User($tokenCheck->first()->user_id);
		$user->login();
	}
}

// Must be placed here for 'Remember Me' to work
$user = new User();

// Email configuration
$mailer = new PHPMailer();

$mailer->isSMTP();
$mailer->SMTPAuth = true;
// $mailer->SMTPDebug = 1;

$mailer->Host       = $config->get('mail.host');
$mailer->Port       = $config->get('mail.port');
$mailer->SMTPSecure = $config->get('mail.secure');
$mailer->Username   = $config->get('mail.username');
$mailer->Password   = $config->get('mail.password');

$mailer->From     = $config->get('mail.from');
$mailer->FromName = $config->get('mail.from_name');
$mailer->addReplyTo($config->get('mail.reply_to'), $config->get('mail.from_name'));
$mailer->isHTML(true);

$mail = new Mail\Mailer($mailer);