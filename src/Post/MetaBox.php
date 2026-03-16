<?php
/**
 * WordPress post meta-box registration and rendering.
 *
 * Manages WordPress meta-box lifecycle: registration, field instantiation,
 * rendering with custom callbacks, and associated scripts/styles.
 *
 * @package SergeLiatko\WPMeta\Post
 * @since 1.0.0
 */

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
 * Encapsulates a WordPress meta-box with field management, rendering hooks,
 * and script/style dependencies. Automatically registers via `add_meta_boxes_{$post_type}`
 * hook for configured post types.
 *
 * @package SergeLiatko\WPMeta\Post
 * @since 1.0.0
 */
class MetaBox implements HasId, HasSupportedPostTypes {

	use IsCallable, PostScriptsSupport, PostTypesSupport;

	/**
	 * Human-readable title displayed as the meta-box heading.
	 *
	 * @var string $title The meta-box title.
	 * @since 1.0.0
	 */
	protected string $title;

	/**
	 * Unique identifier for the meta-box, used in `add_meta_box()` and CSS classes.
	 *
	 * Auto-generated from the title if not explicitly set.
	 *
	 * @var string $id The unique identifier.
	 * @since 1.0.0
	 */
	protected string $id;

	/**
	 * Meta-box display context.
	 *
	 * Allowed values: 'normal', 'side', 'advanced'.
	 * See WordPress `add_meta_box()` documentation.
	 *
	 * @var string $context The display context.
	 * @since 1.0.0
	 */
	protected string $context;

	/**
	 * Meta-box display priority within its context.
	 *
	 * Allowed values: 'default', 'high', 'low'.
	 * See WordPress `add_meta_box()` documentation.
	 *
	 * @var string $priority The display priority.
	 * @since 1.0.0
	 */
	protected string $priority;

	/**
	 * Custom callback invoked after action hook and ObjectMeta fields render.
	 *
	 * Optional callable: function, array (object, method), string (function name),
	 * or Closure. Receives WP_Post and callback arguments.
	 *
	 * @var Closure|callable|string|array|null $callback The custom render callback.
	 * @since 1.0.0
	 */
	protected $callback;

	/**
	 * Callback function to determine whether the meta-box should be displayed.
	 *
	 * Optional callable: function, array (object, method), string (function name),
	 * or Closure. Receives WP_Post and callback arguments. Must return boolean.
	 *
	 * @var Closure|callable|string|array|null $active_callback The callback function.
	 * @since 1.0.0
	 */
	protected $active_callback;

	/**
	 * Arguments passed to the custom callback.
	 *
	 * Also passed to WordPress `add_meta_box()` as meta_box_args.
	 * Available to the callback via the second parameter.
	 *
	 * @var array|null $callback_args Arguments for the callback function.
	 * @since 1.0.0
	 */
	protected ?array $callback_args;

	/**
	 * Descriptive text displayed at the top of the meta-box.
	 *
	 * Processed with `wpautop()` when rendered.
	 *
	 * @var string $description The meta-box description text.
	 * @since 1.0.0
	 */
	protected string $description;

	/**
	 * Meta-box fields managed by ObjectMeta.
	 *
	 * Keyed by field ID. Instantiated by Factory from array configuration.
	 * Rendered automatically before the custom callback.
	 *
	 * @var array|array[] $fields The meta-box fields.
	 * @since 1.0.0
	 */
	protected array $fields;

	/**
	 * Constructor.
	 *
	 * Initializes meta-box configuration and automatically registers action hooks
	 * for each configured post type. Calls Factory to instantiate ObjectMeta fields
	 * with this meta-box as the display context.
	 *
	 * @param array $args Configuration array. Recognized keys:
	 *                      - title (string): Meta-box heading. Required.
	 *                      - id (string): Unique identifier. Auto-generated if omitted.
	 *                      - types (string[]): Post types this meta-box applies to. Default: empty.
	 *                      - context (string): 'normal', 'side', or 'advanced'. Default: 'advanced'.
	 *                      - priority (string): 'default', 'high', or 'low'. Default: 'default'.
	 *                      - callback (callable|string|array|Closure|null): Custom render callback.
	 *                      - callback_args (array|null): Arguments passed to callback.
	 *                      - description (string): Text displayed above fields. Default: empty.
	 *                      - fields (array[]): ObjectMeta field configurations. Default: empty.
	 *                      - scripts (string[]|array[]): Script dependencies (handle or config).
	 *                      - styles (string[]|array[]): Stylesheet dependencies (handle or config).
	 *
	 * @sideEffects Registers `add_meta_boxes_{$post_type}` action hooks via add_action().
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $args ) {
		/**
		 * @var string $title
		 * @var string $id
		 * @var array|string[] $types
		 * @var string $context
		 * @var string $priority
		 * @var Closure|callable|string|array|null $active_callback
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
		$this->setActiveCallback( $active_callback );
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
	 * Retrieve the meta-box title.
	 *
	 * @return string Human-readable title.
	 * @since 1.0.0
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Set the meta-box title.
	 *
	 * @param string $title Human-readable title. Sanitized with sanitize_text_field().
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setTitle( string $title ): MetaBox {
		$this->title = sanitize_text_field( $title );

		return $this;
	}

	/**
	 * Retrieve the meta-box ID.
	 *
	 * Auto-generates from a title if not explicitly set.
	 *
	 * @return string Unique identifier.
	 * @since 1.0.0
	 */
	public function getId(): string {
		if ( empty( $this->id ) ) {
			$this->setId( sanitize_title_with_dashes( $this->getTitle() ) );
		}

		return $this->id;
	}

	/**
	 * Set the meta-box ID.
	 *
	 * @param string $id Unique identifier. Sanitized with sanitize_key().
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setId( string $id ): MetaBox {
		$this->id = sanitize_key( $id );

		return $this;
	}

	/**
	 * Retrieve the meta-box display context.
	 *
	 * @return string One of: 'normal', 'side', 'advanced'.
	 * @since 1.0.0
	 */
	public function getContext(): string {
		return $this->context;
	}

	/**
	 * Set the meta-box display context.
	 *
	 * @param string $context Display location. Validated against allowed values.
	 *                         Falls back to 'advanced' if invalid.
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setContext( string $context ): MetaBox {
		$context       = in_array( $context, $this->getAllowedContexts() ) ? $context : 'advanced';
		$this->context = $context;

		return $this;
	}

	/**
	 * Retrieve the meta-box display priority.
	 *
	 * @return string One of: 'default', 'high', 'low'.
	 * @since 1.0.0
	 */
	public function getPriority(): string {
		return $this->priority;
	}

	/**
	 * Set the meta-box display priority.
	 *
	 * @param string $priority Priority within context. Validated against allowed values.
	 *                          Falls back to 'default' if invalid.
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setPriority( string $priority ): MetaBox {
		$priority       = in_array( $priority, $this->getAllowedPriorities() ) ? $priority : 'default';
		$this->priority = $priority;

		return $this;
	}

	/**
	 * Retrieve the meta-box active callback.
	 *
	 * @return Closure|callable|string|array|null Callback function or null if not set.
	 * @since 1.0.0
	 */
	public function getActiveCallback(): callable|array|string|Closure|null {
		return $this->active_callback;
	}

	/**
	 * Set the meta-box active callback.
	 *
	 * @param Closure|callable|string|array|null $active_callback Callback function.
	 *                                                       Validated with is_callable().
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setActiveCallback( callable|array|string|Closure|null $active_callback ): MetaBox {
		$this->active_callback = $this->is_callable( $active_callback ) ? $active_callback : null;

		return $this;
	}

	/**
	 * Retrieve the custom render callback.
	 *
	 * @return Closure|callable|string|array|null Callback function or null if not set.
	 * @since 1.0.0
	 */
	public function getCallback(): callable|array|string|Closure|null {
		return $this->callback;
	}

	/**
	 * Set the custom render callback.
	 *
	 * @param callable|array|string|Closure|null $callback Custom render function.
	 *                                                       Validated with is_callable().
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setCallback( callable|array|string|Closure|null $callback ): MetaBox {
		$this->callback = $this->is_callable( $callback ) ? $callback : null;

		return $this;
	}

	/**
	 * Retrieve arguments passed to the custom callback.
	 *
	 * @return array|null Callback arguments or null.
	 * @since 1.0.0
	 */
	public function getCallbackArgs(): ?array {
		return $this->callback_args;
	}

	/**
	 * Set arguments for the custom callback.
	 *
	 * @param array|null $callback_args Arguments passed to callback. Also used as
	 *                                   meta_box_args in add_meta_box().
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setCallbackArgs( ?array $callback_args = null ): MetaBox {
		$this->callback_args = $callback_args;

		return $this;
	}

	/**
	 * Retrieve the meta-box description.
	 *
	 * @return string Description text or empty string.
	 * @since 1.0.0
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * Set the meta-box description.
	 *
	 * @param string $description Introductory text. Processed with wpautop() during render.
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setDescription( string $description ): MetaBox {
		$this->description = $description;

		return $this;
	}

	/**
	 * Retrieve all meta-box fields.
	 *
	 * @return ObjectMeta[]|HasId[]|array[] Fields keyed by field ID.
	 * @noinspection PhpUnused
	 * @since 1.0.0
	 */
	public function getFields(): array {
		return $this->fields;
	}

	/**
	 * Set and instantiate meta-box fields.
	 *
	 * Converts field configuration arrays into ObjectMeta instances via Factory,
	 * passing this meta-box's context and display hook.
	 *
	 * @param array[] $fields ObjectMeta field configurations. Each item is converted
	 *                         to an ObjectMeta instance.
	 *
	 * @return MetaBox Current instance for method chaining.
	 * @since 1.0.0
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
	 * Retrieve a single meta-box field by ID.
	 *
	 * @param string $field_id The field ID.
	 *
	 * @return ObjectMeta|HasId|array[]|null Field instance or null if not found.
	 * @since 1.0.0
	 */
	public function getField( string $field_id ): array|HasId|ObjectMeta|null {
		$fields = $this->getFields();

		return ! empty( $fields[ $field_id ] ) ? $fields[ $field_id ] : null;
	}

	/**
	 * Register the meta-box with WordPress.
	 *
	 * Called by the `add_meta_boxes_{$post_type}` action hook for each configured
	 * post type. Registers the meta-box using add_meta_box() with display callback.
	 *
	 * @param WP_Post $post Current post object. Used for context in add_meta_box().
	 *
	 * @return void
	 * @sideEffects Calls add_meta_box() which registers the meta-box with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function register( WP_Post $post ): void {
		// check if the meta-box should be displayed for the current post context
		if ( true === (
				$this->is_callable( $active_callback = $this->getActiveCallback() )
				&& call_user_func_array( $active_callback, [ $post, $this->getCallbackArgs() ] )
			)
		) {
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
	}

	/**
	 * Render the meta-box.
	 *
	 * Called by WordPress when rendering the meta-box in the edit post screen.
	 * Renders description, fires action hook, triggers ObjectMeta field rendering,
	 * and invokes optional custom callback.
	 *
	 * @param WP_Post $post The post object being edited.
	 * @param array|null $args Meta-box arguments from add_meta_box().
	 *
	 * @return void
	 * @sideEffects Outputs HTML. Triggers do_action() with a display hook.
	 *             ObjectMeta fields render and emit their output.
	 *
	 * @since 1.0.0
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
	 * Retrieve the action hook name for field rendering.
	 *
	 * Format: `do_meta_box-{$meta_box_id}`.
	 *
	 * @return string Action hook name.
	 * @since 1.0.0
	 */
	protected function getDisplayHook(): string {
		return sprintf( 'do_meta_box-%s', $this->getId() );
	}

	/**
	 * Retrieve allowed priority values.
	 *
	 * @return string[] List of valid priority values.
	 * @since 1.0.0
	 */
	protected function getAllowedPriorities(): array {
		return array(
			'default',
			'high',
			'low',
		);
	}

	/**
	 * Retrieve allowed context values.
	 *
	 * @return string[] List of valid context values.
	 * @since 1.0.0
	 */
	protected function getAllowedContexts(): array {
		return array(
			'normal',
			'side',
			'advanced',
		);
	}

	/**
	 * Retrieve default configuration values.
	 *
	 * Used by constructor to initialize properties with fallback values
	 * when configuration keys are missing or not provided.
	 *
	 * @return array Keyed array of default values for all constructor parameters.
	 * @since 1.0.0
	 */
	protected function getDefaults(): array {
		return array(
			'title'         => '',
			'id'            => '',
			'types'         => array(),
			'context'       => 'advanced',
			'priority'      => 'default',
			'active_callback' => [ $this, 'meta_box_active' ],
			'callback'      => null,
			'callback_args' => null,
			'description'   => '',
			'fields'        => array(),
			'scripts'       => array(),
			'styles'        => array(),
		);
	}

	/**
	 * Determine whether the meta-box should be displayed.
	 *
	 * @param WP_Post $post The post object being edited.
	 * @param array|null $args Meta-box arguments from add_meta_box().
	 *
	 * @return bool Whether the meta-box should be displayed.
	 * @noinspection PhpUnusedParameterInspection
	 */
	protected function meta_box_active( WP_Post $post, ?array $args = null ): bool {
		// by default, meta-box is active for all posts of all types
		return true;
	}

}
