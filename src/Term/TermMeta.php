<?php


namespace SergeLiatko\WPMeta\Term;


use SergeLiatko\WPMeta\ObjectMeta;

/**
 * Class TermMeta
 *
 * @package SergeLiatko\WPMeta\Term
 */
class TermMeta extends ObjectMeta {

	/**
	 * @return string|string[]
	 */
	public function getDisplayHook() {
		if ( $this->isEmpty( $this->display_hook ) ) {
			if ( $this->isEmpty( $object_subtype = $this->getObjectSubtype() ) ) {
				$object_subtype = $this->getAllTaxonomies();
			}
			$taxonomies = array_filter( (array) $object_subtype );
			array_walk( $taxonomies, function ( &$taxonomy ) {
				$taxonomy = sprintf( '%s_edit_form_fields', $taxonomy );
			} );
			$this->setDisplayHook( $taxonomies );
		}

		return $this->display_hook;
	}

	/**
	 * @param \WP_Term $object
	 */
	public function display( $object ) {
		//exit if no display callback is provided
		if ( !$this->is_callable( $this->getDisplayCallback() ) ) {
			return;
		}
		$description = $this->isEmpty( $description = $this->getHelp() ) ? ''
			: sprintf( '<p class="description">%1$s</p>', $description );
		printf(
			'<tr class="form-field term-%1$s-wrap"><th scope="row"><label for="%2$s">%3$s</label></th><td>',
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
		return wp_parse_args( array(
			'object_type' => 'term',
		), parent::getDefaults() );
	}

	/**
	 * @return array
	 */
	protected function getAllTaxonomies(): array {
		return array_merge(
			$this->getBuiltInTaxonomies(),
			get_taxonomies( array( '_builtin' => false ) )
		);
	}

	/**
	 * @return string[]
	 */
	protected function getBuiltInTaxonomies(): array {
		return array(
			'category'      => 'category',
			'tag'           => 'tag',
			'link_category' => 'link_category',
		);
	}

}
