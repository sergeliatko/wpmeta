<?php


namespace SergeLiatko\WPMeta\Comment;


use SergeLiatko\WPMeta\Factory;
use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Traits\PostScriptsSupport;

/**
 * Class Screen
 *
 * @package SergeLiatko\WPMeta\Comment
 */
class Screen implements HasId {

	use PostScriptsSupport;

	/**
	 * @var string $id
	 */
	protected string $id;

	/**
	 * @var MetaBox[]
	 */
	protected array $boxes;

	/**
	 * Screen constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string $id
		 * @var MetaBox[]|array[]|array $boxes
		 * @var array|array[]|string[] $scripts
		 * @var array|array[]|string[] $styles
		 */
		extract( wp_parse_args( $args, $this->defaults() ) );
		$this->setId( $id );
		$this->setBoxes( $boxes );
		$this->setScripts( $scripts );
		$this->setStyles( $styles );
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
	 * @return MetaBox[]
	 * @noinspection PhpUnused
	 */
	public function getBoxes(): array {
		return $this->boxes;
	}

	/**
	 * @param MetaBox[]|array|array[] $boxes
	 *
	 * @return Screen
	 */
	public function setBoxes( array $boxes ): Screen {
		$this->boxes = Factory::createMultiple(
			$boxes,
			array(),
			'\SergeLiatko\WPMeta\Comment\MetaBox'
		);

		return $this;
	}

	/**
	 * @return array Default constructor arguments.
	 */
	protected function defaults(): array {
		return array(
			'id'      => '',
			'boxes'   => array(),
			'scripts' => array(),
			'styles'  => array(),
		);
	}

}
