<?php 
$errorHandler = new ErrorHandler();

if (Input::exists()) {
	if (Token::check(Input::get('token'))) {
		$validator = new Validator($errorHandler);

		if (isset($_POST['login_submit'])) {
			$validation = $validator->check($_POST, [
				'username' => [
					'required' => true
				],
				'password' => [
					'required' => true
				]
			]);

			if ($validation->passed()) {
				$remember = (Input::get('remember') === 'on') ? true : false;
				$loginSuccessful = $user->login(Input::get('username'), Input::get('password'), $remember);

				if ($loginSuccessful) {
					if (Hash::needsRehash($user->data()->password)) {
						$user->update([
							'password' => Hash::create(Input::get('password'))
						], $user->data()->id);
					}

					Redirect::to('secure_page.php');
				} else {
					Session::flash('error', $user->getLoginErrors());
				}
			} else {
				$message = '';
				foreach ($validation->errors()->all() as $field => $value) {
					$message .= $value[0] . '<br>';
				}
				
				Session::flash('error', $message);
			}
		} else if (isset($_POST['reset_password_submit'])) {
			$validation = $validator->check($_POST, [
				'email' => [
					'required' => true,
					'email'    => true
				]
			]);

			if ($validation->passed()) {
				// Check if email is registered to a faculty member
				if ($db->get('users', ['email', '=', strtolower(Input::get('email'))])->count()) {
					// First check if the user had already requested a reset
					$recoverUser = $db->get('users', ['email', '=', strtolower(Input::get('email'))])->first();

					$resetSql = 'SELECT *
						FROM `forgot_password`
						WHERE `user_id` = ?
							AND `active` = 1';
					$resetRequest = $db->query($resetSql, [$recoverUser->id]);

					// Send an email
					if ($resetRequest->count()) {
						$resetPasswordToken = $resetRequest->first()->token;
						// A request exists, now see if it had expired
						if (date('Y-m-d H:i:s', strtotime($resetRequest->first()->date_expire)) < date('Y-m-d H:i:s')) {
							
							// Deactivate the request because it is expired
							$db->update('forgot_password', $resetRequest->first()->id, ['active' => false]);

							// Create a new request
							// Token gets reassigned if the current request has expired
							$resetPasswordToken = Hash::unique();
							$db->insert('forgot_password', [
								'user_id'        => $recoverUser->id,
								'token'          => $resetPasswordToken,
								'date_requested' => date('Y-m-d H:i:s'),
								'date_expire'    => date('Y-m-d H:i:s', strtotime('+1 day')),
								'active'         => true
							]);
						}
					} else {
						$resetPasswordToken = Hash::unique();

						$db->insert('forgot_password', [
							'user_id'        => $recoverUser->id,
							'token'          => $resetPasswordToken,
							'date_requested' => date('Y-m-d H:i:s'),
							'date_expire'    => date('Y-m-d H:i:s', strtotime('+1 day')),
							'active'         => true
						]);
					}
					
					$recoverUserEmail = $recoverUser->email;

					// Send the email whether or not the request exists
					// in case the user had accidentally deleted it or can't find it
					$mail->send('app/views/email/password.php', ['email' => $recoverUserEmail, 'resetToken' => $resetPasswordToken], function($m) {
						$db = Database::getInstance();
						$recoverUser = $db->get('users', ['email', '=', strtolower(Input::get('email'))])->first();
						$recoverUserEmail = $recoverUser->email;

						$m->to($recoverUserEmail);
						$m->subject('Login - Forgot Password');
					});
				}

				// Provide a message regardless of whether or not it was a valid request.
				// This reduces the amount of information given through brute-force attacks.
				Session::flash('success', 'A request for password reset for ' . Input::get('email') . ' has been received.');
			} else {
				$message = '';
				foreach ($validation->errors()->all() as $field => $value) {
					$message .= $value[0] . '<br>';
				}
				
				Session::flash('error', $message);
			}
		}
	}
}

if ($user->isLoggedIn()) {
	// No need to stay on the login page if the user is logged in
	Redirect::to('secure_page.php');
}

$token = Token::generate();


include_once 'app/views/flash.php';
?>


<?php include VIEW_ROOT . '/templates/header.php'; ?>

<h1 class="login-title">Login Module</h1>


<form action="" method="POST" name="login-form" id="login-form">
	<label id="username-label" for="username">Username / Email</label>
	<input id="username"
		type="username"
		autofocus
		required
		placeholder="Username / Email"
		title="Username / Email"
		name="username"
		value="<?php echo e(Input::get('username')); ?>">

	<label id="password-label" for="password">Password</label>
	<input id="password"
		type="password"
		required
		placeholder="Password"
		title="Password"
		name="password">

	<input id="remember" class="styled-checkbox" type="checkbox" name="remember">
	<label id="remember-label" class="styled-checkbox-label" for="remember">Remember Me</label>

	<input type="hidden" name="token" value="<?php echo $token; ?>">

	<button class="btn"
		form="login-form"
		type="submit"
		name="login_submit">
		Login
	</button>
</form>

<form action="" method="POST" name="reset-pw-form" id="reset-pw-form">
	<p>Enter your email address:</p>
	<input id="recover-form-email" type="email" name="email" placeholder="Email" required>

	<input type="hidden" name="token" value="<?php echo $token; ?>">

	<button class="btn" type="submit" name="reset_submit">Reset</button>
</form>

<?php include VIEW_ROOT . '/templates/footer.php'; ?>