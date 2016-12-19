<?hh // strict
namespace Pando\TreeRouter;
use \Pando\TreeRouter\Dispatcher;
abstract class Route<+Tv, +Tx as arraykey> extends \Pando\Tree\AbstractFutureKeyedTree<(Tv, ?arraykey), Tx> { // Tv corresponding to the view-type (e.g. XHP, string)
	public function __construct(
		(function(this): Awaitable<(Tv, ?arraykey)>) $resolver, // top-level resolver creates both a view and a score - a number representing the view in some comparable domain
		?ImmMap<Tx, Dispatcher<Tx, Tv, this>> $subtree) {
		parent::__construct($resolver, $subtree);
	}
	
	// Allow Pando\TreeRouter\Dispatcher to store and deal with the resolver.
	// public function _resolve(): Awaitable<(Tv, ?arraykey)> {
	// 	$resolver = $this->resolver;
	// 	return $resolver($this);
	// }
}