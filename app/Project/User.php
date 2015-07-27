<?php

/**
* 
*/
class User
{
	private $_db,
		$_data,
		$_sessionName,
		$_cookieName,
		$_isLoggedIn,
		$_config,
		$_errors;


	function __construct($user = null) {
		$this->_config = new Config();

		$this->_db = Database::getInstance();
		$this->_sessionName = $this->_config->get('session.session_name');
		$this->_cookieName = $this->_config->get('remember.cookie_name');

		$this->_errors = '';

		if (!$user) {
			if (Session::exists($this->_sessionName)) {
				$user = Session::get($this->_sessionName);

				if ($this->find($user)) {
					$this->_isLoggedIn = true;
				} else {
					$this->_isLoggedIn = false;
					$this->logout();
				}
			}
		} else {
			$this->find($user);
		}
	}

	public function update($fields = [], $id = null) {
		if (!$id && $this->isLoggedIn()) {
			$id = $this->data()->id;
		}

		if (!$this->_db->update('users', $id, $fields)) {
			throw new Exception('There was a problem updating.');
		}
	}

	public function create($fields = []) {
		if (!$this->_db->insert('users', $fields)) {
			throw new Exception('There was a problem creating an account.');
		}
	}

	public function find($user = null) {
		if ($user) {
			if (is_numeric($user)) {
				$field = 'id';
			} else if (filter_var($user, FILTER_VALIDATE_EMAIL)) {
				$field = 'email';
			} else {
				$field = 'username';
			}
			$data = $this->_db->get('users', [$field, '=', $user]);

			if ($data->count()) {
				$this->_data = $data->first();

				return true;
			}
		}

		return false;
	}

	public function login($username = null, $password = null, $remember = false) {
		if (!$username && !$password && $this->exists()) {
			Session::put($this->_sessionName, $this->data()->id);
		} else {
			$user = $this->find(strtolower($username));
		
			if ($user) {
				// Check that the user is both active and the password is correct
				if ($this->data()->active && Hash::check($password, $this->data()->password)) {
					Session::put($this->_sessionName, $this->data()->id);

					// Create a 'Remember Me' cookie if the checkbox was checked
					if ($remember === true) {
						$hash = Hash::unique();
						$hashCheck = $this->_db->get('users_session', ['user_id', '=', $this->data()->id]);

						if (!$hashCheck->count()) {
							$this->_db->insert('users_session', [
								'user_id' => $this->data()->id,
								'token' => $hash
							]);
						} else {
							$hash = $hashCheck->first()->hash;
						}

						Cookie::put($this->_cookieName, $hash, $this->_config->get('remember.cookie_expiry'));
					}

					return true;
				} else if (!$this->data()->active) {
					$this->_errors = 'Sorry, this account is deactivated.';
				} else if (!Hash::check($password, $this->data()->password)) {
					$this->_errors = 'Sorry, the provided password was incorrect.';
				}
			}
		}

		return false;
	}

	public function recordLoginAttempt($success) {
		// Set userId to 0 if no user exists
		// This is to at least record the IP address for brute-force attacks
		$userId = (isset($this->data()->id)) ? $this->data()->id : 0;

		$this->_db->insert('login_attempts', [
			'user_id'    => $userId,
			'time'       => date('Y-m-d H:i:s'),
			'ip_address' => filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP),
			'successful' => $success
		]);
	}

	public function hasPermission($key) {
		$group = $this->_db->get('groups', ['id', '=', $this->data()->permissions_group]);

		if ($group->count()) {
			$permissions = json_decode($group->first()->permissions, true);

			if ($permissions[$key] == true) {
				return true;
			}
		}

		return false;
	}

	public function exists() {
		return (!empty($this->_data)) ? true : false;
	}

	public function logout() {
		$this->_db->delete('users_session', ['user_id', '=', $this->data()->id]);

		Session::delete($this->_sessionName);
		Cookie::delete($this->_cookieName);
	}

	public function data() {
		return $this->_data;
	}

	public function isLoggedIn() {
		return $this->_isLoggedIn;
	}

	public function getLoginErrors() {
		return $this->_errors;
	}
}