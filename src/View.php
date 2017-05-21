<?hh // strict
namespace Pando;
/**
 * Necessary, because `\Stringish::getString` returns exactly `string`, not `Stringish`.
 * Therefore, `ComparableView` which at time of the original implementation is the major
 * descendant, can't be `Stringish` but return a generic `<T as Stringish>` via
 * `__toString`.
 */
interface View<+T as \Stringish> {
	public function get_view(): T;
	public static function get_content_type(): string;
}