<?hh // strict
namespace Pando;
interface IView<+T as \Stringish> {
	public function compare(this $prev): bool;
	public function get_view(): T;
}