<?hh // strict
namespace Pando\State;
use Psr\Http\Message\RequestInterface;

class StateFactory implements IStateFactory<\State> {
	public function make(\Route<\State> $context, RequestInterface $request): \State {
		return new \State($context, $request);
	}
}