<?hh // strict
namespace Pando\MySQL;
use Pando\Database;
type MySQLIdentifier = shape('database' => string, 'table' => string, 'column' => string);
// MUST DISTINGUISH TRANSACTIONAL FROM NON-TRANSACTIONAL QUERIES, AND ANTICIPATE ROLLBACKS
class AsyncMySQL extends Database<string> {
	public ?\AsyncMySQLConnection $mysqli;
	protected bool $_in_transaction = false;
	const ?string GENERIC_CLASS = null;
	const ?string GENERAL_SELECTOR = '*';
	private static ImmSet<string> $isolation_levels = ImmSet{
				"READ REPEATABLE",
				"READ UNCOMMITTED",
				"READ COMMITTED",
				"SERIALIZABLE"
				};

	public function __construct(
		protected Credentials $credentials,
		public Parser $sql_parser,
		private \AsyncMysqlConnectionPool $connection_pool = new \AsyncMysqlConnectionPool([])) { // SQLParser?
	}
	
	// and so queryf was lost.
	private async function _get_connection(): Awaitable<\AsyncMysqlConnection> {
		if(is_null($this->mysqli))
			$mysqli = await $this->connection_pool->connect(
				$this->credentials['host'],
				$this->credentials['port'],
				$this->credentials['user'],
				$this->credentials['pass'],
				$this->credentials['database']
			);
		else
			$mysqli = $this->mysqli;
		
		return ($this->mysqli = $mysqli);
	}
	
	public async function query(string $sql): Awaitable<\AsyncMysqlQueryResult> {
		$this->_log_query($sql);
		
		$mysqli = await $this->_get_connection();
		return await $mysqli->query($sql); // throw the AsyncMySQLException to the calling scope
	}
	
	public async function multi_query(Traversable<string> $sql, int $timeout_micros = -1): Awaitable<\AsyncMysqlQueryResult> {
		$mysqli = await $this->_get_connection();
		return await $mysqli->multiQuery($sql, $timeout_micros); // throw the AsyncMySQLException to the calling scope
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
	
	public async function begin(string $isolation_level = null): Awaitable<bool> {
		/*
		(string) -> boolean
		Tries to start a transaction and, if one is already running, returns false.
		*/
		if($this->_in_transaction === true)
			return false;
		else {
			if(self::$isolation_levels->contains($isolation_level))
				await $this->_get_connection()->query("SET SESSION TRANSACTION ISOLATION LEVEL ".$isolation_level.";");
			//default to READ REPEATABLE if not found in isolation levels
			else
				await $this->_get_connection()->query("SET SESSION TRANSACTION ISOLATION LEVEL READ REPEATABLE;");
			
			await $this->_get_connection()->query('SET @@autocommit = 0;');
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