<?hh // strict
namespace Shufflr\TreeRouter;
use \Shufflr\TreeRouter\Dispatcher;
abstract class Route<+Tv, +Tx as arraykey> extends \Shufflr\Tree\FutureKeyedTree<Tv, Tx> { // Tv corresponding to the view-type (e.g. XHP, string)
	public function __construct(
		private (function(this): Awaitable<(Tv, ?arraykey)>) $resolver, // top-level resolver creates both a view and a score - a number representing the view in some comparable domain
		?ImmMap<arraykey, Dispatcher<Tx, Tv, this>> $subtree) {
		parent::__construct($subtree);
	}
	public function _resolve(): Awaitable<(Tv, ?arraykey)> {
		$resolver = $this->resolver;
		return $resolver($this);
	}
}