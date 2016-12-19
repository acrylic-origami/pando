<?hh // strict
namespace Pando;
interface Parser {
	public function parse(string $str): mixed;
	public function serialize(mixed $obj): ?string;
}