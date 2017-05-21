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

interface BaseRoute<Tx as arraykey, Tv as \Stringish, -TState as State\State<Tx, Tv>> {
	public static function get_method(): HttpMethod;
	public function getUriPattern(): UriPattern;
	public function getFastRoutePattern(): string;
	public function get_dependencies(): \ConstMap<Tx, Dispatcher<Tx, Tv, TState>>;
	public function render(RequestParameters $params, string $path): Awaitable<View<Tv>>;
	public function rerender_and_compare(RequestParameters $params, string $path): Awaitable<?View<Tv>>;
}