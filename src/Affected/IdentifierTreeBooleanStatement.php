<?hh // strict
namespace Pando\Affected;
<<__ConsistentConstruct>>
class IdentifierTreeBooleanStatement<T as \Pando\IdentifierTree> {
	public function __construct(
		protected (function(T): bool) $statement = ((T $v) ==> true)
	) {}
	public function _or(T $incoming) : this {
		$statement = $this->statement;
		return new static((T $v) ==> $incoming->has_intersect($v) && $statement($v));
	}
	public function _and(T $incoming) : this {
		$statement = $this->statement;
		return new static((T $v) ==> $v->is_subset($incoming) && $statement($v));
	}
	public function execute(T $source) : bool {
		$statement = $this->statement;
		return $statement($source);
	}
}