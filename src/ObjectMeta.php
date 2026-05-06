<?php
/**
 * WordPress object metadata registration, persistence, and UI integration.
 *
 * Provides a configurable wrapper around `register_meta()` plus helpers for
 * saving, retrieving, deleting, and optionally rendering metadata fields for
 * supported WordPress object types.
 *
 * @package SergeLiatko\WPMeta
 * @since 1.0.0
 */

namespace SergeLiatko\WPMeta;

use Closure;
use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Interfaces\HasSupportedPostTypes;
use SergeLiatko\WPMeta\Traits\IsCallable;
use SergeLiatko\WPMeta\Traits\ParseArgsRecursive;
use SergeLiatko\WPMeta\Traits\PostScriptsSupport;

/**
 * Class ObjectMeta
 *
 * Encapsulates a single metadata definition for comments, posts, terms, or
 * users. Handles WordPress registration, save hooks, display callbacks, and
 * convenience wrappers around metadata CRUD operations.
 *
 * @package SergeLiatko\WPMeta
 * @since 1.0.0
 */
class ObjectMeta implements HasId, HasSupportedPostTypes {

	use IsCallable, ParseArgsRecursive, PostScriptsSupport;

	/**
	 * The submitted suffix for metadata keys that were rendered in the UI.
	 */
	public const DISPLAYED_NAME_SUFFIX = '__displayed';

	/**
	 * Unique field identifier used in markup and internal references.
	 *
	 * Auto-generated from the meta key when omitted.
	 *
	 * @var string $id Object meta identifier.
	 * @since 1.0.0
	 */
	protected string $id;

	/**
	 * Registered WordPress metadata key.
	 *
	 * Used for registration, request input lookup, and CRUD helpers.
	 *
	 * @var string $meta_key Sanitized metadata key.
	 * @since 1.0.0
	 */
	protected string $meta_key;

	/**
	 * Target the WordPress object type for this metadata definition.
	 *
	 * Supported values are limited to the keys returned by
	 * `getObjectTypesMap()`.
	 *
	 * @var string $object_type Object type such as `post` or `user`.
	 * @since 1.0.0
	 */
	protected string $object_type;

	/**
	 * Optional object subtype restriction for the registered metadata.
	 *
	 * For post metadata this typically contains one or more post types.
	 *
	 * @var array|string|string[] $object_subtype One or more subtype slugs.
	 * @since 1.0.0
	 */
	protected string|array $object_subtype;

	/**
	 * Registered WordPress metadata value type.
	 *
	 * Allowed values mirror the `register_meta()` `type` argument.
	 *
	 * @var string $type Metadata storage type.
	 * @since 1.0.0
	 */
	protected string $type;

	/**
	 * Human-readable metadata description used during registration.
	 *
	 * @var string $description Metadata description.
	 * @since 1.0.0
	 */
	protected string $description;

	/**
	 * Whether the metadata key stores a single value per object.
	 *
	 * Controls both registration and save behavior.
	 *
	 * @var bool $single True for single-value metadata, false for multi-value metadata.
	 * @since 1.0.0
	 */
	protected bool $single;

	/**
	 * Optional callback used by WordPress to sanitize metadata values.
	 *
	 * @var Closure|callable|string|array|null $sanitize_callback Sanitization callback.
	 * @since 1.0.0
	 */
	protected $sanitize_callback;

	/**
	 * Optional callback used by WordPress to authorize metadata access.
	 *
	 * @var Closure|callable|string|array|null $auth_callback Authorization callback.
	 * @since 1.0.0
	 */
	protected $auth_callback;

	/**
	 * REST API exposure configuration passed to `register_meta()`.
	 *
	 * Accepts either a boolean flag or a detailed REST schema array.
	 *
	 * @var bool|array $show_in_rest REST exposure settings.
	 * @since 1.0.0
	 */
	protected array|bool $show_in_rest;

	/**
	 * Hook or hooks where the field display callback should run.
	 *
	 * Empty values disable UI rendering while still allowing registration and
	 * save handling.
	 *
	 * @var string[]|string $display_hook One or more WordPress action hooks.
	 * @since 1.0.0
	 */
	protected string|array $display_hook;

	/**
	 * Callback used to render the field UI for an object.
	 *
	 * Receives the resolved object ID and the current `ObjectMeta` instance.
	 *
	 * @var Closure|callable|string|array|null $display_callback Display callback.
	 * @since 1.0.0
	 */
	protected $display_callback;

	/**
	 * Human-readable field label available to consumers and renderers.
	 *
	 * @var string $label Field label text.
	 * @since 1.0.0
	 */
	protected string $label;

	/**
	 * Supplemental help text available to consumers and renderers.
	 *
	 * @var string $help Field help text.
	 * @since 1.0.0
	 */
	protected string $help;

	/**
	 * HTML attributes applied to the rendered input element.
	 *
	 * Defaults are merged so the input `name` always matches the meta key.
	 *
	 * @var array $input_attrs Input attribute map.
	 * @since 1.0.0
	 */
	protected array $input_attrs;

	/**
	 * Choice list used by select, checkbox, or radio-style renderers.
	 *
	 * @var array $choices Available field choices.
	 * @since 1.0.0
	 */
	protected array $choices;

	/**
	 * Constructor.
	 *
	 * Hydrates metadata configuration, normalizes callbacks and UI options, and
	 * registers the WordPress hooks required for registration, saving, and
	 * optional UI display. If the resolved meta key is empty, the instance is
	 * left inert and no hooks are attached.
	 *
	 * @param array $args Configuration array. Recognized keys:
	 *                      - id (string): Unique identifier. Auto-generated if omitted.
	 *                      - meta_key (string): Registered metadata key. Required for activation.
	 *                      - object_type (string): `comment`, `post`, `term`, or `user`.
	 *                      - object_subtype (string|string[]): Optional subtype restriction.
	 *                      - type (string): Metadata type for `register_meta()`.
	 *                      - description (string): Registration description text.
	 *                      - single (bool): Whether the metadata stores a single value.
	 *                      - sanitize_callback (callable|string|array|Closure|null): Sanitization callback.
	 *                      - auth_callback (callable|string|array|Closure|null): Authorization callback.
	 *                      - show_in_rest (bool|array): REST API exposure settings.
	 *                      - display_hook (string|string[]): Action hook(s) for rendering the field.
	 *                      - display_callback (callable|string|array|Closure|null): Field renderer.
	 *                      - label (string): Human-readable field label.
	 *                      - help (string): Optional help text.
	 *                      - input_attrs (array): Input element attributes.
	 *                      - choices (array): Choice map for multi-option inputs.
	 *                      - scripts (string[]|array[]): Script dependencies for UI rendering.
	 *                      - styles (string[]|array[]): Stylesheet dependencies for UI rendering.
	 *
	 * @sideEffects Registers WordPress actions for metadata registration, saving,
	 *              and optional display rendering.
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $args ) {
		/**
		 * @var string $id
		 * @var string $meta_key
		 * @var string $object_type
		 * @var array|string|string[] $object_subtype
		 * @var string $type
		 * @var string $description
		 * @var bool $single
		 * @var Closure|callable|string|array|null $sanitize_callback
		 * @var Closure|callable|string|array|null $auth_callback
		 * @var array|bool $show_in_rest
		 * @var string|string[]|array $display_hook
		 * @var Closure|callable|string|array|null $display_callback
		 * @var string $label
		 * @var string $help
		 * @var array $input_attrs
		 * @var array $choices
		 * @var array|array[]|string[] $scripts
		 * @var array|array[]|string[] $styles
		 */
		extract( $this->parseArgsRecursive( $args, $this->getDefaults() ) );
		$this->setMetaKey( $meta_key );

		if ( ! $this->isEmpty( $this->getMetaKey() ) ) {
			$this->setId( $id );
			$this->setObjectType( $object_type );
			$this->setObjectSubtype( $object_subtype );
			$this->setType( $type );
			$this->setDescription( $description );
			$this->setSingle( $single );
			$this->setSanitizeCallback( $sanitize_callback );
			$this->setAuthCallback( $auth_callback );
			$this->setShowInRest( $show_in_rest );
			$this->setDisplayHook( $display_hook );
			$this->setDisplayCallback( $display_callback );
			$this->setLabel( $label );
			$this->setHelp( $help );
			$this->setInputAttrs( $input_attrs );
			$this->setChoices( $choices );
			$this->setScripts( $scripts );
			$this->setStyles( $styles );

			add_action( 'init', array( $this, 'register' ), 20, 0 );
			add_action( $this->getSaveHook(), array( $this, 'maybeSave' ) );

			if (
				! $this->isEmpty( $this->getDisplayCallback() )
				&& ! $this->isEmpty( $hooks = $this->getDisplayHook() )
			) {
				foreach ( (array) $hooks as $hook ) {
					add_action( $hook, array( $this, 'display' ) );
				}
			}
		}
	}

	/**
	 * Return supported post types when this metadata targets posts.
	 *
	 * This satisfies `HasSupportedPostTypes` for consumers that need to inspect
	 * post-specific metadata definitions.
	 *
	 * @return string[] Configured post types, or an empty array for non-post metadata.
	 * @since 1.0.0
	 */
	public function getSupportedPostTypes(): array {
		return ( 'post' === $this->getObjectType() ) ? (array) $this->getObjectSubtype() : array();
	}

	/**
	 * Retrieve the field identifier.
	 *
	 * Auto-generates the ID from the meta key when one was not explicitly set.
	 *
	 * @return string Unique field identifier.
	 * @since 1.0.0
	 */
	public function getId(): string {
		if ( empty( $this->id ) ) {
			$this->setId( $this->hyphenize( $this->getMetaKey() ) );
		}

		return $this->id;
	}

	/**
	 * Set the field identifier.
	 *
	 * @param string $id Unique field identifier.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setId( string $id ): ObjectMeta {
		$this->id = $id;

		return $this;
	}

	/**
	 * Retrieve the registered metadata key.
	 *
	 * @return string Metadata key.
	 * @since 1.0.0
	 */
	public function getMetaKey(): string {
		return $this->meta_key;
	}

	/**
	 * Set the metadata key.
	 *
	 * The value is normalized with `sanitize_key()` before storage.
	 *
	 * @param string $meta_key Metadata key.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setMetaKey( string $meta_key ): ObjectMeta {
		$this->meta_key = sanitize_key( $meta_key );

		return $this;
	}

	/**
	 * Retrieve the WordPress object type.
	 *
	 * @return string Object type such as `post`, `term`, `comment`, or `user`.
	 * @since 1.0.0
	 */
	public function getObjectType(): string {
		return $this->object_type;
	}

	/**
	 * Set the WordPress object type.
	 *
	 * Invalid values fall back to `post`.
	 *
	 * @param string $object_type Target object type.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setObjectType( string $object_type ): ObjectMeta {
		$object_type       = in_array( $object_type, $this->getAllowedObjectTypes() ) ? $object_type : 'post';
		$this->object_type = $object_type;

		return $this;
	}

	/**
	 * Retrieve the configured object subtype restriction.
	 *
	 * @return array|string|string[] One or more subtype slugs.
	 * @since 1.0.0
	 */
	public function getObjectSubtype(): array|string {
		return $this->object_subtype;
	}

	/**
	 * Set the object subtype restriction.
	 *
	 * Single subtype strings are normalized to an array and sanitized with
	 * `sanitize_key()`.
	 *
	 * @param array|string|string[] $object_subtype One or more subtype slugs.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setObjectSubtype( array|string $object_subtype ): ObjectMeta {
		if ( ! is_array( $object_subtype ) ) {
			$object_subtype = array( $object_subtype );
		}
		$this->object_subtype = array_filter( array_map( 'sanitize_key', $object_subtype ) );

		return $this;
	}

	/**
	 * Retrieve the registered metadata value type.
	 *
	 * @return string Metadata type.
	 * @since 1.0.0
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Set the metadata value type.
	 *
	 * Invalid values fall back to `string`.
	 *
	 * @param string $type Metadata type.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setType( string $type ): ObjectMeta {
		$type       = in_array( $type, $this->getAllowedTypes() ) ? $type : 'string';
		$this->type = $type;

		return $this;
	}

	/**
	 * Retrieve the metadata description.
	 *
	 * @return string Description text.
	 * @since 1.0.0
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * Set the metadata description.
	 *
	 * @param string $description Description text.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setDescription( string $description ): ObjectMeta {
		$this->description = $description;

		return $this;
	}

	/**
	 * Determine whether the metadata stores a single value.
	 *
	 * @return bool True when only one value should be stored.
	 * @since 1.0.0
	 */
	public function isSingle(): bool {
		return $this->single;
	}

	/**
	 * Set whether the metadata stores a single value.
	 *
	 * @param bool $single True for single-value metadata, false for multi-value metadata.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setSingle( bool $single ): ObjectMeta {
		$this->single = $single;

		return $this;
	}

	/**
	 * Retrieve the sanitization callback.
	 *
	 * @return Closure|callable|string|array|null Sanitization callback or null.
	 * @since 1.0.0
	 */
	public function getSanitizeCallback(): callable|array|string|Closure|null {
		return $this->sanitize_callback;
	}

	/**
	 * Set the sanitization callback.
	 *
	 * Non-callable values are discarded and stored as null.
	 *
	 * @param callable|array|string|Closure|null $sanitize_callback Sanitization callback.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setSanitizeCallback( callable|array|string|Closure|null $sanitize_callback ): ObjectMeta {
		$this->sanitize_callback = $this->is_callable( $sanitize_callback ) ? $sanitize_callback : null;

		return $this;
	}

	/**
	 * Retrieve the authorization callback.
	 *
	 * @return Closure|callable|string|array|null Authorization callback or null.
	 * @since 1.0.0
	 */
	public function getAuthCallback(): callable|array|string|Closure|null {
		return $this->auth_callback;
	}

	/**
	 * Set the authorization callback.
	 *
	 * Non-callable values are discarded and stored as null.
	 *
	 * @param callable|array|string|Closure|null $auth_callback Authorization callback.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setAuthCallback( callable|array|string|Closure|null $auth_callback ): ObjectMeta {
		$this->auth_callback = $this->is_callable( $auth_callback ) ? $auth_callback : null;

		return $this;
	}

	/**
	 * Retrieve REST API exposure settings.
	 *
	 * @return array|bool REST registration settings.
	 * @since 1.0.0
	 */
	public function getShowInRest(): bool|array {
		return $this->show_in_rest;
	}

	/**
	 * Set REST API exposure settings.
	 *
	 * @param bool|array $show_in_rest REST registration settings.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setShowInRest( bool|array $show_in_rest ): ObjectMeta {
		$this->show_in_rest = $show_in_rest;

		return $this;
	}

	/**
	 * Retrieve the display hooks used for UI rendering.
	 *
	 * @return string[]|string One hook, many hooks, or an empty string when disabled.
	 * @since 1.0.0
	 */
	public function getDisplayHook(): array|string {
		return $this->display_hook;
	}

	/**
	 * Set the display hooks used for UI rendering.
	 *
	 * Array values are sanitized and filtered. Invalid or empty values collapse
	 * to an empty string to disable display registration.
	 *
	 * @param string|string[] $display_hook One or more WordPress action hooks.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setDisplayHook( array|string $display_hook ): ObjectMeta {
		if ( is_array( $display_hook ) ) {
			$display_hook = array_filter( $display_hook, 'sanitize_key' );
			if ( empty( $display_hook ) ) {
				$display_hook = '';
			}
		} elseif ( is_string( $display_hook ) ) {
			$display_hook = sanitize_key( $display_hook );
		} else {
			$display_hook = sanitize_key( strval( $display_hook ) );
		}
		$this->display_hook = $display_hook;

		return $this;
	}

	/**
	 * Retrieve the display callback.
	 *
	 * @return Closure|callable|string|array|null Display callback or null.
	 * @since 1.0.0
	 */
	public function getDisplayCallback(): callable|array|string|Closure|null {
		return $this->display_callback;
	}

	/**
	 * Set the display callback.
	 *
	 * Non-callable values are discarded and stored as null.
	 *
	 * @param callable|array|string|Closure|null $display_callback Display callback.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setDisplayCallback( callable|array|string|Closure|null $display_callback ): ObjectMeta {
		$this->display_callback = $this->is_callable( $display_callback ) ? $display_callback : null;

		return $this;
	}

	/**
	 * Retrieve the field label.
	 *
	 * @return string Field label text.
	 * @since 1.0.0
	 * @noinspection PhpUnused
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * Set the field label.
	 *
	 * @param string $label Field label text.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setLabel( string $label ): ObjectMeta {
		$this->label = $label;

		return $this;
	}

	/**
	 * Retrieve the field help text.
	 *
	 * @return string Help text.
	 * @since 1.0.0
	 * @noinspection PhpUnused
	 */
	public function getHelp(): string {
		return $this->help;
	}

	/**
	 * Set the field help text.
	 *
	 * @param string $help Help text.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setHelp( string $help ): ObjectMeta {
		$this->help = $help;

		return $this;
	}

	/**
	 * Retrieve input element attributes.
	 *
	 * @return array Input attribute map.
	 * @since 1.0.0
	 * @noinspection PhpUnused
	 */
	public function getInputAttrs(): array {
		return $this->input_attrs;
	}

	/**
	 * Set input element attributes.
	 *
	 * Merges provided attributes with a default `name` that matches the meta key.
	 *
	 * @param array $input_attrs Input attribute map.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setInputAttrs( array $input_attrs ): ObjectMeta {
		$this->input_attrs = wp_parse_args( $input_attrs, array(
			'name' => $this->getMetaKey(),
		) );

		return $this;
	}

	/**
	 * Retrieve the field choice list.
	 *
	 * @return array Choice map.
	 * @since 1.0.0
	 * @noinspection PhpUnused
	 */
	public function getChoices(): array {
		return $this->choices;
	}

	/**
	 * Set the field choice list.
	 *
	 * @param array $choices Choice map.
	 *
	 * @return ObjectMeta Current instance for method chaining.
	 * @since 1.0.0
	 */
	public function setChoices( array $choices ): ObjectMeta {
		$this->choices = $choices;

		return $this;
	}

	/**
	 * Render the field UI for the current object.
	 *
	 * Resolves a supported object ID from the provided hook argument and invokes
	 * the configured display callback with the object ID and this metadata
	 * instance.
	 *
	 * @param mixed $object Hook argument containing an object ID or supported WordPress object.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function display( mixed $object ): void {
		if ( ! $this->is_callable( $callback = $this->getDisplayCallback() ) ) {
			return;
		}
		if ( is_int( $object ) ) {
			$id = absint( $object );
		} elseif ( is_object( $object ) ) {
			$id = match ( get_class( $object ) ) {
				'WP_Comment' => $object->comment_ID,
				'WP_Post', 'WP_User' => $object->ID,
				'WP_Term' => $object->term_id,
				default => 0,
			};
		} else {
			$id = 0;
		}

		// call the provided callback with the resolved object ID and this metadata instance
		call_user_func_array( $callback, array( $id, $this ) );

		// mark this field as participating in the submitted form (for save/delete in the save() method)
		printf(
			'<input type="hidden" name="%1$s" value="1">',
			esc_attr( $this->getMetaKey() . self::DISPLAYED_NAME_SUFFIX )
		);
	}

	/**
	 * Register the metadata definition with WordPress.
	 *
	 * Registers once for the base object type when no subtype is configured, or
	 * once per subtype when restrictions are present.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register(): void {
		if ( $this->isEmpty( $subtypes = $this->getObjectSubtype() ) ) {
			register_meta(
				$this->getObjectType(),
				$this->getMetaKey(),
				array(
					'object_subtype'    => '',
					'type'              => $this->getType(),
					'description'       => $this->getDescription(),
					'single'            => $this->isSingle(),
					'sanitize_callback' => $this->getSanitizeCallback(),
					'auth_callback'     => $this->getAuthCallback(),
					'show_in_rest'      => $this->getShowInRest(),
				)
			);
		} else {
			foreach ( (array) $subtypes as $subtype ) {
				register_meta(
					$this->getObjectType(),
					$this->getMetaKey(),
					array(
						'object_subtype'    => $subtype,
						'type'              => $this->getType(),
						'description'       => $this->getDescription(),
						'single'            => $this->isSingle(),
						'sanitize_callback' => $this->getSanitizeCallback(),
						'auth_callback'     => $this->getAuthCallback(),
						'show_in_rest'      => $this->getShowInRest(),
					)
				);
			}
		}
	}

	/**
	 * Conditionally persist submitted metadata for an object.
	 *
	 * @param int $id Target object ID from the save hook.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function maybeSave( int $id = 0 ): void {
		if ( $this->canSave( $id ) ) {
			$this->save( $id );
		}
	}

	/**
	 * Add a metadata value for an object.
	 *
	 * @param int $id Target object ID.
	 * @param mixed $value Metadata value to add.
	 *
	 * @return bool|int Metadata row ID on success or false on failure.
	 * @since 1.0.0
	 */
	public function add( int $id, mixed $value ): bool|int {
		return add_metadata(
			$this->getObjectType(),
			$id,
			$this->getMetaKey(),
			$value,
			$this->isSingle()
		);
	}

	/**
	 * Update a metadata value for an object.
	 *
	 * @param int $id Target object ID.
	 * @param mixed $value New metadata value.
	 * @param mixed|string $previous Optional previous value constraint.
	 *
	 * @return bool|int True when updated, metadata ID when added, or false on failure.
	 * @since 1.0.0
	 */
	public function update( int $id, mixed $value, mixed $previous = '' ): bool|int {
		return update_metadata(
			$this->getObjectType(),
			$id,
			$this->getMetaKey(),
			$value,
			$previous
		);
	}

	/**
	 * Retrieve metadata for an object.
	 *
	 * The return shape depends on the `single` setting.
	 *
	 * @param int $id Target object ID.
	 *
	 * @return mixed Metadata value or values returned by WordPress.
	 * @since 1.0.0
	 */
	public function get( int $id ): mixed {
		return get_metadata(
			$this->getObjectType(),
			$id,
			$this->getMetaKey(),
			$this->isSingle()
		);
	}

	/**
	 * Delete metadata from an object.
	 *
	 * @param int $id Target object ID.
	 * @param mixed|string $value Optional value constraint.
	 * @param bool $all Whether to delete data from all objects matching the value.
	 *
	 * @return bool True on success, false on failure.
	 * @since 1.0.0
	 */
	public function delete( int $id, mixed $value = '', bool $all = false ): bool {
		return delete_metadata(
			$this->getObjectType(),
			$id,
			$this->getMetaKey(),
			$value,
			$all
		);
	}

	/**
	 * Determine whether metadata exists for an object.
	 *
	 * @param mixed $id Target object ID.
	 *
	 * @return bool True when the metadata key exists for the object.
	 * @since 1.0.0
	 */
	public function exists( mixed $id ): bool {
		return metadata_exists(
			$this->getObjectType(),
			$id,
			$this->getMetaKey()
		);
	}

	/**
	 * Persist submitted request data to the current metadata key.
	 *
	 * Single-value metadata is updated or deleted directly. Multi-value metadata
	 * is synchronized by removing missing values and adding newly submitted
	 * values.
	 *
	 * @param int $id Target object ID.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	protected function save( int $id = 0 ): void {
		$key = $this->getMetaKey();

		// skip persistence when this field was not rendered in the submitted form
		if ( ! array_key_exists( $key . self::DISPLAYED_NAME_SUFFIX, $_REQUEST ) ) {
			return;
		}

		$new = $_REQUEST[ $key ] ?? null;
		if ( '' === $new ) {
			$new = null;
		}

		// grab the current metadata value(s) for comparison and synchronization
		$old = $this->get( $id );

		if ( $this->isSingle() ) {
			is_null( $new ) ? $this->delete( $id ) : $this->update( $id, $new, $old );
		} else {
			$old = (array) $old;
			$new = array_filter( (array) $new );

			foreach ( array_diff( $old, $new ) as $to_remove ) {
				$this->delete( $id, $to_remove );
			}

			foreach ( array_diff( $new, $old ) as $to_add ) {
				$this->add( $id, $to_add );
			}
		}
	}

	/**
	 * Retrieve the save hook for the configured object type.
	 *
	 * @return string WordPress action name used for save handling.
	 * @since 1.0.0
	 */
	protected function getSaveHook(): string {
		$hooks = $this->getObjectTypesMap();

		return $hooks[ $this->getObjectType() ];
	}

	/**
	 * Return allowed metadata value types.
	 *
	 * @return string[] Supported `register_meta()` type values.
	 * @since 1.0.0
	 */
	protected function getAllowedTypes(): array {
		return array(
			'string',
			'boolean',
			'integer',
			'number',
			'array',
			'object',
		);
	}

	/**
	 * Return allowed WordPress object types.
	 *
	 * @return string[] Supported object type slugs.
	 * @since 1.0.0
	 */
	protected function getAllowedObjectTypes(): array {
		return array_keys( $this->getObjectTypesMap() );
	}

	/**
	 * Map object types to the WordPress save hooks they use.
	 *
	 * @return string[] Object-type-to-save-hook map.
	 * @since 1.0.0
	 */
	protected function getObjectTypesMap(): array {
		return array(
			'comment' => 'edit_comment',
			'post'    => 'save_post',
			'term'    => 'edited_terms',
			'user'    => 'profile_update',
		);
	}

	/**
	 * Determine whether the current request is allowed to save metadata.
	 *
	 * Rejects auto-saves and requires a valid object-specific nonce field.
	 *
	 * @param int $id Target object ID.
	 *
	 * @return bool True when the current request may persist metadata.
	 * @since 1.0.0
	 */
	protected function canSave( int $id = 0 ): bool {
		return (
			! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			&& (
				isset( $_REQUEST['_wpnonce'] )
				&& wp_verify_nonce( $_REQUEST['_wpnonce'], $this->getNonce( $id ) )
			)
		);
	}

	/**
	 * Build the nonce action string for the configured object type and ID.
	 *
	 * @param int $id Target object ID.
	 *
	 * @return string Nonce action string expected by `wp_verify_nonce()`.
	 * @since 1.0.0
	 */
	protected function getNonce( int $id = 0 ): string {
		$nonces = $this->getNoncesMap();

		return sprintf( $nonces[ $this->getObjectType() ], $id );
	}

	/**
	 * Map object types to their expected nonce action patterns.
	 *
	 * @return string[] Object-type-to-nonce-pattern map.
	 * @since 1.0.0
	 */
	protected function getNoncesMap(): array {
		return array(
			'comment' => 'update-comment_%d',
			'post'    => 'update-post_%d',
			'term'    => 'update-tag_%d',
			'user'    => 'update-user_%d',
		);
	}

	/**
	 * Convert an arbitrary string into a lowercase hyphenated identifier.
	 *
	 * Used to derive the default field ID from the meta key.
	 *
	 * @param string $string Source string.
	 *
	 * @return string Hyphenated identifier.
	 * @since 1.0.0
	 */
	protected function hyphenize( string $string = '' ): string {
		return trim(
			preg_replace( '/([^a-z0-9-]+)/', '-', strtolower( trim( $string ) ) ),
			'-'
		);
	}

	/**
	 * Return default configuration values for object metadata definitions.
	 *
	 * @return array Default constructor arguments.
	 * @since 1.0.0
	 */
	protected function getDefaults(): array {
		return array(
			'id'                => '',
			'meta_key'          => '',
			'object_type'       => 'post',
			'object_subtype'    => array(),
			'type'              => 'string',
			'description'       => '',
			'single'            => false,
			'sanitize_callback' => null,
			'auth_callback'     => null,
			'show_in_rest'      => false,
			'display_hook'      => '',
			'display_callback'  => null,
			'label'             => '',
			'help'              => '',
			'input_attrs'       => array(),
			'choices'           => array(),
			'scripts'           => array(),
			'styles'            => array(),
		);
	}

}
