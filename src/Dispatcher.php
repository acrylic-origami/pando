<?hh // strict
namespace Pando;
class Dispatcher<+Tv, +Tk as arraykey, TQuery, TDb as Database<TQuery>, +TRoute as Route<Tv, Tk, TQuery, TDb>> extends RootDispatcher<Tv, Tk, TQuery, TDb, TRoute> { // generic hell.
	public function __construct(Iterable<TRoute> $routes) {
		parent::__construct($routes);
	}
}