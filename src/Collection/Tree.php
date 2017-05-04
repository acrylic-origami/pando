<?hh // strict
namespace Pando\Collection;
interface Tree<Tk as arraykey, +Tv> {
	public function get_subtree(Tk $k): ?this;
	public function get_v(): ?Tv;
	public function get_forest(): KeyedIterable<Tk, this>;
}