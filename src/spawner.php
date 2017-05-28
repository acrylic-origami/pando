<?hh // strict
namespace Pando;
async function spawner<+T_ as Comparable<T_>, -TState as State>(
	(function(RequestParameters, TState): T_) $resolver,
	Route<TState> $context
): AsyncGenerator<mixed, ?\XHPRoot, SpawnerAction> {
	$staged_view = null;
	
	// first pass: always stash
	list($params, $request) = $context->get_request();
	$state = $state_factory->make($context, $path);
	$stashed_view = await $resolver($params, $state);
	$action = yield $stashed_view;
	
	while(true) {
		list($params, $request) = $context->get_request();
		$state = $state_factory->make($context, $path); // fresh state
		switch($action) {
			case null:
				// `next` call
				$emittable_view = await $resolver($params, $state);
				if($emittable_view->compare($stashed_view)) { // ALL THIS, FOR THIS CALL. ALL. FOR. THIS. CALL.
					$stashed_view = $emittable_view;
					
					// propagate commits down tree
					$hit_keys = $state->get_hit_keys();
					foreach($context->get_dependencies() as $k => $dependency)
						if($hit_keys->contains($k))
							await $dependency->send(ComparerAction::COMMIT);
					
					$action = yield $emittable_view;
				}
				else {
					$action = yield null;
				}
				break;
			case ComparerAction::STAGE:
				$staged_view = await $resolver($params, $state); // note overwriting here
				$action = yield $staged_view;
				break;
			case ComparerAction::COMMIT:
				invariant(!is_null($staged_view), 'Must send STAGE before COMMIT on spawner instance.');
				$stashed_view = $staged_view;
				$action = yield null;
				break;
		}
	}
}