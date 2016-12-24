<?hh // strict

// WE ARE ONLY USING THIS CLASS WHILE WE WAIT FOR ANONYMOUS CLASSES. 

// Here's the lowdown: Closure::bind isn't a recognized interface method 
// (understandable so... though depending on the typing of the Closure object, 
// you might be able to glean something? idk) which means that this isn't
// type-safe, i.e. where I expect the lambda to return int, it could [provably]
// always return string, and I would be none the wiser.

// Anonymous classes would rectify this with constraints on the return parameter
// of _resolve.

namespace Pando\Tree;
class AbstractFutureKeyedTree<+Tv, -Tx as arraykey> extends FutureKeyedTree<Tv, Tx> {
	<<__Override>>
	public function __construct(
		?ConstMap<Tx, Awaitable<this>> $subtree,
		private (function(this): Awaitable<Tv>) $_resolver,
		?Tv $v = null
		) {
		parent::__construct($subtree, $v);
	}
	public function _resolve(): Awaitable<Tv> {
		//  HH_FIXME[4090]: Could not find static method bind in type Closure
		// Trust me, Closure::bind definitely exists [well, now it does]. See #1203.
		
		// return (\Closure::bind($this->_resolve, $this))();
		
		// Alternatively...
		$resolver = $this->_resolver;
		return $resolver($this); // can't access protected methods, but eh. When I need that, they'll probably have anonymous classes. [Right? Plz? :(]
	}
}