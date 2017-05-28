<?hh // strict
namespace Pando;
interface View {
	public function get_view(): \Stringish;
	public static function get_content_type(): string;
}