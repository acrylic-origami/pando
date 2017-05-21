<?hh // strict
namespace Pando\View\Comparable;
use Pando\ComparableView;
abstract class XHPView<-TComparable as XHPView<TComparable>> implements ComparableView<\XHPRoot, TComparable> {
	public function __construct(protected \XHPRoot $view) {}
	public function get_view(): \XHPRoot {
		return $this->view;
	}
	final public static function get_content_type(): string {
		return 'text/html';
	}
}