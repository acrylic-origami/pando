<?hh // strict
namespace Pando;
<<__ConsistentConstruct>> // Mostly to accept just the u_id. Else, building the subscription tree will be a total pain. I'm still on the fence about how doable this actually all is...
// Nope, the __ConsistentConstruct does a lot more than sit there with its fancy syntax. With it, we can enforce an Identifiable approach, forcing the calling scope to only provide the base dependencies and the identity. 
// The constructor is the only function to be called _exactly_ once during the lifetime of the object, so it is the only place in which we can have arguments of covariant types. It turns out that __ConsistentConstruct only enforces the _signature_ of the constructor, not its implementation (makes a lot of sense, thanks Hack team!) (which means at least we can control the setting of the identity and _strongly_ suggest the unpacking of the dependencies).
abstract class Identifiable<+TIdentity as arraykey, +TPackage as DependencyPackage> {
	// We can expect all Identifiables to be PackageDependent just because it's such a general condition, and it allows our constructor to be consistent, purely to make the subscription easier without compromising _too_ much the usage of these entities outside of subscriptions
	private ?ImmMap<string, ?Identifiable<arraykey, DependencyPackage>> $subidentifiables;
	// well, the lack of being able to cast this is a real pain. I think I'll have to get rid of unpack, which means that PackageDependent will be a useless class. 
	
	public function __construct(
		TPackage $package,
		private ?TIdentity $identity, // waiting for that object-protected! :D #7216
		// or `?Map<string, TIdentity> $identities`? Then, `identify` can coalesce the identities, starting with the most direct and trying to resolve from there
		) {
		$this->subidentifiables = $this->specify_subidentifiables();
		// $this->
	}
	public function identify(): ?TIdentity {
		return $this->identity;
	}
	final public function get_subidentifiables(): ?ImmMap<string, ?Identifiable<arraykey, DependencyPackage>> {
		return $this->subidentifiables;
	}
	// final protected function specify_subidentifiables<TChildPackage as DependencyPackage>(): ?ImmMap<string, IdentifiableTree<arraykey, TChildPackage>> {
	// 	$subidentifiables = $this->_specify_subidentifiables();
	// 	if(!is_null($subidentifiables)) {
	// 		foreach($subidentifiables as $prop=>$subidentifiable) {
	// 			assert($this->{$prop} instanceof $subidentifiable);
	// 		}
	// 	}
	// 	return $subidentifiables;
	// }
	abstract protected function specify_subidentifiables(): ?ImmMap<string, ?Identifiable<arraykey, DependencyPackage>>;
}