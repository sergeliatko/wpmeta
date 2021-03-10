<?php


namespace SergeLiatko\WPMeta\Comment;


use SergeLiatko\WPMeta\Factory;
use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Traits\IsCallable;
use SergeLiatko\WPMeta\Traits\PostScriptsSupport;
use WP_Comment;

/**
 * Class MetaBox
 *
 * @package SergeLiatko\WPMeta\Comment
 */
class MetaBox implements HasId {

	use IsCallable, PostScriptsSupport;

	/**
	 * @var string $id
	 */
	protected $id;

	/**
	 * @var string $title
	 */
	protected $title;

	/**
	 * @var string $description
	 */
	protected $description;

	/**
	 * @var string $context
	 */
	protected $context;

	/**
	 * @var string $priority
	 */
	protected $priority;

	/**
	 * @var array|null $callback_args
	 */
	protected $callback_args;

	/**
	 * @var \SergeLiatko\WPMeta\Comment\CommentMeta[] $fields
	 */
	protected $fields;

	/**
	 * MetaBox constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string                                            $id
		 * @var string                                            $title
		 * @var string                                            $description
		 * @var string                                            $context
		 * @var string                                            $priority
		 * @var array|null                                        $callback_args
		 * @var \SergeLiatko\WPMeta\Comment\CommentMeta[]|array[] $fields
		 * @var array|array[]|string[]                            $scripts
		 * @var array|array[]|string[]                            $styles
		 */
		extract( wp_parse_args( $args, $this->defaults() ), EXTR_OVERWRITE );
		$this->setId( $id );
		$this->setTitle( $title );
		$this->setDescription( $description );
		$this->setContext( $context );
		$this->setPriority( $priority );
		$this->setCallbackArgs( $callback_args );
		$this->setFields( $fields );
		$this->setScripts( $scripts );
		$this->setStyles( $styles );
		add_action( 'add_meta_boxes_comment', array( $this, 'register' ), 10, 0 );
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
		$this->id = $id;

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
	 * @return MetaBox
	 */
	public function setTitle( string $title ): MetaBox {
		$this->title = $title;

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
		$contexts = array(
			'normal',
			'side',
			'advanced',
		);

		$this->context = in_array( $context, $contexts ) ? $context : 'advanced';

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
		$priorities = array(
			'default',
			'high',
			'low',
		);

		$this->priority = in_array( $priority, $priorities ) ? $priority : 'default';

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
	public function setCallbackArgs( ?array $callback_args ): MetaBox {
		$this->callback_args = $callback_args;

		return $this;
	}

	/**
	 * @return \SergeLiatko\WPMeta\Comment\CommentMeta[]
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * @param \SergeLiatko\WPMeta\Comment\CommentMeta[] $fields
	 *
	 * @return MetaBox
	 */
	public function setFields( array $fields ): MetaBox {
		$this->fields = empty( $fields ) ?
			array()
			: Factory::createMultiple(
				$fields,
				array(
					'object_type'  => 'comment',
					'display_hook' => $this->getDisplayHook(),
				),
				'\SergeLiatko\WPMeta\Comment\CommentMeta'
			);

		return $this;
	}

	/**
	 * Registers meta box in WP.
	 */
	public function register(): void {
		add_meta_box(
			$this->getId(),
			$this->getTitle(),
			array( $this, 'display' ),
			'comment',
			$this->getContext(),
			$this->getPriority(),
			$this->getCallbackArgs()
		);
	}

	/**
	 * @param \WP_Comment $comment
	 * @param array|null  $args
	 */
	public function display( WP_Comment $comment, ?array $args ): void {
		if ( !$this->isEmpty( $description = $this->getDescription() ) ) {
			echo wpautop( $description );
		}
		//action hook "do_meta_box-{$meta_box_id}"
		do_action( $this->getDisplayHook(), $comment, $args );
	}

	/**
	 * @return string
	 */
	protected function getDisplayHook(): string {
		return sprintf( 'do_meta_box-%s', $this->getId() );
	}

	/**
	 * @return array Constructor default arguments.
	 */
	protected function defaults(): array {
		return array(
			'id'            => '',
			'title'         => '',
			'description'   => '',
			'context'       => 'advanced',
			'priority'      => 'default',
			'callback_args' => null,
			'fields'        => array(),
			'scripts'       => array(),
			'styles'        => array(),
		);
	}

}
