<?hh // strict
namespace Shufflr;
type MySQLIdentifier = shape('database' => string, 'table' => string, 'column' => string);
class MySQL extends Database<\Shufflr\Affected\MySQLIdentifierTree> {
	public ?\mysqli $mysqli = null;
	protected bool $_record_deltas = false;
	protected bool $_in_transaction = false;
	const ?string GENERIC_CLASS = null;
	const ?string GENERAL_SELECTOR = '*';
	private ImmVector<string> $isolation_levels = ImmVector{
				"READ REPEATABLE",
				"READ UNCOMMITTED",
				"READ COMMITTED",
				"SERIALIZABLE"
				};

	public function __construct(
		protected Credentials $credentials, 
		public ?Parser $sql_parser = null) {
		//don't ask, just initialize
		$this->mysqli = new \mysqli($credentials['host'], $credentials['user'], $credentials['pass'], $credentials['database']);
		if($this->mysqli->connect_error) {
			throw new \InvalidArgumentException('Unable to authenticate to MySQL server ('.$this->mysqli->errno.')');
		}
	}
	
	// hmm... how to fix this mixed? If mysqli had stronger class hierarchy, we could use generics and make this a lot stronger, but unfortunately the .hhi doesn't allow that
	// not to mention that mysqli_result is not in the std .hhi files. neh.
	protected function _query((function(string): mixed) $fn, string $sql, string $class = null): \mysqli_result {
		if($this->_record_deltas && $this->_in_transaction) {
			$parsed = $this->sql_parser->parse($sql);
			if(array_key_exists('CALL', $parsed)) throw new \UnexpectedValueException('Stored procedure CALLs are not allowed during query logging due to unknown side effects.');
			
			if(!is_string($class) && !is_null($class)) {
				$class = get_class($class);
			}
			if(class_exists($class)) {
				if($this->deltas->containsKey($class)) {
					$this->deltas[$class]->push($parsed);
				}
				else {
					$this->deltas[$class] = Vector{[new DatabaseDelta($parsed)]};
				}
			}
			elseif(!is_null($class)) {
				if(isset($this->deltas[self::GENERIC_CLASS])) {
					array_push($this->deltas[self::GENERIC_CLASS], $parsed);
				}
				else {
					$this->deltas[$class] = array($parsed);
				}
			}
			else {
				throw new \UnexpectedValueException('Cannot log queries for class '.$class.': does not exist.');
			}
		}
		return $fn($sql);
	}
	
	public function query(string $sql, string $class = null): mysqli_result {
		return $this->_query(fun($this->mysqli, 'query'), $sql, $class);
	}
	
	public function query_reduce<TInitial>(string $sql, (function(?TInitial, array<arraykey, mixed>): ?TInitial) $fn, ?TInitial $initial = null, int $query_flag = MYSQLI_ASSOC): ?TInitial {
		$results = $this->query($sql);
		while($fetch = $results->fetch_array($query_flag))
			$initial = $fn($initial, $fetch);
		return $initial;
	}
	
	public function multi_query(string $query): mysqli_result {
		return $this->_query(fun($this->mysqli, 'multi_query'), $sql, $class);
	}
	
	// public function get_affected_tables($classname = null, $deltas = null) {
	// 	if(is_null($deltas)) {
	// 		$deltas = $this->get_deltas($classname);
	// 	}
	// 	if($classname !== self::GENERAL_SELECTOR) {
	// 		$deltas = array($deltas);
	// 	}
	// 	foreach($deltas as $delta) {
	// 		reset($delta);
			
	// 	}
	// }
	
	public function begin($isolation_level = null, $_record_deltas = false): bool {
		/*
		(string) -> boolean
		Tries to start a transaction and, if one is already running, returns false.
		*/
		if($this->_in_transaction===true)
			return false;
		else {
			if(in_array(trim($isolation_level), $this->isolation_levels)) {
				$this->mysqli->query("SET SESSION TRANSACTION ISOLATION LEVEL ".trim($isolation_level).";");
			}
			//default to READ REPEATABLE if not found in isolation levels
			
			$this->mysqli->autocommit(false);
			$this->_in_transaction = true;
			return true;
		}
	}
	public function commit(): bool {
		/*
		(null) -> boolean
		Tries to commit a transation and, if one isn't running, returns false
		*/
		if($this->_in_transaction===false)
			return false;
		else {
			$this->mysqli->commit();
			$this->mysqli->autocommit(true);
			$this->_in_transaction = false;
			return true;
		}
	}
	public function rollback(): bool {
		/*
		(null) -> boolean
		Tries to commit a transation and, if one isn't running, returns false
		*/
		if($this->_in_transaction===false)
			return false;
		else {
			$this->mysqli->rollback();
			$this->mysqli->autocommit(true);
			$this->_in_transaction = false;
			return true;
		}
	}
	public function sanitize(?string $input): ?string {
		return is_null($input) 
			? null 
			: (is_numeric(trim($input)) ? fun("floatval") : fun($this->mysqli, "sanitize"))($input);
	}
	public function burn_multi_results(): void {
		$this->mysqli->store_result();
		while($this->mysqli->more_results() && $this->mysqli->next_result()) {
			$this->mysqli->store_result();
		}
	}
}