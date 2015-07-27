<?php

/**
*
*/
class Database
{
	private static $_instance = null;
	private $_connection,
		$_query,
		$_error = false,
		$_results,
		$_count = 0,
		$_config;

	protected $stmt;
	protected $table;


	private function __construct() {
		try {
			$this->_config = new Config();

			$this->_connection = new PDO($this->_config->get('db.type') . ':host=' . $this->_config->get('db.host') . ';dbname=' . $this->_config->get('db.username'), $this->_config->get('db.username'), $this->_config->get('db.password'));
		} catch(PDOException $e) {
			die($e->getMessage());
		}
	}

	private function action($action, $table, $where = []) {
		if (count($where) === 3) {
			$operators = [
				'=',
				'!=',
				'<',
				'>',
				'>=',
				'<='
			];

			$field    = $where[0];
			$operator = $where[1];
			$value    = $where[2];

			if (in_array($operator, $operators)) {
				$sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
				
				if (!$this->query($sql, [$value])->error()) {
					return $this;
				}
			}
		}

		return false;
	}

	// Connect to DB only once
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new Database();
		}

		return self::$_instance;
	}

	public function table($table) {
		$this->table = $table;

		return $this;
	}

	public function exists($data) {
		$field = array_keys($data)[0];

		return $this->get($this->table, [$field, '=', $data[$field]])->count() ? true : false;
	}

	public function count() {
		return $this->_count;
	}

	public function query($sql, $params = []) {
		$this->_error = false;

		if ($this->_query = $this->_connection->prepare($sql)) {
			$n = 1;

			// Check if there are parameters
			if (count($params)) {
				foreach ($params as $param) {
					$this->_query->bindValue($n, $param);
					$n++;
				}
			}

			if ($this->_query->execute()) {
				$this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
				$this->_count = $this->_query->rowCount();
			} else {
				$this->_error = true;
			}
		}

		return $this;
	}

	public function get($table, $where) {
		return $this->action('SELECT *', $table, $where);
	}

	public function delete($table, $where) {
		return $this->action('DELETE ', $table, $where);
	}

	public function insert($table, $fields = []) {
		$keys = array_keys($fields);
		$values = '';
		$n = 1;

		foreach ($fields as $field) {
			$values .= '?';

			if ($n < count($fields)) {
				$values .= ', ';
			}

			$n++;
		}

		$sql = "INSERT INTO {$table} (`" . implode('`,`', $keys) . "`) VALUES ({$values})";
		
		if (!$this->query($sql, $fields)->error()) {
			return true;
		}

		return false;
	}

	public function update($table, $id, $fields) {
		$set = '';
		$n = 1;

		foreach ($fields as $name => $value) {
			$set .= "{$name} = ?";

			if ($n < count($fields)) {
				$set .= ', ';
			}

			$n++;
		}

		$sql = "UPDATE {$table} SET {$set} WHERE id = {$id}";

		if (!$this->query($sql, $fields)->error()) {
			return true;
		}

		return false;
	}

	public function results() {
		return $this->_results;
	}

	public function first() {
		return $this->results()[0];
	}

	public function error() {
		return $this->_error;
	}
}