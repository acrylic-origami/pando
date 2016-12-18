<?hh // strict
namespace Shufflr;
interface BooleanDeltaBuilder {
	public function or_() : (function(DatabaseDeltaCollection) : bool);
	public function and_() : (function(DatabaseDeltaCollection) : bool);
	public function xor_(string $a, string $b) : (function(DatabaseDeltaCollection) : bool);
}