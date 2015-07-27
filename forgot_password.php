<?php
require_once 'app/start.php';

// Check if the email and token are provided
if (empty(trim(Input::get('email')))
	|| empty(trim(Input::get('recover_token')))) {
	Redirect::to('index.php');
}

// Check if the email and token are valid and if the request has not expired or been used already.
// First grab the user
$recoverUser = $db->get('users', ['email', '=', strtolower(Input::get('email'))])->first();

$now = date('Y-m-d H:i:s');
$sql = "SELECT *
	FROM `forgot_password`
	WHERE `user_id` = ?
		AND `token` = ?
		AND `date_expire` > ?
		AND `active` = true";
$recoverRequest = $db->query($sql, [$recoverUser->id, Input::get('recover_token'), $now]);

if (!$recoverRequest->count()) {
	Redirect::to('index.php');
}


// Process form submission
if (Input::exists()) {
	if (Token::check(Input::get('token'))) {
		$errorHandler = new ErrorHandler();
		$validator = new Validator($errorHandler);

		$rules = [
			'new_password' => [
				'containsletter'      => true,
				'containsnumber'      => true,
				'containsspecialchar' => true,
				'maxlength'           => 25,
				'minlength'           => 8
			],
			'password_again' => [
				'match' => 'new_password'
			]
		];

		$validation = $validator->check($_POST, $rules);

		if ($validation->passed()) {
			$fields = [
				'password' => Hash::create(Input::get('new_password'))
			];

			$recoverRequestId = $recoverRequest->first()->id;
			if($db->update('users', $recoverUser->id, $fields)
				&& $db->update('forgot_password', $recoverRequestId, ['active' => false])) {
				// Password update successful
				Session::flash('success', 'Successfully changed password.');
				
				Redirect::to('index.php');
			} else {
				Session::flash('error', 'There was a problem updating.');
			}
		} else {
			$message = '';
			foreach ($validation->errors()->all() as $field => $value) {
				$message .= $value[0] . '<br>';
			}
			
			Session::flash('error', $message);
		} // Validation check
	} // Token is valid
} // Input exists

$token = Token::generate();

require_once 'core/base.php';
include_once 'includes/flash.php';
?>

<img id="login-logo" src="images/westphal_logo.png">

<h1 class="login-title">Attendance App</h1>

<form id="login-form"
	enctype="multipart/form-data"
	method="POST"
	action="">
	<label for="new-password">Password</label>
	<input type="password" name="new_password" id="new-password">

	<label for="password-again">Repeat Password</label>
	<input type="password" name="password_again" id="password-again">

	<h6 class="heading">Password Requirements</h6>
	<ul>
		<li>Minimum of 8 characters</li>
		<li>Maximum of 25 characters</li>
		<li>Contains at least one special character: <strong>!#$%^&*()</strong></li>
		<li>Contains at least one letter and one number</li>
	</ul>

	<input type="hidden" name="token" value="<?php echo $token; ?>">

	<button type="submit" name="faculty_details_submit" class="btn right" form="login-form">
		Submit
	</button>
</form>

<script src="//use.typekit.net/gwn4yfg.js"></script>
<script>
	try{Typekit.load();}catch(e){}
</script>