<?hh // strict
namespace Pando\State;
use Psr\Http\Message\RequestInterface;
interface IStateFactory<+TState as \State> {
	public function make(\Route<TState> $context, RequestInterface $request): TState;
}