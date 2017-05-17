<?hh // strict
namespace Pando;

use Pando\State\IStateFactory;

use Facebook\HackRouter\{
	UriPattern,
	HttpMethod,
	HasUriPattern,
	RequestParameters,
	GetFastRoutePatternFromUriPattern
};

interface ComparableRoute<Tx as arraykey, Tv as \Stringish, -TState as State\State<Tx, Tv>, +TComparable as ComparableView<Tv, TComparable>> extends BaseRoute<Tx, Tv, TState> {
	<<__Override>>
	public function render(RequestParameters $params, string $path): Awaitable<TComparable>;
}