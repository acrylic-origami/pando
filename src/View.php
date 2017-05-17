<?hh // strict
namespace Pando;
interface View<+T as \Stringish> {
	public function get_view(): T;
}