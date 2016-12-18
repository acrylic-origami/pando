<?hh // strict
namespace Shufflr;
class Template {
	protected Set<string> $mapping_set;
	public function __construct(
		protected string $templateHTML,
		protected Vector<string> $mapping,
		protected Vector<Action> $subscriptions = Vector{} // check that this can indeed work with just a vector of Actions, otherwise this might need to be something like a Vector<PostSubscription>
		) {
		$this->mapping_set = Set($this->mapping);
	}
	public function prune(Map<arraykey, arraykey> $information_entity) {
		/*
		(Object | array) -> array
		Takes relevant properties from object/array and shoves them into another array that it returns to the client script for population with other values
		*/
		return $information_entity->filterWithKey((arraykey $k, arraykey $v) ==> $this->mapping_set->contains($k));
		
		// and this is why I love Hacklang :)
		
		// $ret = array();
		// if(is_array($information_entity)) {
		// 	foreach($this->mapping as $param) {
		// 		$ret[$param] = $information_entity[$param];
		// 	}
		// }
		// else {
		// 	// debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		// 	foreach($this->mapping as $param) {
		// 		$ret[$param] = $information_entity->{$param};
		// 	}
		// }
		// return $ret;
	}
	public function apply(Map<arraykey, mixed> $apply = Map{}): string {
		/*
		(array) -> string
		Applies populated array to HTML template 
		*/
		$params = array();
		foreach($this->mapping as $param) {
			$val = $this->_apply_param($param, $apply);
			array_push($params, $val);
			//purposefully not checking for whether isset
		}
		return vsprintf($this->templateHTML, $params);
	}
	protected function _apply_param(string $param, Map<arraykey, mixed> $apply = Map{}) {
		if(strstr($param, "?")) {
			list($param, $true_str) = explode("?", $param, 2);
			if(strstr($true_str, ":")) {
				list($true_str, $false_str) = explode(":", $true_str, 2);
			}
			return $apply[$param."?"] ? $this->_resolve($true_str, $apply) : ( $this->_resolve($false_str, $apply) ?: "" ); //param name for $mapping["plural?s"] for example is "plural?"
		}
		else {
			return $this->_resolve($apply[$param], $apply);
		}
	}
	protected function _resolve(string $param, Map<arraykey, string> $apply): mixed { //woohoo for immediately-invoked function expressions!
		if(strpos($param, "@") === 0) {
			return $this->_resolve($apply[substr($param, 1)], $apply);
		}
		elseif(is_callable($apply[$param])) {
			return $apply[$param]($this);
		}
		else {
			return $param;
		}
	}
}