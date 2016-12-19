<?hh // strict
namespace Pando;
abstract class TrackedDatabase<TDelta as DatabaseDelta, TDeltaCollection as Iterable<TDelta>> as Database {
	protected TDeltaCollection $deltas; //  = new MySQLDeltaCollection(Map{}); quickfix: look into this more
	
	public function get_deltas(?string $classname = null): ?Vector<DatabaseDelta> {
		if($classname === static::GENERAL_SELECTOR) {
			return $this->deltas->flatten();
		}
		elseif(!is_null($classname) && $this->deltas->containsKey($classname)) {
			return $this->deltas[$classname];
		}
		else {
			return null;
		}
	}
	public function clear_deltas(?string $classname = null): void {
		if($classname === static::GENERAL_SELECTOR) {
			$this->deltas->units->clear();
		}
		elseif(!is_null($classname) && $this->deltas->units->containsKey($classname)) {
			$this->deltas->units->removeKey($classname);
		}
	}
}