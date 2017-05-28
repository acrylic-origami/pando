<?hh // strict
use Facebook\HackRouter\HttpMethod;
class ViewTree<Ta, Tb> extends :x:element {
	attribute 
		Route<Tx, Ta> route @required,
		Ta view @required,
		Map<string, Map<HttpMethod, Map<string, ViewTree<Tb, Ta>>>>
		//  dependency key              route string
	// children this*;
	children empty;
	
	public function render(): TComparable {
		return $this->:view;
	}
	public async function rerender(): Awaitable<ViewTree<Tx, TComparable>> {
		return $this->:route->render();
	}
}