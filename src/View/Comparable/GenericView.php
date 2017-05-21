<?hh // strict
namespace Pando\View\Comparable;
use Pando\ComparableView;

trait GenericView<T as \Stringish, -TComparable as ComparableView<T, TComparable>, +TState as shape()> {
	require implements ComparableView<T, TComparable>;
	public function __construct(
		private (function(TComparable): bool) $comparer, // expect this to be a closure: this is how it compares against the previous state. When it's re-rendered, the state it closes over is the target of the comparison.
		private TState $state
		) {}
	public function get_state(): TState {
		return $this->state;
	}
	public function compare(TComparable $prev): bool {
		$comparer = $this->comparer;
		return $comparer($prev);
	}
}