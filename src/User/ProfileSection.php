<?php


namespace SergeLiatko\WPMeta\User;

use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Traits\IsCallable;
use TechSpokes\TrackHospitalitySoftware\Traits\IsEmptyStatic;
use WP_User;

/**
 * Class ProfileSection
 *
 * @package SergeLiatko\WPMeta\User
 */
class ProfileSection implements HasId {

	use IsEmptyStatic, IsCallable;

	/**
	 * @var string $id
	 */
	protected $id;

	/**
	 * @var int $priority
	 */
	protected $priority;

	/**
	 * @var string $title
	 */
	protected $title;

	/**
	 * @var string $description
	 */
	protected $description;

	/**
	 * @var array|\SergeLiatko\WPMeta\User\UserMeta[]
	 */
	protected $fields;

	/**
	 * @var string $display_hook
	 */
	protected $display_hook;

	public function __construct( array $args ) {
		/**
		 * @var string                                    $id
		 * @var int                                       $priority
		 * @var string                                    $title
		 * @var string                                    $description
		 * @var array|\SergeLiatko\WPMeta\User\UserMeta[] $fields
		 */
		extract( wp_parse_args( $args, $this->defaults() ), EXTR_OVERWRITE );
		$this->setId( $id );
		$this->setPriority( $priority );
		$this->setTitle( $title );
		$this->setDescription( $description );
		$this->setFields( $fields );
		if ( !self::isEmpty( $this->getFields() ) ) {
			add_action( 'show_user_profile', array( $this, 'display' ), $this->getPriority(), 1 );
			add_action( 'edit_user_profile', array( $this, 'display' ), $this->getPriority(), 1 );
		}
	}

	/**
	 * @param \WP_User $user
	 */
	public function display( WP_User $user ): void {
		//get class base
		$class = esc_attr( $this->hyphenize( $id = $this->getId() ) );
		//output title
		if ( !self::isEmpty( $title = $this->getTitle() ) ) {
			printf( '<h2 class="%1$s-title">%2$s</h2>', $class, $title );
		}
		//output description
		if ( !self::isEmpty( $description = $this->getDescription() ) ) {
			printf(
				'<div class="%1$s-description">%2$s</div>',
				$class,
				wpautop( $description, false )
			);
		}
		//start table output
		printf(
			'<table id="%1$s" class="form-table %2$s-table" role="presentation">',
			esc_attr( $id ),
			$class
		);
		//output fields
		do_action( $this->getDisplayHook(), $user );
		//close table
		echo '</table>';
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
	 * @return ProfileSection
	 */
	public function setId( string $id ): ProfileSection {
		$this->id = sanitize_key( $id );

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return $this->priority;
	}

	/**
	 * @param int $priority
	 *
	 * @return ProfileSection
	 */
	public function setPriority( int $priority ): ProfileSection {
		$this->priority = absint( $priority );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * @param string $title
	 *
	 * @return ProfileSection
	 */
	public function setTitle( string $title ): ProfileSection {
		$this->title = sanitize_text_field( $title );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * @param string $description
	 *
	 * @return ProfileSection
	 */
	public function setDescription( string $description ): ProfileSection {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return array|\SergeLiatko\WPMeta\User\UserMeta[]
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * @param array|\SergeLiatko\WPMeta\User\UserMeta[] $fields
	 *
	 * @return ProfileSection
	 */
	public function setFields( array $fields ): ProfileSection {
		$display_hook = $this->getDisplayHook();
		$objects      = array();
		array_walk(
			$fields,
			function ( &$field, $index, $display_hook ) use ( &$objects ) {
				if ( $field instanceof UserMeta ) {
					$field->setDisplayHook( $display_hook );
				} else {
					$field = new UserMeta( array_merge(
						(array) $field,
						array(
							'display_hook' => $display_hook,
						)
					) );
				}
				$objects[ $field->getId() ] = $field;
			},
			$display_hook
		);
		$this->fields = $objects;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayHook(): string {
		if ( is_null( $this->display_hook ) ) {
			$this->setDisplayHook( sprintf( 'do_user_profile_fields-%1$s', $this->getId() ) );
		}

		return $this->display_hook;
	}

	/**
	 * @param string $display_hook
	 *
	 * @return ProfileSection
	 */
	public function setDisplayHook( string $display_hook ): ProfileSection {
		$this->display_hook = $display_hook;

		return $this;
	}

	/**
	 * @return array
	 */
	protected function defaults(): array {
		return array(
			'id'          => '',
			'priority'    => 10,
			'title'       => '',
			'description' => '',
			'fields'      => array(),
		);
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	protected function hyphenize( string $text ): string {
		return strtolower(
			trim(
				preg_replace( '/^[\d]+|[^a-z0-9]+/i', '-', $text ),
				'-'
			)
		);
	}

}
