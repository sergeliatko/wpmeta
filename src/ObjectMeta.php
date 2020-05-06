<?php


namespace SergeLiatko\WPMeta;


use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Traits\IsEmpty;
use SergeLiatko\WPMeta\Traits\ParseArgsRecursive;

/**
 * Class ObjectMeta
 *
 * @package SergeLiatko\WPMeta
 */
class ObjectMeta implements HasId {

	use IsEmpty, ParseArgsRecursive;

	/**
	 * @var string $id
	 */
	protected $id;

	/**
	 * @var string $meta_key
	 */
	protected $meta_key;

	/**
	 * @var string $object_type
	 */
	protected $object_type;

	/**
	 * @var string|string[] $object_subtype
	 */
	protected $object_subtype;

	/**
	 * @var string $type
	 */
	protected $type;

	/**
	 * @var string $description
	 */
	protected $description;

	/**
	 * @var bool $single
	 */
	protected $single;

	/**
	 * @var callable|null $sanitize_callback
	 */
	protected $sanitize_callback;

	/**
	 * @var callable|null $auth_callback
	 */
	protected $auth_callback;

	/**
	 * @var bool|array $show_in_rest
	 */
	protected $show_in_rest;

	/**
	 * @var string[]|string $display_hook
	 */
	protected $display_hook;

	/**
	 * @var callable|null $display_callback
	 */
	protected $display_callback;

	/**
	 * @var string $label
	 */
	protected $label;

	/**
	 * @var string $help
	 */
	protected $help;

	/**
	 * @var array $input_attrs
	 */
	protected $input_attrs;

	/**
	 * @var array $choices
	 */
	protected $choices;

	/**
	 * ObjectMeta constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string          $id
		 * @var string          $meta_key
		 * @var string          $object_type
		 * @var string|string[] $object_subtype
		 * @var string          $type
		 * @var string          $description
		 * @var bool            $single
		 * @var callable|null   $sanitize_callback
		 * @var callable|null   $auth_callback
		 * @var array|bool      $show_in_rest
		 * @var string          $display_hook
		 * @var callable|null   $display_callback
		 * @var string          $label
		 * @var string          $help
		 * @var array           $input_attrs
		 * @var array           $choices
		 */
		extract( $this->parseArgsRecursive( $args, $this->getDefaults() ), EXTR_OVERWRITE );
		$this->setId( $id );
		$this->setMetaKey( $meta_key );
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
		//proceed only if meta key is not empty
		if ( ! $this->isEmpty( $this->getMetaKey() ) ) {
			//register meta data
			add_action( 'init', array( $this, 'register' ), 10, 0 );
			//save meta data
			add_action( $this->getSaveHook(), array( $this, 'maybeSave' ), 10, 1 );
			//maybe display the field in UI
			if (
				! $this->isEmpty( $this->getDisplayCallback() )
				&& ! $this->isEmpty( $hooks = $this->getDisplayHook() )
			) {
				foreach ( (array) $hooks as $hook ) {
					add_action( $hook, array( $this, 'display' ), 10, 1 );
				}
			}
		}
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
	 * @return string|string[]
	 */
	public function getObjectSubtype() {
		return $this->object_subtype;
	}

	/**
	 * @param string|string[] $object_subtype
	 *
	 * @return ObjectMeta
	 */
	public function setObjectSubtype( $object_subtype ) {
		$this->object_subtype = $object_subtype;

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
	 * @return callable|null
	 */
	public function getSanitizeCallback() {
		return $this->sanitize_callback;
	}

	/**
	 * @param callable|null $sanitize_callback
	 *
	 * @return ObjectMeta
	 */
	public function setSanitizeCallback( $sanitize_callback ): ObjectMeta {
		$this->sanitize_callback = $sanitize_callback;

		return $this;
	}

	/**
	 * @return callable|null
	 */
	public function getAuthCallback() {
		return $this->auth_callback;
	}

	/**
	 * @param callable|null $auth_callback
	 *
	 * @return ObjectMeta
	 */
	public function setAuthCallback( $auth_callback ): ObjectMeta {
		$this->auth_callback = $auth_callback;

		return $this;
	}

	/**
	 * @return array|bool
	 */
	public function getShowInRest() {
		return $this->show_in_rest;
	}

	/**
	 * @param array|bool $show_in_rest
	 *
	 * @return ObjectMeta
	 */
	public function setShowInRest( $show_in_rest ) {
		$show_in_rest       = ( is_array( $show_in_rest ) || is_bool( $show_in_rest ) ) ? $show_in_rest : false;
		$this->show_in_rest = $show_in_rest;

		return $this;
	}

	/**
	 * @return string[]|string
	 */
	public function getDisplayHook(): string {
		return $this->display_hook;
	}

	/**
	 * @param string[]|string $display_hook
	 *
	 * @return ObjectMeta
	 */
	public function setDisplayHook( $display_hook ): ObjectMeta {
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
	 * @return callable|null
	 */
	public function getDisplayCallback() {
		return $this->display_callback;
	}

	/**
	 * @param callable|null $display_callback
	 *
	 * @return ObjectMeta
	 */
	public function setDisplayCallback( $display_callback ): ObjectMeta {
		$this->display_callback = $display_callback;

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
	public function display( $object ) {
		if ( ! is_callable( $callback = $this->getDisplayCallback() ) ) {
			return;
		}
		if ( is_int( $object ) ) {
			$id = absint( $object );
		} elseif ( is_object( $object ) ) {
			switch ( get_class( $object ) ) {
				case 'WP_Comment':
					/** @var \WP_Comment $object */
					$id = $object->comment_ID;
					break;
				case 'WP_Post':
					/** @var \WP_Post $object */
					$id = $object->ID;
					break;
				case 'WP_Term':
					/** @var \WP_Term $object */
					$id = $object->term_id;
					break;
				case 'WP_User':
					/** @var \WP_User $object */
					$id = $object->ID;
					break;
				default:
					$id = 0;
					break;
			}
		} else {
			$id = 0;
		}
		//call the provided callback with 2 parameters, object ID and ObjectMeta instance.
		call_user_func_array( $callback, array( $id, $this ) );
	}

	/**
	 * Registers meta in WordPress.
	 */
	public function register() {
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
	public function maybeSave( $id = 0 ) {
		if ( $this->canSave( $id ) ) {
			$this->save( $id );
		}
	}

	/**
	 * @param int   $id
	 * @param mixed $value
	 *
	 * @return bool|false|int
	 */
	public function add( $id, $value ) {
		return add_metadata(
			$this->getObjectType(),
			$id,
			$this->getMetaKey(),
			$value,
			$this->isSingle()
		);
	}

	/**
	 * @param int   $id
	 * @param mixed $value
	 * @param mixed $previous
	 *
	 * @return bool|false|int
	 */
	public function update( $id, $value, $previous = '' ) {
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
	public function get( $id ) {
		return get_metadata(
			$this->getObjectType(),
			$id,
			$this->getMetaKey(),
			$this->isSingle()
		);
	}

	/**
	 * @param int   $id
	 * @param mixed $value
	 * @param bool  $all
	 *
	 * @return bool
	 */
	public function delete( $id, $value = '', $all = false ) {
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
	public function exists( $id ) {
		return metadata_exists(
			$this->getObjectType(),
			$id,
			$this->getMetaKey()
		);
	}

	/**
	 * @param int $id
	 */
	protected function save( $id = 0 ) {
		$single = $this->isSingle();
		$key    = $this->getMetaKey();
		$new    = isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : null;
		$old    = $this->get( $id );
		// update single value
		if ( $single ) {
			empty( $new ) ? $this->delete( $id ) : $this->update( $id, $new, $old );
		} else {
			// update multiple meta values
			$old = (array) $old;
			$new = (array) $new;
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
	protected function getSaveHook() {
		$hooks = $this->getObjectTypesMap();

		return $hooks[ $this->getObjectType() ];
	}

	/**
	 * @return string[]
	 */
	protected function getAllowedTypes() {
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
	protected function getAllowedObjectTypes() {
		return array_keys( $this->getObjectTypesMap() );
	}

	/**
	 * @return string[]
	 */
	protected function getObjectTypesMap() {
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
	protected function canSave( $id = 0 ) {
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
	protected function getNonce( $id = 0 ) {
		$nonces = $this->getNoncesMap();

		return sprintf( $nonces[ $this->getObjectType() ], $id );
	}

	/**
	 * @return string[]
	 */
	protected function getNoncesMap() {
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
	protected function hyphenize( $string = '' ) {
		return trim(
			preg_replace( '/([^a-z0-9-]+)/', '-', strtolower( trim( $string ) ) ),
			'-'
		);
	}

	/**
	 * @return array
	 */
	protected function getDefaults() {
		return array(
			'id'                => '',
			'meta_key'          => '',
			'object_type'       => 'post',
			'object_subtype'    => '',
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
		);
	}
}
