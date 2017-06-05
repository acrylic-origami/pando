<?hh // strict
use Facebook\HackRouter\HttpMethod;
use Pando\{
	Route,
	ViewFactory
};
class :ViewTree<-TState as State> extends :x:element {
	attribute 
		/* HH_FIXME[4121] Just... don't touch it, k? */
		ViewFactory<TState> view_factory @required,
		/* HH_FIXME[4121] Just... don't touch it, k? */
		PartialViewTree<TState> partial @required;
		//  dependency key              route string
	// children this*;
	children empty;
	private ?int $id = null;
	
	/**
	 * \Stringish from View -> XHPRoot
	 * @return
	 */
	public function render(): \XHPRoot {
		invariant(is_null($this->id), '%s::%s must only be called exactly once per view component', __CLASS__, __METHOD__);
		$id = $this->getContext('id');
		invariant(is_int($id), 'Running ID count should be int');
		$this->id = $id;
		$this->setContext('id', $this->id + 1);
		return <div id={sprintf('pando-%d', $this->id)}>
			$this->:partial->get_view()
		</div>;
	}
	public async function rerender(): Awaitable<?\Stringish> {
		invariant(!is_null($this->id), '%s::render must be called before %s::%s', __CLASS__, __CLASS__, __METHOD__);
		$pair = await $this->:view_factory->next();
		invariant(!is_null($pair), 'Implementation error: unexpected `null` yielded from ViewFactory, since ViewFactory should never terminate');
		return <div id={sprintf('pando-%d', $this->id)}>
			$pair[1]?->get_view();
		</div>;
	}
}