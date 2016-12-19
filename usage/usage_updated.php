<?hh
use Pando\Tree\AbstractFutureKeyedTree;
use Pando\Tree\Route\Tracked;
use Pando\Tree\Route\GET;
use Pando\Tree\Route\POST;
use Pando\Tree\Route\PUT;
use Pando\Tree\Route\HEAD;
type XHP_FKT = Pando\Tree\AbstractFutureKeyedTree<string, \XHPRoot>;
require_once(__DIR__ . '/bootstrap.php');
$root_dispatcher = new XHPDispatcher(
	new GET('/blog', async(XHP_FKT $deps) {
		$template = $templater->load_template('/blog.twig');
		$params = await HH\Asio\m($deps->get_forest());
		$params['time'] = date('Y-M-j H:i:s');
		return $template->render($params);
	}, Map{
		'reputation_placard' => new XHPDispatcher(
			new Tracked\GET('/blog/201(5|6)', async(XHP_FKT $deps) { 
				$reputations = $reputation_collection_factory->repute();
				$total_rep = $reputations->reduce((num $rep, num $next) ==> $rep + $next, 0.0);
				$template = $templater->load_template('reputation_placard');
				$params = HH\Asio\m($deps->get_forest());
				$params['total_rep'] = $total_rep;
				return tuple($template->render($params), $total_rep);
			}, (?arraykey $old, ?arraykey $new) ==> {
				return intval($new) - intval($old) > $thresholder->get('reputation');
			}),
			new GET('/blog/201[0-4]/.*', I::XHP('This is old content. Please go [here](/some_internal_link.php) instead.'))
		),
		'route_level_dependency' => new XHPDispatcher(
			new GET('/blog/.*', async(XHP_FKT $deps) { ... })
		)
	}),
	Map{
		'route_level_dependency' => new XHPDispatcher(
			new GET('/.*', I::XHP('Welcome to the default behaviour.'))
		),
		'another_route_level_dependency' => new XHPDispatcher( ... )
	}
);

fputs('php://stdout', HH\Asio\join($root_dispatcher->render($method, $uri)));