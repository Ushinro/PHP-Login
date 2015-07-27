<?php

/**
* Rule => Value
* 'alphabetic'          => true
* 'alphanumeric'        => true
* 'containsletter'      => true
* 'containsnumber'      => true
* 'containsspecialchar' => true
* 'email'               => true
* 'lowercase'           => true
* 'match'               => '[field_name]'
* 'matchvalue'          => [Value]
* 'maxlength'           => [Integer]
* 'minlength'           => [Integer]
* 'numeric'             => true
* 'required'            => true
* 'unique'              => '[table_name]'
* 'uppercase'           => true
*/
class Validator
{
	protected $db;
	protected $errorHandler;
	protected $items;
	protected $rules = [
		'alphabetic',
		'alphanumeric',
		'containsletter',
		'containsnumber',
		'containsspecialchar',
		'email',
		'lowercase',
		'match',
		'matchvalue',
		'maxlength',
		'minlength',
		'numeric',
		'required',
		'unique',
		'uppercase'
	];

	public $messages = [
		'alphabetic'          => 'The :field field must only contain letters.',
		'alphanumeric'        => 'The :field field must be only contain letters and numbers.',
		'containsletter'      => 'The :field field must contain at least one letter.',
		'containsnumber'      => 'The :field field must contain at least one number.',
		'containsspecialchar' => 'The :field field must contain at least one special character <b>!#$%^&*()</b>',
		'email'               => 'That is not a valid email address.',
		'lowercase'           => 'The :field must only contain lowercase letters.',
		'match'               => 'The :field field must match the :satisfier field.',
		'matchvalue'          => 'The :field field must match :satisfier.',
		'maxlength'           => 'The :field field must be a maximum of :satisfier length.',
		'minlength'           => 'The :field field must be a minimum of :satisfier length.',
		'numeric'             => 'The :field field must only contain numbers.',
		'required'            => 'The :field field is required.',
		'unique'              => 'That :field already exists.',
		'uppercase'           => 'The :field field must only contain uppercase letters.'
	];


	public function __construct(ErrorHandler $errorHandler) {
		$this->errorHandler = $errorHandler;
		$this->db = Database::getInstance();
	}

	public function check($items, $rules) {
		$this->items = $items;

		foreach ($items as $item => $value) {
			if (in_array($item, array_keys($rules))) {
				$this->validate([
					'field'	=> $item,
					'value'	=> $value,
					'rules'	=> $rules[$item]
				]);
			}
		}

		return $this;
	}

	public function passed() {
		return !$this->errorHandler->hasErrors();
	}

	public function errors() {
		return $this->errorHandler;
	}


	protected function validate($item) {
		$field = $item['field'];

		foreach ($item['rules'] as $rule => $satisfier) {
			if (in_array($rule, $this->rules)) {
				if (!call_user_func_array([$this, $rule], [$field, $item['value'], $satisfier])) {
					// There is an error
					$this->errorHandler->addError(
						str_replace(
							[':field', ':satisfier'],
							[$field, $satisfier],
							$this->messages[$rule]
						),
						$field
					);
				}
			}
		}
	}

	protected function alphabetic($field, $value, $satisfier) {
		return ctype_alpha($value);
	}

	protected function alphanumeric($field, $value, $satisfier) {
		return ctype_alnum($value);
	}

	protected function containsletter($field, $value, $satisfier) {
		return preg_match('/[a-zA-Z]+/', $value);
	}

	protected function containsnumber($field, $value, $satisfier) {
		return preg_match('/[0-9]+/', $value);
	}

	protected function containsspecialchar($field, $value, $satisfier) {
		return preg_match('/[!#$%^&*()]+/', $value);
	}

	protected function email($field, $value, $satisfier) {
		return filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	protected function lowercase($field, $value, $satisfier) {
		return ctype_lower($value);
	}

	protected function match($field, $value, $satisfier) {
		return $value === $this->items[$satisfier];
	}

	protected function matchvalue($field, $value, $satisfier) {
		return $value === $satisfier;
	}

	protected function maxlength($field, $value, $satisfier) {
		return mb_strlen($value) <= $satisfier;
	}

	protected function minlength($field, $value, $satisfier) {
		return mb_strlen($value) >= $satisfier;
	}

	protected function numeric($field, $value, $satisfier) {
		return ctype_digit($value);
	}

	protected function required($field, $value, $satisfier) {
		return !empty(trim($value));
	}

	protected function unique($field, $value, $satisfier) {
		return !$this->db->table($satisfier)->exists([
			$field => $value
		]);
	}

	protected function uppercase($field, $value, $astisfier) {
		return ctype_upper($value);
	}
}