<?php


namespace SergeLiatko\WPMeta\Traits;

/**
 * Trait PostTypesSupport
 *
 * @package SergeLiatko\WPMeta\Traits
 */
trait PostTypesSupport {

	/**
	 * @var string[] $types
	 */
	protected array $types;

	/**
	 * @return string[]
	 */
	public function getTypes(): array {
		return $this->types;
	}

	/**
	 * @param string[] $types
	 *
	 * @return static
	 */
	public function setTypes( array $types ): static {
		$types = array_filter(
			array_map(
				'sanitize_key',
				array_diff( $types, $this->getNotSupportedTypes() )
			)
		);
		//if no types provided use defaults
		if ( empty( $types ) ) {
			$types = $this->getDefaultSupportedTypes();
		}
		$this->types = $types;

		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getSupportedPostTypes(): array {
		return $this->getTypes();
	}

	/**
	 * @return string[]
	 */
	protected function getDefaultSupportedTypes(): array {
		return array_diff(
			get_post_types( array( 'public' => true ) ),
			$this->getNotSupportedTypes()
		);
	}

	/**
	 * @return string[]
	 */
	protected function getNotSupportedTypes(): array {
		return array(
			'nav_menu_item',
			'revision',
			'custom_css',
			'customize_changeset',
		);
	}

}
