<?hh // strict
namespace Pando;
use Pando\Tree\Tree;
class ParsedQueryCache {
	const string IDENTIFY_QUOTE = '`';
	const string IDENTIFY_DELIMITER = '.';
	const string IDENTIFY_ESCAPED_QUOTE = '``';
	
	public function __construct(
		/* HH_FIXME[2049] Non-Hack libraries without HHI files throw `Unbound Name` errors in strict mode. Luckily, I have the privilege of knowing this class does indeed exist. */
		protected \PHPSQLParser\PHPSQLParser $parser, 
		protected \Memcached $memcached) {
	}
	
	public function parse(string $sql): Tree<string, arraykey> {
		$parsed = $this->memcached->get($sql);
		if(is_null($parsed) && $this->memcached->getResultCode() == \Memcached::RES_NOTFOUND) {
			$parsed = $this->parser->parse($sql);
			$this->memcached->set($sql, $parsed);
		}
		invariant(is_array($parsed), 'Parsing did not return array. SQL statement may be unparseable.');
		return $this->_treeify_parsed_query($parsed);
	}
	
	protected function _treeify_parsed_query(array<mixed, mixed> $node): Tree<string, arraykey> {
		$M = Map{};
		foreach($node as $k=>$v) {
			arraykey_invariant($k, 'External library implementation error: parsed key is not of type `arraykey`.', (arraykey $ak) ==> {
				if(is_array($v))
					$M[$ak] = $this->_treeify_parsed_query($v);
				else {
					invariant(is_string($v), 'External library implementation error: leaf of parsed query is not string.');
					$M[$ak] = new Tree(null, $v);
				}
			});
		}
		return new Tree($M, null);
	}
	
	public function identifier(string $str): Vector<string> {
		$splits = Vector{}; 
		$s = 0; // tracks split number
		$q = (int)($str[0] === self::IDENTIFY_QUOTE); // counter for "levels" of quotation: if 0, we've left the identifier block
		$last_i = 0; // tracks the position of the last substring
		$f = false; // tracks quote parity and adjacency
		
		for($i=1; $i<=strlen($str); $i++) {
			$q -= (int)($f && ($i === strlen($str) || $str[$i] !== self::IDENTIFY_QUOTE)); //if the last character is an identifier quote, but this one isn't, then decrement $q (sets to 0 for now)
			
			$f = !$f && ($q && $str[$i] === self::IDENTIFY_QUOTE);
			
			if($q == 0 && ($i === strlen($str) || $str[$i] === self::IDENTIFY_DELIMITER)) { // wait for end of string or delimiter before splitting
				$trimmed = trim(substr($str, $last_i, min(strlen($str), $i)-$last_i)); // trim errant spaces
				$trimmed = substr($trimmed, $trimmed[0] === self::IDENTIFY_QUOTE, -(int)($trimmed[strlen($trimmed)-1] === self::IDENTIFY_QUOTE) ?: strlen($trimmed)); // ...then surrounding quotes: this will never remove quotes part of the name, as ``name is an invalid identifier without surrounding backticks
				$splits->add(str_replace(self::IDENTIFY_ESCAPED_QUOTE, self::IDENTIFY_QUOTE, $trimmed)); // replace escaped quote with single quote to get true identifier name
				$last_i = min(strlen($str), $i)+1; // increment the left boundary to skip the delimiter
			}
		}
		return $splits;
	}
}