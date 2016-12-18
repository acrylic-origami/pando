<?hh // strict
namespace Shufflr\Affected;
<<__ConsistentConstruct>>
class IdentifierTreeBooleanStatement<T as \Shufflr\IdentifierTree> {
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