<?hh // strict
use Facebook\HackRouter\HttpMethod;
class PartialViewTree<-TState as \State> {
	public function __construct(
		private \Stringish $view,
		private Map<string, Map<HttpMethod, Map<string, :ViewTree<TState>>>> $view_forest
		//  dependency key              route string
	) {}
	
	public function get_view(): \Stringish {
		return $this->view;
	}
}