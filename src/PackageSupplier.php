<?hh // strict
namespace Pando;
interface PackageSupplier<TPackage as DependencyPackage, TDependent as PackageDependent<TPackage>> {
	public function supply(classname<TDependent> $class): TPackage;
}