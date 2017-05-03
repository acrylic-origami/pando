<?hh // strict
namespace Pando\MySQL;
type Credentials = shape( 'host' => string, 'port' => int, 'database' => string, 'user' => string, 'pass' => string );