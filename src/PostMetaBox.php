<?php


namespace SergeLiatko\WPMeta;

use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Traits\IsEmpty;
use WP_Post;

/**
 * Class PostMetaBox
 *
 * @package SergeLiatko\WPMeta
 */
class PostMetaBox implements HasId {

	use IsEmpty;

	/**
	 * @var string $title
	 */
	protected $title;

	/**
	 * @var string $id
	 */
	protected $id;

	/**
	 * @var string[] $types
	 */
	protected $types;

	/**
	 * @var string $context
	 */
	protected $context;

	/**
	 * @var string $priority
	 */
	protected $priority;

	/**
	 * @var callable|\Closure|null $callback
	 */
	protected $callback;

	/**
	 * @var array|null $callback_args
	 */
	protected $callback_args;

	/**
	 * @var string $description
	 */
	protected $description;

	/**
	 * @var array|array[] $fields
	 */
	protected $fields;

	/**
	 * PostMetaBox constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string                 $title
		 * @var string                 $id
		 * @var string[]               $types
		 * @var string                 $context
		 * @var string                 $priority
		 * @var callable|\Closure|null $callback
		 * @var array|null             $callback_args
		 * @var string                 $description
		 * @var array|array[]          $fields
		 */
		extract( wp_parse_args( $args, $this->getDefaults() ), EXTR_OVERWRITE );
		$this->setTitle( $title );
		$this->setId( $id );
		$this->setTypes( $types );
		$this->setContext( $context );
		$this->setPriority( $priority );
		$this->setCallback( $callback );
		$this->setCallbackArgs( $callback_args );
		$this->setDescription( $description );
		$this->setFields( $fields );
		foreach ( $this->getTypes() as $type ) {
			add_action( "add_meta_boxes_{$type}", array( $this, 'register' ), 10, 1 );
		}
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
	 * @return PostMetaBox
	 */
	public function setTitle( string $title ): PostMetaBox {
		$this->title = sanitize_text_field( $title );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		if ( empty( $this->id ) ) {
			$this->setId( sanitize_title_with_dashes( $this->getTitle() ) );
		}

		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return PostMetaBox
	 */
	public function setId( string $id ): PostMetaBox {
		$this->id = sanitize_key( $id );

		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getTypes(): array {
		return $this->types;
	}

	/**
	 * @param string[] $types
	 *
	 * @return PostMetaBox
	 */
	public function setTypes( array $types ): PostMetaBox {
		$types = array_filter(
			array_map(
				'sanitize_key',
				array_diff( $types, $this->getNotSupportedTypes() )
			)
		);
		//if no types provided register the meta box for all public supported post types
		if ( empty( $types ) ) {
			$types = array_diff(
				get_post_types( array( 'public' => true ) ),
				$this->getNotSupportedTypes()
			);
		}
		$this->types = $types;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContext(): string {
		return $this->context;
	}

	/**
	 * @param string $context
	 *
	 * @return PostMetaBox
	 */
	public function setContext( string $context ): PostMetaBox {
		$context       = in_array( $context, $this->getAllowedContexts() ) ? $context : 'advanced';
		$this->context = $context;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPriority(): string {
		return $this->priority;
	}

	/**
	 * @param string $priority
	 *
	 * @return PostMetaBox
	 */
	public function setPriority( string $priority ): PostMetaBox {
		$priority       = in_array( $priority, $this->getAllowedPriorities() ) ? $priority : 'default';
		$this->priority = $priority;

		return $this;
	}

	/**
	 * @return callable|\Closure|null
	 */
	public function getCallback() {
		return $this->callback;
	}

	/**
	 * @param callable|\Closure|null $callback
	 *
	 * @return PostMetaBox
	 */
	public function setCallback( $callback ) {
		$this->callback = $callback;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getCallbackArgs() {
		return $this->callback_args;
	}

	/**
	 * @param array|null $callback_args
	 *
	 * @return PostMetaBox
	 */
	public function setCallbackArgs( $callback_args = null ): PostMetaBox {
		$this->callback_args = ( is_array( $callback_args ) || is_null( $callback_args ) ) ?
			$callback_args
			: null;

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
	 * @return PostMetaBox
	 */
	public function setDescription( string $description ): PostMetaBox {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return array|array[]
	 * @noinspection PhpUnused
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param array|array[] $fields
	 *
	 * @return PostMetaBox
	 */
	public function setFields( array $fields ) {
		$this->fields = Factory::createMultiple(
			$fields,
			array(
				'object_type'    => 'post',
				'object_subtype' => $this->getTypes(),
				'display_hook'   => $this->getDisplayHook(),
			),
			'\\SergeLiatko\\WPMeta\\ObjectMeta'
		);

		return $this;
	}

	/**
	 * @param \WP_Post $post
	 */
	public function register( WP_Post $post ) {
		add_meta_box(
			$this->getId(),
			$this->getTitle(),
			array( $this, 'display' ),
			$post->post_type,
			$this->getContext(),
			$this->getPriority(),
			$this->getCallbackArgs()
		);
	}

	/**
	 * @param \WP_Post   $post
	 * @param array|null $args
	 */
	public function display( WP_Post $post, $args ) {
		if ( ! $this->isEmpty( $description = $this->getDescription() ) ) {
			echo wpautop( $description );
		}
		//action hook "do_meta_box-{$meta_box_id}"
		do_action( $this->getDisplayHook(), $post, $args );
		if ( is_callable( $callback = $this->getCallback() ) ) {
			call_user_func_array( $callback, array( $post, $args ) );
		}
	}

	/**
	 * @return string
	 */
	protected function getDisplayHook() {
		return sprintf( 'do_meta_box-%s', $this->getId() );
	}

	/**
	 * @return string[]
	 */
	protected function getAllowedPriorities() {
		return array(
			'default',
			'high',
			'low',
		);
	}

	/**
	 * @return string[]
	 */
	protected function getAllowedContexts() {
		return array(
			'normal',
			'side',
			'advanced',
		);
	}

	/**
	 * @return string[]
	 */
	protected function getNotSupportedTypes() {
		return array(
			'nav_menu_item',
			'revision',
			'custom_css',
			'customize_changeset',
		);
	}

	/**
	 * @return array
	 */
	protected function getDefaults() {
		return array(
			'title'         => '',
			'id'            => '',
			'types'         => array(),
			'context'       => 'advanced',
			'priority'      => 'default',
			'callback'      => null,
			'callback_args' => null,
			'description'   => '',
			'fields'        => array(),
		);
	}

}
