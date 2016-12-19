<?hh // decl
namespace Pando\MySQL;
class MySQLCollection extends DatabaseCollection<MySQL> {
	public function connect(string $class, bool $_force_new = false): MySQL {
		try {
			$db = $this->configurations->traverse(["class_to_database", $class])->get();
		}
		catch(\UnexpectedValueException $e) {
			throw new \UnexpectedValueException($class . ' does not have database credentials setup yet. Link via config file.');
		}
		$creds_cfg = $this->configurations->traverse(['databases', $db])->values();
		$creds = shape( 'host' => $creds_cfg['host'], 'database' => $db, 'user' => $creds_cfg['user'], 'pass' => $creds_cfg['pass'] );
		try {
			$db_auth = shape('db' => $this->_connect($creds), 'credentials' => $creds);
		}
		catch(\mysqli_sql_exception $exception) {
			throw $exception;
		}

		if(!$this->units->containsKey($db)) {
			$this->units[$db] = Vector{ $db_auth };
			return $db_auth['db'];
		}
		elseif(!$_force_new && ($dbs = $this->units[$db]->filter(
				($db_auth) ==> ($db_auth['credentials'] == $creds))
			)->count() > 0) {
			return $dbs[0]['db']; // always return first connection; we could get picky later
		}
		else {
			$this->units[$db]->add($db_auth);
			return $db_auth['db'];
		}
	}
	protected function _connect(Credentials $creds): MySQL {
		return $this->db_factory->spawn($creds);
	}
}