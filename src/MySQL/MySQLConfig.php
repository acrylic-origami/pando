<?hh // decl
namespace Pando\MySQL;
class MySQLConfig extends DatabaseConfig {
	public $configurations;
	public bool $_override_force = false;
	protected Vector<Credentials> $cache_credentials = Vector{};
	protected Vector<Database> $cache_connections = array()
	public function __construct(
		protected SQLParserCache $sql_parser,
		public bool $_override_force = false) {}
	public function connect(string $class, $_force_new = false): Database {
		if(!isset($this->configurations['databases'][$this->configurations['class_to_database'][$class]])) throw new \OutOfRangeException('Configuration does not exist for class '.$class.'.');
		$host = $this->configurations['databases'][$this->configurations['class_to_database'][$class]]['host'];
		$user = $this->configurations['databases'][$this->configurations['class_to_database'][$class]]['user'];
		$pass = $this->configurations['databases'][$this->configurations['class_to_database'][$class]]['pass'];
		$db = $this->configurations['class_to_database'][$class];
		if(!$this->_override_force && !$_force_new && !empty($this->cache_credentials)) {
			$credentials = array($host, $user, $pass, $db);
			foreach($this->cache_credentials as $key=>$c) {
				if($c == $credentials) {
					return $this->cache_connections[$key];
				}
			}
		}
		$dbc = new Database($host, $user, $pass, $db, $this->sql_parser);
		array_push($this->cache_credentials, $credentials);
		array_push($this->cache_connections, $dbc);
		return $dbc;
	}
	public function clear_all_deltas() {
		//meh, it's not really just config anymore, is it?
		foreach($this->cache_connections as $connection) {
			$connection->clear_deltas();
		}
	}
	public function get_all_deltas() {
		$ret = array();
		foreach($this->cache_connections as $connection) {
			array_push($ret, $connection->get_deltas());
		}
		return $ret;
	}
}