<?php


namespace SergeLiatko\WPMeta;

use Closure;
use SergeLiatko\WPMeta\Interfaces\HasId;
use SergeLiatko\WPMeta\Traits\IsCallable;
use SergeLiatko\WPMeta\Traits\IsEmpty;

/**
 * Class CallbackField
 *
 * Displays callback in hook used to display ObjectMeta field.
 *
 * @package SergeLiatko\WPMeta
 */
class CallbackField implements HasId {

	use IsCallable, IsEmpty;

	/**
	 * @var string $id
	 */
	protected string $id;

	/**
	 * @var string $display_hook
	 */
	protected string $display_hook;

	/**
	 * @var Closure|callable|string|array|null $display_callback Must accept not more than 2 parameters.
	 */
	protected $display_callback;

	/**
	 * CallbackField constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string $id
		 * @var string $display_hook
		 * @var Closure|callable|string|array|null $display_callback
		 */
		extract( wp_parse_args( $args, array(
			'id'               => '',
			'display_hook'     => '',
			'display_callback' => null,
		) ) );
		$this->setId( $id );
		$this->setDisplayHook( $display_hook );
		$this->setDisplayCallback( $display_callback );
		if ( ! $this->isEmpty( $hook = $this->getDisplayHook() ) && ! $this->isEmpty( $this->getDisplayCallback() ) ) {
			add_action( $hook, array( $this, 'display' ) );
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
	 * @return CallbackField
	 */
	public function setId( string $id ): CallbackField {
		$this->id = sanitize_key( $id );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayHook(): string {
		return $this->display_hook;
	}

	/**
	 * @param string $display_hook
	 *
	 * @return CallbackField
	 */
	public function setDisplayHook( string $display_hook ): CallbackField {
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
	 * @return CallbackField
	 */
	public function setDisplayCallback( callable|array|string|Closure|null $display_callback ): CallbackField {
		$this->display_callback = $this->is_callable( $display_callback ) ? $display_callback : null;

		return $this;
	}

	/**
	 * @param mixed|null $object
	 */
	public function display( mixed $object = null ): void {
		if ( $this->is_callable( $callback = $this->getDisplayCallback() ) ) {
			call_user_func( $callback, $object );
		}
	}

}
