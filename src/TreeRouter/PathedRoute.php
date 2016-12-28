<?hh // strict
namespace Pando\Route;
use \Pando\Dispatcher;
use \Pando\Util\Collection\KeyedContainerWrapper as KC;
abstract class PathedRoute<+Tv, +Tx as arraykey> extends Route<Awaitable<Tv>, Tx> {
	// <<__Override>>
	public function __construct(
		public string $path,
		private (function(KeyedContainer<Tx, Awaitable<Tv>>): Awaitable<Tv>) $_resolver,
		?KeyedContainer<Tx, Dispatcher<Awaitable<Tv>, Tx, this>> $dep) {
		// wrap resolvers that don't produce a "score" for the view, and and don't depend on them either. Untracked dependencies by definition conform to the former quality, but the latter is a tossup.
		parent::__construct(
			(KC<Tx, (Awaitable<Tv>, ?arraykey)> $dependencies) ==> 
				tuple(
					$_resolver($dependencies->map(
						((Awaitable<Tv>, ?arraykey) $v) ==> $v[0] // construct a KC of only Awaitable<Tv>s; neglect the score from dependencies
					)->get_units() ?? Map{}), 
					null // score for this dependency (to upper levels) is automatically null
				), 
		$dep);
	}
	// leave it to the user to send the score tree to Node
	// <<__Override>>
	// public function resolve(string $method, string $uri): Awaitable<Tv> {
	// 	$resolver = $this->_resolver;
	// 	return $resolver($this);
	// }
}