<?php


namespace SergeLiatko\WPMeta\Comment;

use SergeLiatko\WPMeta\ObjectMeta;
use SergeLiatko\WPMeta\Traits\IsEmpty;

/**
 * Class CommentMeta
 *
 * @package SergeLiatko\WPMeta\Comment
 */
class CommentMeta extends ObjectMeta {

	use IsEmpty;

	/**
	 * @inheritDoc
	 */
	protected function getDefaults(): array {
		return $this->parseArgsRecursive(
			array(
				'object_type' => 'comment',
			),
			parent::getDefaults()
		);
	}


}
