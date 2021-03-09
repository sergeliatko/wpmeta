<?php


namespace SergeLiatko\WPMeta;

use SergeLiatko\WPMeta\Traits\IsCallable;
use SergeLiatko\WPMeta\Traits\IsEmpty;

/**
 * Class CallbackField
 *
 * Displays callback in hook used to display ObjectMeta field.
 *
 * @package SergeLiatko\WPMeta
 */
class CallbackField {

	use IsCallable, IsEmpty;

	/**
	 * @var string $display_hook
	 */
	protected $display_hook;

	/**
	 * @var \Closure|callable|string|array|null $display_callback
	 */
	protected $display_callback;

	/**
	 * CallbackField constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string                              $display_hook
		 * @var \Closure|callable|string|array|null $display_callback
		 */
		extract( wp_parse_args( $args, array(
			'display_hook'     => '',
			'display_callback' => null,
		) ), EXTR_OVERWRITE );
		$this->setDisplayHook( $display_hook );
		$this->setDisplayCallback( $display_callback );
		if ( !$this->isEmpty( $hook = $this->getDisplayHook() ) && !$this->isEmpty( $this->getDisplayCallback() ) ) {
			add_action( $hook, array( $this, 'display' ), 10, 1 );
		}
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
	 * @return \Closure|callable|string|array|null
	 */
	public function getDisplayCallback(): ?callable {
		return $this->display_callback;
	}

	/**
	 * @param \Closure|callable|string|array|null $display_callback
	 *
	 * @return CallbackField
	 */
	public function setDisplayCallback( $display_callback ): CallbackField {
		$this->display_callback = $this->is_callable( $display_callback ) ? $display_callback : null;

		return $this;
	}

	/**
	 * @param mixed $object
	 */
	public function display( $object ) {
		if ( $this->is_callable( $callback = $this->getDisplayCallback() ) ) {
			call_user_func( $callback, $object );
		}
	}

}
