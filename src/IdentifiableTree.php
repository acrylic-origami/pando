<?hh // strict
namespace Pando;
<<__ConsistentConstruct>> // Mostly to accept just the u_id. Else, building the subscription tree will be a total pain. I'm still on the fence about how doable this actually all is...
// Nope, the __ConsistentConstruct does a lot more than sit there with its fancy syntax. With it, we can enforce an Identifiable approach, forcing the calling scope to only provide the base dependencies and the identity. 
// The constructor is the only function to be called _exactly_ once during the lifetime of the object, so it is the only place in which we can have arguments of covariant types. It turns out that __ConsistentConstruct only enforces the _signature_ of the constructor, not its implementation (makes a lot of sense, thanks Hack team!) (which means at least we can control the setting of the identity and _strongly_ suggest the unpacking of the dependencies).
abstract class IdentifiableTree<+TIdentity as arraykey, TPackage as DependencyPackage> implements PackageDependent<TPackage> {
	// We can expect all Identifiables to be PackageDependent just because it's such a general condition, and it allows our constructor to be consistent, purely to make the subscription easier without compromising _too_ much the usage of these entities outside of subscriptions
	public function __construct(
		TPackage $package,
		private ?TIdentity $identity,
		// or `?Map<string, TIdentity> $identities`? Then, `identify` can coalesce the identities, starting with the most direct and trying to resolve from there
		private ?ImmMap<string, IdentifiableTree<arraykey, TPackage>> $subidentifiables
		) {
		$this->unpack($package);
		$this->subidentifiables = $this->subidentifiables ?? $this->specify_subidentifiables();
		// $this->
	}
	public function identify(): ?TIdentity {
		return $this->identity;
	}
	final protected function specify_subidentifiables<TChildPackage as DependencyPackage>(): ?ImmMap<string, IdentifiableTree<arraykey, TChildPackage>> {
		$subidentifiables = $this->_specify_subidentifiables();
		foreach($subidentifiables as $prop=>$subidentifiable) {
			assert($this->{$prop} instanceof $subidentifiable);
		}
		return $subidentifiables;
	}
	abstract protected function _specify_subidentifiables(): ?ImmMap<string, classname<IdentifiableTree>>;
}