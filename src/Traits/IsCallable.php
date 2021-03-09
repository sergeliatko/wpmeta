<?php


namespace SergeLiatko\WPMeta\Traits;

use Closure;

/**
 * Trait IsCallable
 *
 * @package SergeLiatko\WPMeta\Traits
 */
trait IsCallable {

	/**
	 * @param $maybe_callable
	 *
	 * @return bool
	 */
	protected function is_callable( $maybe_callable ): bool {
		return ( $maybe_callable instanceof Closure ) || is_callable( $maybe_callable, true );
	}

}
