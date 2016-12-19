<?hh // strict
namespace Pando;
interface PackageDependent<T as DependencyPackage> {
	public function unpack(T $package): void;
}