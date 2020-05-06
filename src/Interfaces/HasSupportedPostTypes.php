<?php


namespace SergeLiatko\WPMeta\Interfaces;

/**
 * Interface HasSupportedPostTypes
 *
 * @package SergeLiatko\WPMeta\Interfaces
 */
interface HasSupportedPostTypes {

	/**
	 * @return array|string[]
	 */
	public function getSupportedPostTypes(): array;

}
