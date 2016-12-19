<?hh // strict
namespace Pando\Affected;
use \Pando\IdentifierTreeBuilder;
use \Pando\ParsedQueryCache;
type SQLIdentifier = shape('db' => string, 'table' => string, 'col' => string);
final class MySQLIdentifierTreeBuilder implements IdentifierTreeBuilder {
	const string CATCHALL = MySQLIdentifierTree::CATCHALL;
	public function __construct(
		protected ParsedQueryCache $parser,
		public string $db,
		public ?string $table = null,
		public ?string $col = null
		) {}
	public function scaffold_from_identifier(SQLIdentifier $identifier): MySQLIdentifierTreeBuilder {
		return $this->scaffold($identifier['col'], $identifier['table'], $identifier['db']);
	}
	public function scaffold_from_string(string $identifier): MySQLIdentifierTreeBuilder {
		list($db, $table, $col) = $this->parser->identifier($identifier);
		return $this->scaffold($db, $table, $col);
	}
	public function scaffold(?string $col = null, ?string $table = null, ?string $db = null): this {
		return new self($this->parser, $db ?? $this->db, $table ?? $this->table, $col ?? $this->col);
	}
	
	public function build_from_identifier(SQLIdentifier $identifier): MySQLIdentifierTree {
		return $this->build($identifier['col'], $identifier['table'], $identifier['db']);
	}
	public function build_from_identifiers(Vector<SQLIdentifier> $identifiers): Vector<MySQLIdentifierTree> {
		$identifier_wrapper = new \Pando\Util\Collection\VectorWrapper($identifiers);
		return $identifier_wrapper->reduce(
			(MySQLIdentifierTree $prev, SQLIdentifier $identifier) ==> $prev->union($this->build_from_identifier($identifier))
			, new MySQLIdentifierTree($this->db));
	}
	public function build_from_string(?string $identifier): MySQLIdentifierTree {
		if(is_null($identifier))
			return $this->build();
		
		list($db, $table, $col) = $this->parser->identifier($identifier);
		return $this->build($db, $table, $col);
	}
	
	public function build(?string $col = null, ?string $table = null, ?string $db = null): MySQLIdentifierTree {
		$identifier = new MySQLIdentifierTree($this->db);
		$identifier->add($table ?? $this->table, $col ?? $this->col, $db ?? $this->db);
		return $identifier;
	}
}