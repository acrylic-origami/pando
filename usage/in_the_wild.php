<?hh
namespace Shufflr;
use TreeRouter\Route;
use TreeRouter\Route\Default;
use TreeRouter\Route\GET;
use TreeRouter\Route\POST;
use TreeRouter\Route\HEAD;
use TreeRouter\Route\PUT;
use TreeRouter\Dispatcher as D;
use TreeRouter\Route\TrivialDispatcher as I;
$root_dispatcher = new D(
	new GET('/{page:|user|about|meta|reputation}', async(AbstractFKT<arraykey, \HH\XHPRoot> $that) ==> {
		return <section>
			{ await $that->get('foo') }
			You are on page: { await $that->get('bar') }
		</section>;
	}, ImmMap{
		'foo' => new I(async(AbstractFKT<arraykey, \HH\XHPRoot> $that) ==> {
				return <ul><li>Hello &rarr; {await $that->get['what_world'] }</li></ul>;
			}, ImmMap{
				'what_world' => new I(async () ==> 'Mars')
			}),
		'bar' => new D(
			new GET('/user/{id:\d+}', async(AbstractFKT<arraykey, \HH\XHPRoot> $that) ==> {
				return <x:primitive>$that->get('username')</x:primitive>;
			}, ImmMap{
				'username' => new I(async {
					$mysqli = $db_collection->connect('shufflr_users');
					$mysqli->query('SELECT username FROM '); // ...
				})
			}),
			new Default(async {
				return <x:primitive>Never mind. 404.</x:primitive>;
			}))
	})
);