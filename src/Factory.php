<?php


namespace SergeLiatko\WPMeta;

/**
 * Class Factory
 *
 * @package SergeLiatko\WPMeta
 */
class Factory {

	/**
	 * @param array  $args
	 * @param string $default
	 *
	 * @return mixed
	 */
	public static function create( array $args, string $default ) {
		$class = empty( $args['_class'] ) ? $default : $args['_class'];
		return new $class( $args );
	}
}
