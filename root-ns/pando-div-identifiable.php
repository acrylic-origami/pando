<?hh // strict
class :pando:div-identifiable extends :x:element implements Pando\Identifiable {
	use XHPHelpers;
	attribute string id;
	children \Stringish;
	public function identify(): string {
		return $this->getID();
	}
	public function render(): :div {
		return <div id={$this->getID()}>
			{$this->getChildren()}
		</div>;
	}
}