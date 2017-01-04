<?hh // strict
namespace Pando\Route;
use \Pando\Route;
use \Pando\Dispatcher;
class Any<+Tv, Tx as arraykey> extends Route<Tv, Tx> {}