<?hh // strict
namespace Pando\Affected;
use \Pando\Affected;
use \Pando\Util\Collection\KeyedIterableWrapper;
final class MySQLIdentifierTree extends KeyedIterableWrapper<string, MySQLDBIdentifierTree, Map<string, MySQLDBIdentifierTree>> {
	const string CATCHALL = MySQLDBIdentifierTree::CATCHALL;
	public function __construct(
		public string $default_db,
		Map<string, MySQLDBIdentifierTree> $units = Map{},
		public Set<string> $SEAs = Set{} // side-effect accumulators
		) {
		parent::__construct($units);
	}
	
	//Interface/abstract methods
	
	public function union(this $incoming): void {
		foreach($this->get_units() as $db=>$db_identifier) {
			if($incoming->get_units()->containsKey($db))
				$db_identifier->concat($incoming->get_units()[$db]);
		}
		$this->get_units()->addAll($incoming->get_units()->differenceByKey($this->get_units())->items());
	}
	
	public function intersect(this $incoming): ?this {
		return new self($this->default_db, $incoming->keyed_reduce(
			(Map<string, MySQLDBIdentifierTree> $prev, Pair<string, MySQLDBIdentifierTree> $db) ==> {
				if($this->get_units()->containsKey($db[1])) {
					$intersection = $this->get_units()[$db[0]]->intersect($db[1]);
					if(!is_null($intersection)) {
						$prev[$db[0]] = $intersection;
					}
				}
				return $prev;
			}
			, Map{}));
	}
	
	public function has_intersect(this $incoming): bool {
		return $incoming->keyed_reduce(
			(bool $prev_db, Pair<string, MySQLDBIdentifierTree> $next_db) ==> $prev_db || $this->get_units()->containsKey($next_db[0]) && $this->get_units()[$next_db[0]]->has_intersect($next_db[1])
			, false);
	}
	
	public function is_subset(this $incoming): bool {
		return $incoming->keyed_reduce(
			(bool $prev_db, Pair<string, MySQLDBIdentifierTree> $next_db) ==> $prev_db && $this->get_units()->containsKey($next_db[0]) && $next_db[1]->is_subset($this->get_units()[$next_db[0]])
			, true);
	}
	
	public function add_ambiguous(string $col, ?string $db = null): void {
		$db_resolved = $db ?? $this->default_db;
		if(!$this->get_units()->containsKey($db_resolved)) {
			$this->get_units()[$db_resolved] = new MySQLDBIdentifierTree($db_resolved);
		}
		$this->get_units()[$db_resolved]->add_ambiguous($col, $db);
	}
	public function add(?string $table = null, ?string $col = null, ?string $db = null): void {
		$db_resolved = $db ?? $this->default_db;
		if(!$this->get_units()->containsKey($db ?? $this->default_db)) {
			$this->get_units()[$db_resolved] = new MySQLDBIdentifierTree($db_resolved);
		}
		$this->get_units()[$db_resolved]->add($table, $col, $db);
	}
}