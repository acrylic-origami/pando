<?hh // strict
namespace Pando;
interface DatabaseDeltaTransformer<+TDelta as DatabaseDelta, -TFrom as Iterable<TDelta>, +TTo> {
	public function transform(TFrom $from): TTo;
}