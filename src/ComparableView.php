<?hh // strict
namespace Pando;
// Require HHVM ^3.15 for cyclic constraint. See the hhvm source, commit ec7c01f4.
interface ComparableView<T as \Stringish, -TComparable as ComparableView<T, TComparable>> extends View<T> {
	public function compare(TComparable $prev): bool;
}