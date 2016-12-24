<?hh // strict
namespace Pando\Util\Class;
function classname(mixed $obj): string {
	$full = parse_classname(get_class($obj));
	return $full['classname'];
}