<?php


namespace SergeLiatko\WPMeta\Post;

use Closure;
use SergeLiatko\WPMeta\Factory;
use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Interfaces\HasSupportedPostTypes;
use SergeLiatko\WPMeta\ObjectMeta;
use SergeLiatko\WPMeta\Traits\IsCallable;
use SergeLiatko\WPMeta\Traits\PostScriptsSupport;
use SergeLiatko\WPMeta\Traits\PostTypesSupport;
use WP_Post;

/**
 * Class MetaBox
 *
 * @package SergeLiatko\WPMeta\Post
 */
class MetaBox implements HasId, HasSupportedPostTypes {

	use IsCallable, PostScriptsSupport, PostTypesSupport;

	/**
	 * @var string $title
	 */
	protected string $title;

	/**
	 * @var string $id
	 */
	protected string $id;

	/**
	 * @var string $context
	 */
	protected string $context;

	/**
	 * @var string $priority
	 */
	protected string $priority;

	/**
	 * @var Closure|callable|string|array|null $callback
	 */
	protected $callback;

	/**
	 * @var array|null $callback_args
	 */
	protected ?array $callback_args;

	/**
	 * @var string $description
	 */
	protected string $description;

	/**
	 * @var array|array[] $fields
	 */
	protected array $fields;

	/**
	 * MetaBox constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string $title
		 * @var string $id
		 * @var array|string[] $types
		 * @var string $context
		 * @var string $priority
		 * @var Closure|callable|string|array|null $callback
		 * @var array|null $callback_args
		 * @var string $description
		 * @var array|array[] $fields
		 * @var array|array[]|string[] $scripts
		 * @var array|array[]|string[] $styles
		 */
		extract( wp_parse_args( $args, $this->getDefaults() ) );
		$this->setTitle( $title );
		$this->setId( $id );
		$this->setTypes( $types );
		$this->setContext( $context );
		$this->setPriority( $priority );
		$this->setCallback( $callback );
		$this->setCallbackArgs( $callback_args );
		$this->setDescription( $description );
		$this->setFields( $fields );
		$this->setScripts( $scripts );
		$this->setStyles( $styles );
		foreach ( $this->getTypes() as $type ) {
			add_action( "add_meta_boxes_$type", array( $this, 'register' ) );
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
	 * @return MetaBox
	 */
	public function setTitle( string $title ): MetaBox {
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
	 * @return MetaBox
	 */
	public function setId( string $id ): MetaBox {
		$this->id = sanitize_key( $id );

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
	 * @return MetaBox
	 */
	public function setContext( string $context ): MetaBox {
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
	 * @return MetaBox
	 */
	public function setPriority( string $priority ): MetaBox {
		$priority       = in_array( $priority, $this->getAllowedPriorities() ) ? $priority : 'default';
		$this->priority = $priority;

		return $this;
	}

	/**
	 * @return Closure|callable|string|array|null
	 */
	public function getCallback(): callable|array|string|Closure|null {
		return $this->callback;
	}

	/**
	 * @param callable|array|string|Closure|null $callback
	 *
	 * @return MetaBox
	 */
	public function setCallback( callable|array|string|Closure|null $callback ): MetaBox {
		$this->callback = $this->is_callable( $callback ) ? $callback : null;

		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getCallbackArgs(): ?array {
		return $this->callback_args;
	}

	/**
	 * @param array|null $callback_args
	 *
	 * @return MetaBox
	 */
	public function setCallbackArgs( ?array $callback_args = null ): MetaBox {
		$this->callback_args = $callback_args;

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
	 * @return MetaBox
	 */
	public function setDescription( string $description ): MetaBox {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return array|array[]|ObjectMeta[]|HasId[]
	 * @noinspection PhpUnused
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * @param array|array[] $fields
	 *
	 * @return MetaBox
	 */
	public function setFields( array $fields ): MetaBox {
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
	 * @param string $field_id
	 *
	 * @return array|array[]|ObjectMeta|HasId|null
	 */
	public function getField( string $field_id ): array|HasId|ObjectMeta|null {
		$fields = $this->getFields();

		return ! empty( $fields[ $field_id ] ) ? $fields[ $field_id ] : null;
	}

	/**
	 * @param WP_Post $post
	 */
	public function register( WP_Post $post ): void {
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
	 * @param WP_Post $post
	 * @param array|null $args
	 */
	public function display( WP_Post $post, ?array $args ): void {
		if ( ! $this->isEmpty( $description = $this->getDescription() ) ) {
			echo wpautop( $description );
		}
		//action hook "do_meta_box-{$meta_box_id}"
		do_action( $this->getDisplayHook(), $post, $args );
		if ( $this->is_callable( $callback = $this->getCallback() ) ) {
			call_user_func_array( $callback, array( $post, $args ) );
		}
	}

	/**
	 * @return string
	 */
	protected function getDisplayHook(): string {
		return sprintf( 'do_meta_box-%s', $this->getId() );
	}

	/**
	 * @return string[]
	 */
	protected function getAllowedPriorities(): array {
		return array(
			'default',
			'high',
			'low',
		);
	}

	/**
	 * @return string[]
	 */
	protected function getAllowedContexts(): array {
		return array(
			'normal',
			'side',
			'advanced',
		);
	}

	/**
	 * @return array
	 */
	protected function getDefaults(): array {
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
			'scripts'       => array(),
			'styles'        => array(),
		);
	}

}
