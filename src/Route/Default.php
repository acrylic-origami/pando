<?hh // strict
namespace Pando\Route;
use \Pando\Route;
use \Pando\Util\Collection\KeyedContainerWrapper as KC;
class Default<+Tv, Tx as arraykey> extends Route<Tv, Tx> {
	public function __construct(
		(function(KC<Tx, (Tv, ?arraykey)>): (Tv, ?arraykey)) $resolver, 
		ImmMap<Tx, \Pando\Dispatcher<Tv, Tx, this>> $dep = ImmMap{}
	) {
		parent::__construct('', $resolver, $dep);
	}
}