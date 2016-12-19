<?hh // strict
namespace Pando;
// 16-12-18: not actually sure if IdentifierTree is what I want here, but I'll cross that bridge when I get there.
interface BooleanDeltaBuilder {
	public function or_() : (function(IdentifierTree) : bool);
	public function and_() : (function(IdentifierTree) : bool);
	public function xor_(string $a, string $b) : (function(IdentifierTree) : bool);
}