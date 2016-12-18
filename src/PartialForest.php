<?hh // strict
namespace Shufflr;
type TreeNodeUnit = shape( 'obj' => Identifiable, 'level' => int );
type TreeNode = shape( 'unit' => TreeNodeUnit, 'descendants' => Vector<int>, 'focus' => bool, 'ancestor' => ?int );
abstract class PartialForest<T as TreeNode> extends VectorWrapper<T> {
	protected Set<int> $built = Set{};
	protected Set<int> $roots = Set{};
	protected Map<int, int> $reverse_map = Map{}; // maps identities to vector indices
	public function flatten_subtree(int $cursor): Vector<TreeNode> {
		$ret = Vector{};
		if($this->units->count() > $cursor) {
			foreach($this->units[$cursor]['descendants'] as $descendant) {
				$ret = $ret->concat($this->flatten_subtree($descendant));
			}
		}
		return $ret;
	}
	public function DFS(int $cursor, (function(TreeNode): void) $f): void {
		if($this->units->count() > $cursor) {
			$f($this->units[$cursor]);
			foreach($this->units[$cursor]['descendants'] as $descendant) {
				$this->DFS($descendant, $f); //DFS-style
			}
		}
	}
	public function bottom_fold(int $cursor, (function(Vector<mixed>, Vector<T>): mixed) $f): mixed {
		$args = Vector{};
		foreach($this->units[$cursor]['descendants'] as $descendant) {
			$args->add($this->bottom_fold($descendant, $f));
		}
		return $f($args, $this->units);
	}
	protected function _new_cursor(): int {
		return $this->units->count();
	}
}