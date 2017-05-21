<?hh // strict
namespace Pando\Route;
use \Pando\{
	BaseRoute,
	State\State,
	ComparableView
};
use Facebook\HackRouter\HttpMethod;
trait GET<Tx as arraykey, Tv as \Stringish, -TState as State<Tx, Tv>> {
	require implements BaseRoute<Tx, Tv, TState>;
	public static function get_method(): HttpMethod {
		return HttpMethod::GET;
	}
}