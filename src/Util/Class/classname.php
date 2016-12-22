<?hh // strict
namespace Pando\Util\Class;
function classname(\stdClass $name): string {
	$full = parse_classname(get_class($name));
	return $full['classname'];
}