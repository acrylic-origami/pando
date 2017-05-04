<?hh // strict
namespace Pando\HTTP;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

trait PlainWebSocketResponseTrait {
	require implements WebSocketHandler;
	
	public function __construct(
		private WebSocketConnection $connection
	) {
		$lowercase_headers = Map{};
		foreach($connection->getRequest()->getHeaders() as $k => $header) 
			$lowercase_headers[strtolower($k)] = $header;
		
		$sec_websocket_accept = base64_encode(sha1(sprintf('%s%s', $lowercase_headers['sec-websocket-key'], self::GUID)));
		$connection->respond(new Response(
			101,
			[
				'Upgrade' => 'websocket',
				'Connection' => 'Upgrade',
				'Sec-Websocket-Accept' => $sec_websocket_accept
			]
			)
		);
		
		parent::__construct($connection);
	}
}