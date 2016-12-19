<?hh // strict
namespace Pando;
abstract class ThreeStatePullable<+TIdentify as arraykey, +TPackage as DependencyPackage> extends Identifiable<TIdentify, TPackage> {
	//                                                                                           ↑ Might seem kind of random, but this makes a lot of sense for all of the pullables that I can iamgine, which can only pull on Identifiable instances
	protected ?bool $_exists = false;
	
	abstract public function pull(): bool;
}