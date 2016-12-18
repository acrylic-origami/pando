<?hh
/*
	Dispatcher is a powerful class that builds an asynchronous dependency tree and permits routes that require them.
	
	Below shows the usage. A root dispatcher [or root dispatchers] is instantiated, and the dependency subtree is constructed through its constructor by nesting `Map`s, tuples and Dispatchers.
	
	There are two layers of asynchronicity. The dispatching is the first layer: it requires dependent dispatchers to return their dispatched values [into an FutureKeyedTree [henceforth aliased to FKT]] before itself dispatching a route. These routes are not yet resolved: the resulting dependency tree is empty. This is the second layer of asynchronicity: the root of this tree is returned by `await $root_dispatcher->dispatch()`, and this root can be `resolve`d to yield the Tv value, which executes _only_ the routes that are used in each routing function to create the final output. There are a myriad of advantages to this, namely:
	
	1. FKT is a distinct object: results that need to be reused between routes are memoized and can be encoded in the dependency tree by creating an intermediate variable holding this FKT, and passing it as a map value.
	2. The Map can be overpopulated (as far as dependencies are concerned) without a performance hit: this might be invaluable when creating general, reusable routes!
*/
$root_dispatcher = new Dispatcher(Map{
	'/' => tuple(new class extends FKT<string, string, void> {
		public async function _resolve(TResolver $arg): Awaitable<Tv> {
			
		}
	})
}); // unrolled syntax


// alias Dispatcher -> D
const type Route = FKT<string, string, void>;
function route(function(): Awaitable<string> $fn) {
	return new class extends Route {
		public async function _resolve(TResolver $arg): Awaitable<Tv> {
			return ($fn->bindTo($this))($arg); // mwahaha!
		}
	})
}
$root_dispatcher = new D(Map{
	'/' => tuple(route(async {
		return $renderer->render(Map{
			'foo' => await $this->get('foo'), // equivalent to await $this->subtree['foo']->resolve()
			'bar' => $hashtag_puller->pull_hashtags($this->get('bar_hashtags'));
		});
	}), new D(Map{
		'/[foo]' => tuple(route(async {
			return // cap somehow
		}));
	})) // fio how to cap this tree, probably with some nullable, but not sure on which level
});

echo await (await $root_dispatcher->dispatch(($method, $uri)))->resolve(); // this might be the most beautiful line of code I've ever written.