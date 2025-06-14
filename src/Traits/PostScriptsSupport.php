<?php


namespace SergeLiatko\WPMeta\Traits;

use WP_Screen;

/**
 * Trait PostScriptsSupport
 *
 * @package SergeLiatko\WPMeta\Traits
 */
trait PostScriptsSupport {

	use IsEmpty;

	/**
	 * @var array|array[]|string[]
	 */
	protected array $scripts = [];

	/**
	 * @var array|array[]|string[]
	 */
	protected array $styles = [];

	/**
	 * @var bool $enqueued_scripts
	 */
	protected bool $enqueued_scripts = false;

	/**
	 * @return array|array[]|string[]
	 */
	public function getScripts(): array {
		return $this->scripts;
	}

	/**
	 * @param array|array[]|string[] $scripts
	 *
	 * @return static
	 */
	public function setScripts( array $scripts ): static {
		$this->scripts = $this->filterScripts( $scripts );
		if ( ! empty( $this->scripts ) ) {
			$this->maybeEnqueueScripts();
		}

		return $this;
	}

	/**
	 * @return array|array[]|string[]
	 */
	public function getStyles(): array {
		return $this->styles;
	}

	/**
	 * @param array|array[]|string[] $styles
	 *
	 * @return static
	 */
	public function setStyles( array $styles ): static {
		$this->styles = empty( $styles ) ? array() : $this->filterScripts( $styles, 'style' );
		if ( ! empty( $this->styles ) ) {
			$this->maybeEnqueueScripts();
		}

		return $this;
	}

	/**
	 * @return bool|null
	 */
	public function isEnqueuedScripts(): ?bool {
		return $this->enqueued_scripts;
	}

	/**
	 * @param bool $enqueued_scripts
	 *
	 * @return static
	 */
	public function setEnqueuedScripts( bool $enqueued_scripts ): static {
		$this->enqueued_scripts = $enqueued_scripts;

		return $this;
	}

	/**
	 * @param string $hook
	 *
	 * @noinspection PhpUnused
	 */
	public function enqueueScripts( string $hook ): void {
		//proceed only on screens related to post editing
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			//do we have specific post types to act on?
			if ( $this->supportsPostTypes() ) {
				$screen = get_current_screen();
				if (
					( $screen instanceof WP_Screen )
					&& isset( $screen->post_type )
					&& in_array( $screen->post_type, $this->getSupportedPostTypes() )
				) {
					//enqueue scripts for this post type
					$this->addScripts();
				}
			} else {
				//enqueue scripts for all post types
				$this->addScripts();
			}
		}
	}

	/**
	 * Enqueues scripts and styles in WordPress.
	 */
	protected function addScripts(): void {
		if ( ! $this->isEmpty( $scripts = $this->getScripts() ) ) {
			foreach ( $scripts as $script ) {
				if ( is_array( $script ) ) {
					$handle = $script['handle'];
					wp_enqueue_script(
						$handle,
						$script['src'],
						$script['deps'],
						$script['ver'],
						$script['in_footer']
					);
					//@developers: hook your wp_localize_script() functions here to localize the enqueued script
				} else {
					$handle = $script;
					wp_enqueue_script( $handle );
					//@developers: hook your wp_localize_script() functions here to localize the enqueued script
				}
				do_action( "admin_enqueued_script-$handle", $this );
			}
		}
		if ( ! $this->isEmpty( $styles = $this->getStyles() ) ) {
			foreach ( $styles as $style ) {
				if ( is_array( $style ) ) {
					$handle = $style['handle'];
					wp_enqueue_style(
						$handle,
						$style['src'],
						$style['deps'],
						$style['ver'],
						$style['media']
					);
					//@developers: hook your wp_localize_script() functions here to localize the enqueued script
				} else {
					$handle = $style;
					wp_enqueue_style( $handle );
					//@developers: hook your wp_localize_script() functions here to localize the enqueued script
				}
				do_action( "admin_enqueued_style-$handle", $this );
			}
		}
	}

	/**
	 * Checks if scripts were enqueued and enqueues them if not.
	 */
	protected function maybeEnqueueScripts(): void {
		if (
			$this->isEmpty( $this->isEnqueuedScripts() )
			&& (
				! $this->isEmpty( $this->getScripts() )
				|| ! $this->isEmpty( $this->getStyles() )
			)
		) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueueScripts' ) );
			$this->setEnqueuedScripts( true );
		}
	}

	/**
	 * @param array|array[]|string[] $scripts
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function filterScripts( array $scripts, string $type = 'script' ): array {
		$scripts = array_filter(
			$scripts,
			function ( $item ) {
				return ( ! empty( $item ) && ( is_array( $item ) || is_string( $item ) ) );
			}
		);
		array_walk( $scripts, function ( &$item ) use ( $type ) {
			if ( is_array( $item ) ) {
				switch ( $type ) {
					case 'script':
						$item = wp_parse_args( $item, array(
							'handle'    => '',
							'src'       => '',
							'deps'      => array(),
							'ver'       => false,
							'in_footer' => false,
						) );
						break;
					case 'style':
						$item = wp_parse_args( $item, array(
							'handle' => '',
							'src'    => '',
							'deps'   => array(),
							'ver'    => false,
							'media'  => 'all',
						) );
						break;
				}
				$item = empty( $item['handle'] ) ? null : $item;
			}
		} );

		return array_filter( $scripts );
	}

	/**
	 * @return bool
	 */
	protected function supportsPostTypes(): bool {
		return in_array(
			'\\SergeLiatko\\WPMeta\\Interfaces\\HasSupportedPostTypes',
			class_implements( $this )
		);
	}

}
