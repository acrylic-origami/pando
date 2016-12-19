<?hh // decl
namespace Pando;
class RelationalBooleanDeltaBuilder implements BooleanDeltaBuilder {
	protected SQLParserCache $parser;
	protected $namespace = array();
	public function __construct(array $args = array()) {
		$this->parser = $args['parser']; //expect ParsedQueryCache
		
		if(!empty($args['database'])) {
			array_push($this->namespace, $args['database']);
		}
		if(!empty($args['table'])) {
			array_push($this->namespace, $args['table']);
		}
	}
	public function database($db) {
		$this->namespace[0] = $db;
	}
	public function table($table) {
		$this->namespace[1] = $table;
	}
	
	public function or_() {
		$args = func_get_args();
		return function(DatabaseDeltaCollection $affecteds) use ($args) {
			foreach($args as $arg) {
				if($affecteds->contains($this->_resolve($arg))) {
					return true; //short-circuiting
				}
			}
			return false;
		};
	}
	public function and_() {
		$args = func_get_args();
		return function(DatabaseDeltaCollection $affecteds) use ($args) {
			foreach(func_get_args() as $arg) {
				if(!$affecteds->contains($this->_resolve($arg))) {
					return false; //short-circuiting
				}
			}
			return true;
		};
	}
	public function xor_($a, $b) {
		return function(DatabaseDeltaCollection $affecteds) use ($a, $b) {
			return $affecteds->contains($this->_resolve($a)) ^ $affecteds->contains($this->_resolve($b));
		};
	}
	
	protected function _resolve($name) {
		$identifier = $this->parser->identifier($name);
		$namespace = $this->namespace;
		if(count($identifier) > 3-count($this->namespace)) {
			foreach($identifier as $k=>$i) {
				$namespace[3 - count($identifier) + $k] = $i;
			}
		}
		else {
			array_merge($namespace, $identifier); //tack $identifier onto the end of $namespace
		}
		return $namespace;
	}
}