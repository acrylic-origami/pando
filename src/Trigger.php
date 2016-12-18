<?hh // strict
<<__ConsistentConstruct>>
abstract class Trigger<TDelta as BooleanDelta> {
	protected bool $_triggered = false;
	public IdentifierTreeBooleanStatement<MySQLTreeIdentifier> $catchall_trigger;
	public Map<classname<Trigger>, IdentifierTreeBooleanStatement<MySQLTreeIdentifier>> $classed_triggers; // I'm going to bet that classnames are not hashable
	// I bet wrong! 
	public function _triggered(): bool {
		return $this->_triggered;
	}
	public function prescreen(ImmVector<Trigger> $triggers, Vector<DatabaseDelta> $deltas) : bool {
		return ($this->dependencies->containsKey('*') && $this->dependencies['*']->exec($deltas)) || $triggers->reduce((bool $last, Trigger $next) ==> $last || $this->dependencies[$next:: class]->exec($deltas), false);
	}
	abstract protected function _screen(): bool;
	public function screen() : bool {
		return ($this->triggered = $this->_screen())
	}
}