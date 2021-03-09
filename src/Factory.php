<?php


namespace SergeLiatko\WPMeta;

use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Traits\ParseArgsRecursive;

/**
 * Class Factory
 *
 * @package SergeLiatko\WPMeta
 */
class Factory {

	use ParseArgsRecursive;

	/**
	 * @var \SergeLiatko\WPMeta\Factory $instance
	 */
	protected static $instance;

	/**
	 * @return \SergeLiatko\WPMeta\Factory
	 */
	public static function getInstance(): Factory {
		if ( !self::$instance instanceof Factory ) {
			self::setInstance( new self() );
		}

		return self::$instance;
	}

	/**
	 * @param \SergeLiatko\WPMeta\Factory $instance
	 */
	public static function setInstance( Factory $instance ) {
		self::$instance = $instance;
	}

	/**
	 * @param array  $args
	 * @param string $default_class
	 *
	 * @return mixed
	 */
	public static function create( array $args, string $default_class ) {
		$class = empty( $args['_class'] ) ? $default_class : $args['_class'];

		return new $class( $args );
	}

	/**
	 * @param array  $items
	 * @param array  $default_args
	 * @param string $default_class
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function createMultiple( array $items, array $default_args, string $default_class ): array {
		$instance = self::getInstance();
		//instantiate items
		array_walk(
			$items,
			function ( &$item, $key, array $defaults ) use ( $default_class, $instance ) {
				$item = self::create(
					$instance->parseArgsRecursive( $item, $defaults ),
					$default_class
				);
			},
			$default_args
		);
		//accept items implementing HasId interface only
		$items = array_filter( $items, function ( $item ) {
			return ( $item instanceof HasId );
		} );
		//populate associative array with items and their IDs as keys
		$new_items = array();
		/** @var HasId $item */
		foreach ( $items as $item ) {
			$new_items[ $item->getId() ] = $item;
		}

		return $new_items;
	}

	/**
	 * Factory constructor.
	 */
	protected function __construct() {
	}

}
