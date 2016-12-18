<?hh // strict
namespace Shufflr;
interface IdentifierTreeBuilder {
	public function build(?string $identifier): IdentifierTree;
}