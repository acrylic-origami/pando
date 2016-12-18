<?hh // strict
namespace Shufflr;
class MySQLFactory extends DatabaseFactory<MySQL> {
	public function spawn(Credentials $credentials): MySQL {
		return new MySQL($credentials, $this->query_parser);
	}
}