<?php


namespace SergeLiatko\WPMeta\User;


use SergeLiatko\WPMeta\ObjectMeta;

/**
 * Class UserMeta
 *
 * @package SergeLiatko\WPMeta\User
 */
class UserMeta extends ObjectMeta {

	/**
	 * @param mixed $object
	 */
	public function display( $object ): void {
		//exit if no display callback is provided
		if ( !$this->is_callable( $this->getDisplayCallback() ) ) {
			return;
		}
		$description = $this->isEmpty( $description = $this->getHelp() ) ?
			''
			: sprintf( '<p class="description">%1$s</p>', $description );
		printf(
			'<tr class="form-field user-%1$s-wrap"><th scope="row"><label for="%2$s">%3$s</label></th><td>',
			$this->hyphenize( $this->getId() ),
			$this->getId(),
			$this->getLabel()
		);
		parent::display( $object );
		printf(
			'%1$s</td></tr>',
			$description
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getDefaults(): array {
		return wp_parse_args(
			array(
				'object_type' => 'user',
			),
			parent::getDefaults()
		);
	}

}
