<?php


namespace SergeLiatko\WPMeta\Post;


use SergeLiatko\WPMeta\Factory;
use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Interfaces\HasSupportedPostTypes;
use SergeLiatko\WPMeta\Traits\PostScriptsSupport;
use SergeLiatko\WPMeta\Traits\PostTypesSupport;

/**
 * Class Screen
 *
 * @package SergeLiatko\WPMeta\Post
 */
class Screen implements HasId, HasSupportedPostTypes {

	use PostScriptsSupport, PostTypesSupport;

	/**
	 * @var string $id
	 */
	protected $id;

	/**
	 * @var array|array[]|\SergeLiatko\WPMeta\Post\MetaBox[]
	 */
	protected $boxes;

	/**
	 * @var array|array[]|\SergeLiatko\WPMeta\ObjectMeta[] $fields
	 */
	protected $fields;

	/**
	 * Screen constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string                 $id
		 * @var array|string[]         $types
		 * @var array|array[]          $boxes
		 * @var array|array[]          $fields
		 * @var array|string[]|array[] $scripts
		 * @var array|string[]|array[] $styles
		 */
		extract( wp_parse_args( $args, $this->getDefaults() ), EXTR_OVERWRITE );
		$this->setId( $id );
		$this->setTypes( $types );
		$this->setBoxes( $boxes );
		$this->setFields( $fields );
		//set only if not empty to skip extra work
		if ( ! empty( $scripts ) ) {
			$this->setScripts( $scripts );
		}
		//set only if not empty to skip extra work
		if ( ! empty( $styles ) ) {
			$this->setStyles( $styles );
		}
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return Screen
	 */
	public function setId( string $id ): Screen {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return array|array[]|\SergeLiatko\WPMeta\Post\MetaBox[]
	 * @noinspection PhpUnused
	 */
	public function getBoxes() {
		return $this->boxes;
	}

	/**
	 * @param array|array[] $boxes
	 *
	 * @return Screen
	 */
	public function setBoxes( array $boxes ) {
		$this->boxes = Factory::createMultiple(
			$boxes,
			array(
				'types' => $this->getTypes(),
			),
			'\\SergeLiatko\\WPMeta\\Post\\MetaBox'
		);

		return $this;
	}

	/**
	 * @return array|array[]|\SergeLiatko\WPMeta\ObjectMeta[]
	 * @noinspection PhpUnused
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param array|array[] $fields
	 *
	 * @return Screen
	 */
	public function setFields( array $fields ) {
		$this->fields = Factory::createMultiple(
			$fields,
			array(
				'object_type'    => 'post',
				'object_subtype' => $this->getTypes(),
				'display_hook'   => 'edit_form_advanced',
			),
			'\\SergeLiatko\\WPMeta\\ObjectMeta'
		);

		return $this;
	}

	/**
	 * @return array
	 */
	protected function getDefaults() {
		return array(
			'id'      => '',
			'types'   => array(),
			'boxes'   => array(),
			'fields'  => array(),
			'scripts' => array(),
			'styles'  => array(),
		);
	}

}
