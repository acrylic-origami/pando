<?hh // strict
class I {
	public static function I<T, Tf>(T $v): (function(Tf): Awaitable<T>) {
		return async(Tf $_) ==> $v;
	}
	public static function XHP<Tf>(string $plaintext): (function(Tf): Awaitable<\XHPRoot>) {
		return self::I(<x:frag>{$plaintext}</x:frag>); // await not allowed directly within XHP declaration unfortunately :/
	}
}