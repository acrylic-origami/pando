<?hh // strict
namespace Pando\Route;
use \Pando\{
	Route,
	State\State,
	ComparableView
};
use Facebook\HackRouter\HttpMethod;
class GET<Tx as arraykey, Tv as \Stringish, -TState as State<Tx, Tv>, +TComparable as ComparableView<Tv, TComparable>> extends Route<Tx, Tv, TState, TComparable> {
	public static function get_method(): HttpMethod {
		return HttpMethod::GET;
	}
}