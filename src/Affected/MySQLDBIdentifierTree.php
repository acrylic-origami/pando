<?hh // strict
namespace Pando\Affected;
use \Pando\IdentifierTree;
final class MySQLDBIdentifierTree extends \Pando\Util\Collection\KeyedIterableWrapper<string, Set<string>, Map<string, Set<string>>> implements IdentifierTree {
	const string CATCHALL = '*';
	/* theoretically I could get really fancy and define an abstract class of { bool catch_all, [T as Collection] $collection, ?[T as Collection] $ambiguous } for every level, then chain them together somehow in the calling class with something like `compose('RelationalDatabaseStructure', 'RelationalTableStructure')`;
		RelationableDatabaseStructure<T as Collection> extends Structure<T>
		RelationableTableStructure<T as Collection> extends Structure<T>
		
		StructureBuilder::chain = function(...$args) {
			...
		}
		*/
	
	public function __construct(
		public string $db,
		// tables are in $this->get_units() ,
		Map<string, Set<string>> $units = Map{},
		public Set<string> $ambiguous = Set{},
		public bool $db_caughtall = false // if $db->*
		) {
		parent::__construct($units);
	}
	
	// Interface functions
	
	public function intersect(this $incoming): ?this {
		if($incoming->db !== $this->db)
			return null;
		
		$ambiguous = $this->ambiguous->filter($ambiguous_col ==> $incoming->get_units()->contains($ambiguous_col));
		$units = $this->get_units()
			->mapWithKey(
				(string $table_name, Set<string> $table) ==> $table->filter(
					(string $col) ==> $incoming->get_units()->containsKey($table_name) && $incoming->get_units()[$table_name]->contains($col)
					)
				) // column intersection
			->filter($unit ==> $unit->count() > 0); // filter empty keys
			
		if($ambiguous->count() === 0 && $units->count() === 0)
			return null;
		
		return new self($this->db, $units, $ambiguous);
	}
	
	public function is_subset(this $incoming): bool {
		if($incoming->db !== $this->db)
			return false;
		
		return 
				(((mixed $next_col) ==> !$this->get_units()[$next_table[0]]->contains($next_col))
							|> ((bool $accumulator, Pair<string, Set<string>> $next_table) ==> $accumulator && $this->get_units()->containsKey($next_table[0]) && $next_table->filter($$)->count() === 0) // which elements are contained in incoming $units that aren't contained in this's $units?
							|> $incoming->keyed_reduce($$, true)); // reducing over Pairs<table names [string], Set<column names [string]>> 
	}
	
	public function has_intersect(this $incoming): bool {
		if($incoming->db !== $this->db)
			return false;
		
		return $incoming->keyed_reduce(
			(bool $prev_table, Pair<string, Set<string>> $next_table) ==> $prev_table || $this->get_units()->containsKey($next_table[0]) && ($next_table[1]->contains(self::CATCHALL) || $next_table[1]->filter(
				(mixed $next_col) ==> $this->get_units()[$next_table[0]]->contains($next_col)
				)
				->count() > 0) // which elements are contained in both this $units and the incoming $units?
			, false);
	}
	
	public function add(?string $table = null, ?string $col = null, ?string $db = null): void {
		if(is_null($table)) {
			// database is caught-all
			$this->get_units()->clear();
			$this->ambiguous->clear();
			$this->db_caughtall = true;
		}
		else {
			if(!$this->get_units()->containsKey($table)) {
				$this->get_units()[$table] = Set{};
			}
			
			if($col === self::CATCHALL) {
				$this->get_units()[$table]->clear();
				$this->get_units()[$table]->add(self::CATCHALL);
			}
			elseif(!is_null($col)) {
				$this->get_units()[$table]->add($col);
			}
		}
	}
	public function add_ambiguous(string $col, ?string $db = null): void {
		$this->ambiguous->add($col);
	}
	public function concat(this $incoming): void {
		if($incoming->db_caughtall)
			$this->add(); // catch-all this DB too
		else {
			$this->ambiguous->addAll($incoming->ambiguous->toVector());
			$this->get_units()->addAll($incoming->get_units()->items());
		}
	}
	
	// public function has((function(Affected<MySQL> $affecteds): bool) $f): bool {
	// 	// implemented via trait in calling class? 
	// 	return $f($this);
	// }
}