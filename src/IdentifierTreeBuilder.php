<?hh // strict
namespace Pando;
interface IdentifierTreeBuilder {
	public function build(?string $identifier): IdentifierTree;
}