<?hh // strict
namespace Pando\Route;
use \Pando\Dispatcher;
abstract class PathedRoute<+Tv, +Tx as arraykey> extends Route<Tv, Tx> {
	// <<__Override>>
	public function __construct(
		public string $path,
		private (function(PathedRoute<Tv, Tx>): Awaitable<Tv>) $_resolver,
		?ImmMap<arraykey, Dispatcher<Tx, Tv, this>> $dep) {
		parent::__construct(async(this $v) ==> tuple(await $_resolver($v), null), $dep);
	}
	<<__Override>>
	public function _resolve(string $method, string $uri): Awaitable<Tv> {
		$resolver = $this->_resolver;
		return $resolver($this);
	}
}