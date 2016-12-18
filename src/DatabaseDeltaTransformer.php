<?hh // strict
namespace Shufflr;
interface DatabaseDeltaTransformer<+TDelta as DatabaseDelta, -TFrom as Iterable<TDelta>, +TTo> {
	public function transform(TFrom $from): TTo;
}