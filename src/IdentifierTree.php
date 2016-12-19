<?hh // strict
namespace Pando;
// NOTE: becuase of the lavish use of "this", all children of this class must be final.
interface IdentifierTree {
	// public function union(this $incoming): this; // this should be taken care of by the Collection
	public function intersect(this $incoming): ?this;
	public function is_subset(this $incoming): bool;
	public function has_intersect(this $incoming): bool;
}