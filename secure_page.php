<?php
require_once 'app/start.php';


//Only display content if someone is logged in
if (!$user->isLoggedIn()) {
	Redirect::to('index.php');
}


// Handle form input
if (Input::exists()) {
	if (Token::check(Input::get('token'))) {
		$errorHandler = new ErrorHandler();
		$validator = new Validator($errorHandler);

		$validation = $validator->check($_POST, [
			'field' => [
				// Parameters
			],
		]);

		if ($validation->passed()) {
			// Do something with the data
		} // Validation
	} // Token is valid
} // Input exists


$token = Token::generate();
?>
	<p>This page is secure. You are logged in as <?php echo $user->data()->username; ?></p>
</body>
</html>