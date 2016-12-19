<?hh // strict
namespace Pando\Util\Class;
function parse_classname(string $name): FQClassname {
  return shape(
    'namespace' => array_slice(explode('\\', $name), 0, -1),
    'classname' => implode('', array_slice(explode('\\', $name), -1)),
  );
}