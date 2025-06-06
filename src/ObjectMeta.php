<?php


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
 * @package SergeLiatko\WPMeta
 */
class ObjectMeta implements HasId, HasSupportedPostTypes {

	use IsCallable, ParseArgsRecursive, PostScriptsSupport;

	/**
	 * @var string $id
	 */
	protected string $id;

	/**
	 * @var string $meta_key
	 */
	protected string $meta_key;

	/**
	 * @var string $object_type
	 */
	protected string $object_type;

	/**
	 * @var array|string|string[] $object_subtype
	 */
	protected string|array $object_subtype;

	/**
	 * @var string $type
	 */
	protected string $type;

	/**
	 * @var string $description
	 */
	protected string $description;

	/**
	 * @var bool $single
	 */
	protected bool $single;

	/**
	 * @var Closure|callable|string|array|null $sanitize_callback
	 */
	protected $sanitize_callback;

	/**
	 * @var Closure|callable|string|array|null $auth_callback
	 */
	protected $auth_callback;

	/**
	 * @var bool|array $show_in_rest
	 */
	protected array|bool $show_in_rest;

	/**
	 * @var string[]|string $display_hook
	 */
	protected string|array $display_hook;

	/**
	 * @var Closure|callable|string|array|null $display_callback
	 */
	protected $display_callback;

	/**
	 * @var string $label
	 */
	protected string $label;

	/**
	 * @var string $help
	 */
	protected string $help;

	/**
	 * @var array $input_attrs
	 */
	protected array $input_attrs;

	/**
	 * @var array $choices
	 */
	protected array $choices;

	/**
	 * ObjectMeta constructor.
	 *
	 * @param array $args
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
		 * @var string $display_hook
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
		//proceed only if meta key is not empty
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
			//register meta data
			add_action( 'init', array( $this, 'register' ), 20, 0 );
			//save meta data
			add_action( $this->getSaveHook(), array( $this, 'maybeSave' ) );
			//maybe display the field in UI
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

	public function getSupportedPostTypes(): array {
		return ( 'post' === $this->getObjectType() ) ? (array) $this->getObjectSubtype() : array();
	}


	/**
	 * @return string
	 */
	public function getId(): string {
		if ( empty( $this->id ) ) {
			$this->setId( $this->hyphenize( $this->getMetaKey() ) );
		}

		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return ObjectMeta
	 */
	public function setId( string $id ): ObjectMeta {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMetaKey(): string {
		return $this->meta_key;
	}

	/**
	 * @param string $meta_key
	 *
	 * @return ObjectMeta
	 */
	public function setMetaKey( string $meta_key ): ObjectMeta {
		$this->meta_key = sanitize_key( $meta_key );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getObjectType(): string {
		return $this->object_type;
	}

	/**
	 * @param string $object_type
	 *
	 * @return ObjectMeta
	 */
	public function setObjectType( string $object_type ): ObjectMeta {
		$object_type       = in_array( $object_type, $this->getAllowedObjectTypes() ) ? $object_type : 'post';
		$this->object_type = $object_type;

		return $this;
	}

	/**
	 * @return array|string|string[]
	 */
	public function getObjectSubtype(): array|string {
		return $this->object_subtype;
	}

	/**
	 * @param array|string|string[] $object_subtype
	 *
	 * @return ObjectMeta
	 */
	public function setObjectSubtype( array|string $object_subtype ): ObjectMeta {
		if ( ! is_array( $object_subtype ) ) {
			$object_subtype = array( $object_subtype );
		}
		$this->object_subtype = array_filter( array_map( 'sanitize_key', $object_subtype ) );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @param string $type
	 *
	 * @return ObjectMeta
	 */
	public function setType( string $type ): ObjectMeta {
		$type       = in_array( $type, $this->getAllowedTypes() ) ? $type : 'string';
		$this->type = $type;

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
	 * @return ObjectMeta
	 */
	public function setDescription( string $description ): ObjectMeta {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSingle(): bool {
		return $this->single;
	}

	/**
	 * @param bool $single
	 *
	 * @return ObjectMeta
	 */
	public function setSingle( bool $single ): ObjectMeta {
		$this->single = $single;

		return $this;
	}

	/**
	 * @return Closure|callable|string|array|null
	 */
	public function getSanitizeCallback(): callable|array|string|Closure|null {
		return $this->sanitize_callback;
	}

	/**
	 * @param callable|array|string|Closure|null $sanitize_callback
	 *
	 * @return ObjectMeta
	 */
	public function setSanitizeCallback( callable|array|string|Closure|null $sanitize_callback ): ObjectMeta {
		$this->sanitize_callback = $this->is_callable( $sanitize_callback ) ? $sanitize_callback : null;

		return $this;
	}

	/**
	 * @return Closure|callable|string|array|null
	 */
	public function getAuthCallback(): callable|array|string|Closure|null {
		return $this->auth_callback;
	}

	/**
	 * @param callable|array|string|Closure|null $auth_callback
	 *
	 * @return ObjectMeta
	 */
	public function setAuthCallback( callable|array|string|Closure|null $auth_callback ): ObjectMeta {
		$this->auth_callback = $this->is_callable( $auth_callback ) ? $auth_callback : null;

		return $this;
	}

	/**
	 * @return array|bool
	 */
	public function getShowInRest(): bool|array {
		return $this->show_in_rest;
	}

	/**
	 * @param bool|array $show_in_rest
	 *
	 * @return ObjectMeta
	 */
	public function setShowInRest( bool|array $show_in_rest ): ObjectMeta {
		$this->show_in_rest = $show_in_rest;

		return $this;
	}

	/**
	 * @return string[]|string
	 */
	public function getDisplayHook(): array|string {
		return $this->display_hook;
	}

	/**
	 * @param string|string[] $display_hook
	 *
	 * @return ObjectMeta
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
	 * @return Closure|callable|string|array|null
	 */
	public function getDisplayCallback(): callable|array|string|Closure|null {
		return $this->display_callback;
	}

	/**
	 * @param callable|array|string|Closure|null $display_callback
	 *
	 * @return ObjectMeta
	 */
	public function setDisplayCallback( callable|array|string|Closure|null $display_callback ): ObjectMeta {
		$this->display_callback = $this->is_callable( $display_callback ) ? $display_callback : null;

		return $this;
	}

	/**
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * @param string $label
	 *
	 * @return ObjectMeta
	 */
	public function setLabel( string $label ): ObjectMeta {
		$this->label = $label;

		return $this;
	}

	/**
	 * @return string
	 * @noinspection PhpUnused
	 */
	public function getHelp(): string {
		return $this->help;
	}

	/**
	 * @param string $help
	 *
	 * @return ObjectMeta
	 */
	public function setHelp( string $help ): ObjectMeta {
		$this->help = $help;

		return $this;
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function getInputAttrs(): array {
		return $this->input_attrs;
	}

	/**
	 * @param array $input_attrs
	 *
	 * @return ObjectMeta
	 */
	public function setInputAttrs( array $input_attrs ): ObjectMeta {
		$this->input_attrs = wp_parse_args( $input_attrs, array(
			'name' => $this->getMetaKey(),
		) );

		return $this;
	}

	/**
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function getChoices(): array {
		return $this->choices;
	}

	/**
	 * @param array $choices
	 *
	 * @return ObjectMeta
	 */
	public function setChoices( array $choices ): ObjectMeta {
		$this->choices = $choices;

		return $this;
	}

	/**
	 * @param mixed $object
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
				'WP_Post' => $object->ID,
				'WP_Term' => $object->term_id,
				'WP_User' => $object->ID,
				default => 0,
			};
		} else {
			$id = 0;
		}
		//call the provided callback with 2 parameters, object ID and ObjectMeta instance.
		call_user_func_array( $callback, array( $id, $this ) );
	}

	/**
	 * Registers meta in WordPress.
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
	 * @param int $id
	 */
	public function maybeSave( int $id = 0 ): void {
		if ( $this->canSave( $id ) ) {
			$this->save( $id );
		}
	}

	/**
	 * @param int $id
	 * @param mixed $value
	 *
	 * @return bool|int
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
	 * @param int $id
	 * @param mixed $value
	 * @param mixed|string $previous
	 *
	 * @return bool|int
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
	 * @param int $id
	 *
	 * @return mixed
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
	 * @param int $id
	 * @param mixed|string $value
	 * @param bool $all
	 *
	 * @return bool
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
	 * @param mixed $id
	 *
	 * @return bool
	 */
	public function exists( mixed $id ): bool {
		return metadata_exists(
			$this->getObjectType(),
			$id,
			$this->getMetaKey()
		);
	}

	/**
	 * @param int $id
	 */
	protected function save( int $id = 0 ): void {
		$single = $this->isSingle();
		$key    = $this->getMetaKey();
		$new    = $_REQUEST[ $key ] ?? null;
		$old    = $this->get( $id );
		// update single value
		if ( $single ) {
			empty( $new ) ? $this->delete( $id ) : $this->update( $id, $new, $old );
		} else {
			// update multiple meta values
			$old = (array) $old;
			$new = array_filter( (array) $new );
			// remove old that are not in new
			foreach ( array_diff( $old, $new ) as $to_remove ) {
				$this->delete( $id, $to_remove );
			}
			// add new that are not in old
			foreach ( array_diff( $new, $old ) as $to_add ) {
				$this->add( $id, $to_add );
			}
		}
	}

	/**
	 * @return string
	 */
	protected function getSaveHook(): string {
		$hooks = $this->getObjectTypesMap();

		return $hooks[ $this->getObjectType() ];
	}

	/**
	 * @return string[]
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
	 * @return string[]
	 */
	protected function getAllowedObjectTypes(): array {
		return array_keys( $this->getObjectTypesMap() );
	}

	/**
	 * @return string[]
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
	 * @param int $id
	 *
	 * @return bool
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
	 * @param int $id
	 *
	 * @return string
	 */
	protected function getNonce( int $id = 0 ): string {
		$nonces = $this->getNoncesMap();

		return sprintf( $nonces[ $this->getObjectType() ], $id );
	}

	/**
	 * @return string[]
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
	 * @param string $string
	 *
	 * @return string
	 */
	protected function hyphenize( string $string = '' ): string {
		return trim(
			preg_replace( '/([^a-z0-9-]+)/', '-', strtolower( trim( $string ) ) ),
			'-'
		);
	}

	/**
	 * @return array
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
