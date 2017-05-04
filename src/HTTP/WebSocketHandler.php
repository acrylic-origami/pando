<?hh // strict
namespace Pando\HTTP;
use GuzzleHttp\Psr7\Request;

<<__ConsistentConstruct>>
interface WebSocketHandler extends Awaitable<mixed> {
	const string GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
	
	public function __construct(WebSocketConnection $connection);
}