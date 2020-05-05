<?php


namespace SergeLiatko\WPMeta\Traits;

/**
 * Trait IsEmpty
 *
 * @package SergeLiatko\WPMeta\Traits
 */
trait IsEmpty {

	/**
	 * Checks is passed parameter $data is empty.
	 *
	 * @param mixed $data
	 *
	 * @return bool
	 */
	protected function isEmpty( $data = null ): bool {
		return empty( $data );
	}
}
