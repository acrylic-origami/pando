<?hh // strict
namespace Pando\Util\Class;
function classname(string $name): string {
	$full = parse_classname($name);
	return $full['classname'];
}