<?hh // strict
namespace Shufflr;
// type TemplateScaffold = shape('template' => string, 'mapping'=>Map<string, mixed>);
class TemplateManager {
	public Map<string, Template> $cache = Map{};
	protected Vector<string> $logged_templates = Vector{};
	protected ?int $log_level = null;
	protected string $template_root_directory;
	public function __construct(
		?string $template_root_directory = __DIR__,
		
		) {
		$this->template_root_directory = rtrim($template_root_directory, '/');
	}
	public function load(string $template): Template {
		if($this->cache->containsKey($template)) {
			return $this->cache[$template];
		}
		$template_realpath = realpath($this->template_root_directory . DIRECTORY_SEPARATOR . $template . '.php');
		// echo $template_realpath."\n";
		if(strpos($template_realpath, realpath($this->template_root_directory)) !== 0) {
			throw new \OutOfRangeException('Template path out of range of template directory.');
		}
		if(!file_exists($template_realpath)) {
			throw new \OutOfRangeException('Template does not exist.');
		}
		if(!include $template_realpath) {
			//PRODUCTION: @include $template_realpath
			throw new \OutOfRangeException('Opening template file failed.');
		}
		$this->cache[$template] = new Template(${basename($template) . '_template'}, ${basename($template) . '_mapping'});
		if(!is_null($this->log_level)) {
			$this->logged_templates->add($template);
		}
		return $this->cache[$template];
	}
	public function collect((function(): void) $f): Vector<Action> {
		// the purpose of this is to collect the vector of subscriptions after running 
	}
	public function start_logging(): void {
		$this->log_level = ob_get_level();
		ob_start();
	}
	public function stop_logging(): void {
		for(; $this->log_level < ob_get_level(); ob_end_clean()) {}
		$this->log_level = null;
	}
}
?>