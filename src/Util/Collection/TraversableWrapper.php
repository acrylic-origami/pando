<?hh // strict
namespace Pando\Util\Collection;
abstract class TraversableWrapper<+Tv, +TCollection as Traversable<Tv>> {
	public function __construct(
		private ?TCollection $units
		) {}
	public function get_units(): ?TCollection {
		return $this->units;
	}
}