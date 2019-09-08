<?php
/**
 * Plugin Name: Auto Load Next Post: Beta Tester
 * Plugin URI: https://github.com/autoloadnextpost/alnp-beta-tester
 * Version: 3.0.0
 * Description: Run bleeding edge versions of Auto Load Next Post from the GitHub repo. This will replace your installed version of Auto Load Next Post with the latest tagged prerelease on GitHub - use with caution, and not on production sites. You have been warned.
 * Author: Auto Load Next Post
 * Author URI: https://autoloadnextpost.com
 * Developer: Sébastien Dumont
 * Developer URI: https://sebastiendumont.com
 * GitHub Plugin URI: https://github.com/autoloadnextpost/alnp-beta-tester
 *
 * Text Domain: alnp-beta-tester
 * Domain Path: /languages/
 *
 * Requires at least: 4.5
 * Tested up to: 5.2
 *
 * Based on WP_GitHub_Updater by Joachim Kudish.
 * Forked from WooCommerce Beta Tester by Mike Jolley and Claudio Sanches.
 *
 * Copyright: © 2019 Sébastien Dumont
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   Auto Load Next Post: Beta Tester
 * @author    Sébastien Dumont
 * @copyright Copyright © 2019, Sébastien Dumont
 * @license   GNU General Public License v3.0 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! class_exists( 'ALNP_Beta_Tester' ) ) {

	class ALNP_Beta_Tester {

		/**
		 * Plugin Configuration
		 *
		 * @access private
		 * @since  1.0.0
		 */
		private $config = array();

		/**
		 * GitHub Data
		 *
		 * @access protected
		 * @static
		 * @since  1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Plugin Version
		 *
		 * @access private
		 * @static
		 * @since  2.0.0
		 */
		private static $version = '3.0.0';

		/**
		 * Main Instance
		 *
		 * @access public
		 * @static
		 * @since  1.0.0
		 * @return ALNP_Beta_Tester - Main instance
		 */
		public static function instance() {
			return self::$_instance = is_null( self::$_instance ) ? new self() : self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @access public
		 * @since  2.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cloning this object is forbidden.', 'alnp-beta-tester' ), self::$version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @access public
		 * @since  2.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'alnp-beta-tester' ), self::$version );
		}

		/**
		 * Constructor
		 *
		 * @access  public
		 * @static
		 * @since   1.0.0
		 * @version 3.0.0
		 */
		public function __construct() {
			$this->config = array(
				'plugin_file'        => 'auto-load-next-post/auto-load-next-post.php',
				'slug'               => 'auto-load-next-post',
				'proper_folder_name' => 'auto-load-next-post',
				'api_url'            => 'https://api.github.com/repos/autoloadnextpost/auto-load-next-post',
				'requires'           => '4.5',
				'tested'             => '5.2.3',
				'requires_php'       => '5.6'
			);

			add_action( 'plugin_loaded', array( $this, 'flush_update_cache' ) );
			add_action( 'init', array( $this, 'check_alnp_installed' ) );
			add_action( 'init', array( $this, 'load_text_domain' ), 0 );
		} // END __construct()

		/**
		 * Run these filters once Auto Load Next Post is installed and active.
		 *
		 * @access public
		 * @return void
		 * @since  2.0.2
		 */
		public function alnp_active() {
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
			add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );

			// Auto update Auto Load Next Post.
			add_filter( 'auto_update_plugin', array( $this, 'auto_update_alnp' ), 100, 2 );
		} // END alnp_active()

		/**
		 * Load the plugin text domain once the plugin has initialized.
		 *
		 * @access public
		 * @since  2.0.0
		 * @return void
		 */
		public function load_text_domain() {
			load_plugin_textdomain( 'alnp-beta-tester', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		} // END load_text_domain()

		/**
		 * Run once the plugin has loaded to flush the update cache.
		 *
		 * @access public
		 * @static
		 * @since  2.0.0
		 */
		public static function flush_update_cache() {
			delete_site_transient( 'update_plugins' ); // Clear all plugin update data
			delete_site_transient( 'auto_load_next_post_latest_tag' ); // Previous beta tester value
		} // END flush_update_cache()

		/**
		 * Checks if Auto Load Next Post is installed before running filters for the WordPress updater.
		 *
		 * @access public
		 * @since  2.0.0
		 * @return bool|void
		 */
		public function check_alnp_installed() {
			if ( ! defined( 'AUTO_LOAD_NEXT_POST_VERSION' ) ) {
				add_action( 'admin_notices', array( $this, 'alnp_not_installed' ) );
				return false;
			}

			// Auto Load Next Post is active.
			$this->alnp_active();
		} // END check_alnp_installed()

		/**
		 * Auto Load Next Post is Not Installed Notice.
		 *
		 * @access  public
		 * @since   2.0.0
		 * @version 2.0.2
		 * @global  string $pagenow
		 * @return  void
		 */
		public function alnp_not_installed() {
			global $pagenow;

			if ( $pagenow == 'update.php' ) {
				return false;
			}

			echo '<div class="notice notice-error">';

				echo '<p>' . sprintf( __( '%1$s requires %2$s%3$s%4$s to be installed and activated in order to serve updates from GitHub.', 'alnp-beta-tester' ), esc_html__( 'Auto Load Next Post: Beta Tester', 'alnp-beta-tester' ), '<strong>', '</strong>', esc_html__( 'Auto Load Next Post', 'alnp-beta-tester' ) ) . '</p>';

				echo '<p>';

				if ( ! is_plugin_active( 'auto-load-next-post/auto-load-next-post.php' ) && current_user_can( 'activate_plugin', 'auto-load-next-post/auto-load-next-post.php' ) ) :

					echo '<a href="' . esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=auto-load-next-post/auto-load-next-post.php&plugin_status=active' ), 'activate-plugin_auto-load-next-post/auto-load-next-post.php' ) ) . '" class="button button-primary">' . sprintf( esc_html__( 'Activate %s', 'alnp-beta-tester' ), esc_html__( 'Auto Load Next Post', 'alnp-beta-tester' ) ) . '</a>';

				else :

					if ( current_user_can( 'install_plugins' ) ) {
						$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=auto-load-next-post' ), 'install-plugin_auto-load-next-post' );
					} else {
						$url = 'https://wordpress.org/plugins/auto-load-next-post/';
					}

					echo '<a href="' . esc_url( $url ) . '" class="button button-primary">' . sprintf( esc_html__( 'Install %s', 'alnp-beta-tester' ), esc_html__( 'Auto Load Next Post', 'alnp-beta-tester' ) ) . '</a>';

				endif;

				if ( current_user_can( 'deactivate_plugin', 'alnp-beta-tester/alnp-beta-tester.php' ) ) :
					echo '<a href="' . esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=alnp-beta-tester/alnp-beta-tester.php&plugin_status=inactive', 'deactivate-plugin_alnp-beta-tester/alnp-beta-tester.php' ) ) . '" class="button button-secondary">' . sprintf( esc_html__( 'Turn off %s plugin', 'alnp-beta-tester' ), esc_html__( 'Auto Load Next Post: Beta Tester', 'alnp-beta-tester' ) ) . '</a>';
				endif;

				echo '</p>';

			echo '</div>';
		} // END alnp_not_installed()

		/**
		 * Enable auto updates for Auto Load Next Post.
		 *
		 * @access  public
		 * @since   2.0.2
		 * @version 3.0.0
		 * @param   bool   $should_update Should this auto update.
		 * @param   object $plugin Plugin being checked.
		 * @return  bool
		 */
		public function auto_update_alnp( $should_update, $plugin ) {
			if ( ! isset( $plugin->slug ) ) {
				return $should_update;
			}

			if ( 'auto-load-next-post' === $plugin->slug ) {
				$should_update = true;
			}

			return $should_update;
		} // END auto_update_alnp()

		/**
		 * Update the required plugin data arguments.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @version 3.0.0
		 * @return  array
		 */
		public function set_update_args() {
			$plugin_data                  = $this->get_plugin_data();

			$this->config['plugin_name']  = 'Auto Load Next Post ' . $this->get_latest_prerelease();
			$this->config['description']  = $this->get_description();
			$this->config['version']      = $plugin_data['Version'];
			$this->config['author']       = $plugin_data['Author'];
			$this->config['homepage']     = $plugin_data['PluginURI'];
			$this->config['new_version']  = str_replace( 'v', '', $this->get_latest_prerelease() );
			$this->config['last_updated'] = $this->get_date();
			$this->config['changelog']    = $this->get_changelog();
			$this->config['zip_name']     = $this->get_latest_prerelease();
			$this->config['zip_url']      = 'https://github.com/autoloadnextpost/auto-load-next-post/archive/' . $this->config['zip_name'] . '.zip';
		} // END set_update_args()

		/**
		 * Check wether or not the transients need to be overruled
		 * and API needs to be called for every single page load.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return bool overrule or not
		 */
		public function overrule_transients() {
			return ( defined( 'ALNP_BETA_TESTER_FORCE_UPDATE' ) && ALNP_BETA_TESTER_FORCE_UPDATE );
		} // END overrule_transients()

		/**
		 * Get New Version from GitHub.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @version 2.0.0
		 * @return  int $tagged_version the version number
		 */
		public function get_latest_prerelease() {
			$tagged_version = get_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

			if ( $this->overrule_transients() || empty( $tagged_version ) ) {

				$raw_response = wp_remote_get( trailingslashit( $this->config['api_url'] ) . 'releases' );

				if ( is_wp_error( $raw_response ) ) {
					return false;
				}

				$releases       = json_decode( $raw_response['body'] );
				$tagged_version = false;

				if ( is_array( $releases ) ) {
					foreach ( $releases as $release ) {

						// If the release is a pre-release then return the tagged version.
						if ( $release->prerelease ) {
							$tagged_version = $release->tag_name;
							break;
						}
					}
				}

				// Refresh every 6 hours.
				if ( ! empty( $tagged_version ) ) {
					set_site_transient( md5( $this->config['slug'] ) . '_latest_tag', $tagged_version, 60*60*6 );
				}
			}

			return $tagged_version;
		} // END get_latest_prerelease()


		/**
		 * Get Published date of New Version from GitHub.
		 *
		 * @access public
		 * @since  2.0.2
		 * @return string $published_date of the latest prerelease
		 */
		public function get_latest_prerelease_date() {
			$published_date = get_site_transient( md5( $this->config['slug'] ) . '_latest_published_date' );

			if ( $this->overrule_transients() || empty( $published_date ) ) {

				$raw_response = wp_remote_get( trailingslashit( $this->config['api_url'] ) . 'releases' );

				if ( is_wp_error( $raw_response ) ) {
					return false;
				}

				$releases       = json_decode( $raw_response['body'] );
				$published_date = false;

				if ( is_array( $releases ) ) {
					foreach ( $releases as $release ) {

						// If the release is a pre-release then return the published date.
						if ( $release->prerelease ) {
							$published_date = $release->published_at;
							break;
						}
					}
				}

				// Refresh every 6 hours.
				if ( ! empty( $published_date ) ) {
					set_site_transient( md5( $this->config['slug'] ) . '_latest_published_date', $published_date, 60 * 60 * 6 );
				}
			}

			return $published_date;
		} // END get_latest_prerelease_date()

		/**
		 * Get Changelog of New Version from GitHub.
		 *
		 * @access  public
		 * @since   2.0.1
		 * @version 2.0.2
		 * @return  string $changelog of the latest prerelease
		 */
		public function get_latest_prerelease_changelog() {
			$changelog = get_site_transient( md5( $this->config['slug'] ) . '_latest_changelog' );

			if ( $this->overrule_transients() || empty( $changelog ) ) {

				$raw_response = wp_remote_get( trailingslashit( $this->config['api_url'] ) . 'releases' );

				if ( is_wp_error( $raw_response ) ) {
					return false;
				}

				$releases  = json_decode( $raw_response['body'] );
				$changelog = false;

				if ( is_array( $releases ) ) {
					foreach ( $releases as $release ) {

						// If the release is a pre-release then return the body.
						if ( $release->prerelease ) {
							if ( ! class_exists( 'Parsedown' ) ) {
								include_once( 'parsedown.php' );
							}
							$Parsedown = new Parsedown();

							$changelog = $Parsedown->text( $release->body );
							break;
						}
					}
				}

				// Refresh every 6 hours.
				if ( ! empty( $changelog ) ) {
					set_site_transient( md5( $this->config['slug'] ) . '_latest_changelog', $changelog, 60*60*6 );
				}
			}

			return $changelog;
		} // END get_latest_prerelease_changelog()

		/**
		 * Get GitHub Data from the specified repository.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return array $github_data the data
		 */
		public function get_github_data() {
			if ( ! empty( $this->github_data ) ) {
				$github_data = $this->github_data;
			} else {
				$github_data = get_site_transient( md5( $this->config['slug'] ) . '_github_data' );

				if ( $this->overrule_transients() || ( ! isset( $github_data ) || ! $github_data || '' == $github_data ) ) {
					$github_data = wp_remote_get( $this->config['api_url'] );

					if ( is_wp_error( $github_data ) ) {
						return false;
					}

					$github_data = json_decode( $github_data['body'] );

					// refresh every 6 hours
					set_site_transient( md5( $this->config['slug'] ) . '_github_data', $github_data, 60*60*6 );
				}

				// Store the data in this class instance for future calls
				$this->github_data = $github_data;
			}

			return $github_data;
		} // END get_github_data()

		/**
		 * Get update date.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @version 2.0.2
		 * @return  string $_date the date
		 */
		public function get_date() {
			$_date = $this->get_latest_prerelease_date();
			return ! empty( $_date ) ? date( 'Y-m-d', strtotime( $_date ) ) : false;
		} // END get_date()

		/**
		 * Get plugin description.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return string $_description the description
		 */
		public function get_description() {
			$_description = $this->get_github_data();
			return ! empty( $_description->description ) ? $_description->description : false;
		} // END get_description()

		/**
		 * Get plugin changelog.
		 *
		 * @access public
		 * @since  2.0.1
		 * @return string $_changelog the changelog of the release
		 */
		public function get_changelog() {
			$_changelog = $this->get_latest_prerelease_changelog();
			return ! empty( $_changelog ) ? $_changelog : false;
		} // END get_changelog()

		/**
		 * Get Plugin data.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return object $data the data
		 */
		public function get_plugin_data() {
			return get_plugin_data( WP_PLUGIN_DIR . '/' . $this->config['plugin_file'] );
		} // END get_plugin_data()

		/**
		 * Hook into the plugin update check and connect to GitHub.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @version 3.0.0
		 * @param   object $transient the plugin data transient
		 * @return  object $transient updated plugin data transient
		 */
		public function api_check( $transient ) {
			// If no plugins have been checked then return its value without hacking it.
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			/**
			 * Clear our transient if we have debug enabled and overruled the transients.
			 * This will allow the API to check fresh every time.
			 *
			 * DEV NOTE: If api checked to many times in a short amount of time, 
			 * GitHub will block you from accessing the API for 1 hour.
			 */
			if ( WP_DEBUG && $this->overrule_transients() ) {
				delete_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );
				delete_site_transient( md5( $this->config['slug'] ) . '_latest_changelog' );
			}

			// Update tags.
			$this->set_update_args();

			// Filename.
			$filename = $this->config['plugin_file'];

			$data = array(
				'id'             => $this->config['slug'],
				'slug'           => $this->config['slug'],
				'plugin'         => $filename,
				'new_version'    => $this->config['new_version'],
				'requires'       => $this->config['requires'],
				'tested'         => $this->config['tested'],
				'requires_php'   => $this->config['requires_php'],
				'url'            => $this->config['homepage'],
				'icons'          => array(
					'2x' => esc_url( 'https://raw.githubusercontent.com/autoloadnextpost/auto-load-next-post/master/.wordpress-org/icon-256x256.png' ),
					'1x' => esc_url( 'https://raw.githubusercontent.com/autoloadnextpost/auto-load-next-post/master/.wordpress-org/icon-128x128.png' ),
				),
				'banners'        => array(
					'low'  => esc_url( 'https://raw.githubusercontent.com/autoloadnextpost/auto-load-next-post/master/.wordpress-org/banner-772x250.png' ),
					'high' => esc_url( 'https://raw.githubusercontent.com/autoloadnextpost/auto-load-next-post/master/.wordpress-org/banner-1544x500.png' )
				),
				'upgrade_notice' => '',
				'package'        => $this->config['zip_url']
			);

			// Check the version and decide if it's new.
			$update = version_compare( $this->config['new_version'], $this->config['version'], '>' );

			// If the version is not newer then return default.
			if ( ! $update ) {
				return $transient;
			}

			// Check if its a beta release or a release candidate.
			$is_beta_rc = ( $this->is_beta_version( $this->config['new_version'] ) || $this->is_rc_version( $this->config['new_version'] ) );

			// Only set the updater to download if its a beta or pre-release version.
			if ( $is_beta_rc ) {
				$response              = new stdClass;
				$response->plugin      = $this->config['slug'];
				$response->new_version = $this->config['new_version'];
				$response->slug        = $this->config['slug'];
				$response->url         = $this->config['github_url'];
				$response->package     = $this->config['zip_url'];

				// If response is false, don't alter the transient.
				if ( false !== $response ) {
					$transient->response[ $this->config['plugin_file'] ] = $response;
				}
			}

			return $transient;
		} // END api_check()

		/**
		 * Filters the Plugin Installation API response results.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @version 3.0.0
		 * @param   object|WP_Error $response Response object or WP_Error.
		 * @param   string          $action   The type of information being requested from the Plugin Installation API.
		 * @param   object          $args     Plugin API arguments.
		 * @return  object          $response The plugin results.
		 */
		public function get_plugin_info( $response, $action, $args ) {
			// Check that we are getting plugin information.
			if ( 'plugin_information' !== $action ) {
				return $response;
			}

			// Check if this call for the API is for the right plugin.
			if ( ! isset( $args->slug ) || $args->slug != $this->config['slug'] ) {
				return $response;
			}

			// Update tags
			$this->set_update_args();

			// New Version
			$new_version = $this->config['new_version'];

			// Prepare warning!
			$warning = '';

			if ( $this->is_stable_version( $new_version ) ) {
				$warning = sprintf( __( '%1$s%3$sThis is a stable release%3$s%2$s', 'alnp-beta-tester' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
			}

			if ( $this->is_beta_version( $new_version ) ) {
				$warning = sprintf( __( '%1$s%3$sThis is a beta release%3$s%2$s', 'alnp-beta-tester' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
			}

			if ( $this->is_rc_version( $new_version ) ) {
				$warning = sprintf( __( '%1$s%3$sThis is a pre-release%3$s%2$s', 'alnp-beta-tester' ), '<h1>', '</h1>', '<span>&#9888;</span>' );
			}

			// If the new version is no different than the one installed then reset results.
			if ( version_compare( $response->version, $new_version, '=' ) ) {
				$response->name        = 'Auto Load Next Post';
				$response->plugin_name = 'Auto Load Next Post';
				$response->version     = $plugin_data['Version'];

				return $response;
			}

			// Update the results to return.
			$response->name            = $this->config['plugin_name'];
			$response->plugin_name     = $this->config['plugin_name'];
			$response->version         = $new_version;
			$response->author          = $this->config['author'];
			$response->author_homepage = 'https://autoloadnextpost.com';
			$response->homepage        = $this->config['homepage'];
			$response->requires        = $this->config['requires'];
			$response->tested          = $this->config['tested'];
			$response->requires_php    = $this->config['requires_php'];
			$response->last_updated    = $this->config['last_updated'];
			$response->slug            = $this->config['slug'];
			$response->plugin          = $this->config['slug'];

			// Sections
			$response->sections        = array(
				'description' => $this->config['description'],
				'changelog'   => $this->config['changelog']
			);
			$response->download_link   = $this->config['zip_url'];

			$download_counter = wp_remote_get( 'https://autoloadnextpost.com/download/counter/dl-counter.php' );
			if ( ! is_wp_error( $download_counter ) ) {
				$response->downloaded = wp_remote_retrieve_body( $download_counter );
			}

			$response->contributors = array(
				'autoloadnextpost' => array(
					'display_name' => 'Auto Load Next Post',
					'profile'      => esc_url( 'https://autoloadnextpost.com' ),
					'avatar'       => get_avatar_url( 'autoloadnextpost@gmail.com', array(
						'size' => '36',
					) ),
				),
				'sebd86' => array(
					'display_name' => 'Sébastien Dumont',
					'profile'      => esc_url( 'https://sebastiendumont.com' ),
					'avatar'       => get_avatar_url( 'mailme@sebastiendumont.com', array(
						'size' => '36',
					) ),
				),
			);

			// Add WordPress dot org banners for recognition.
			$response->banners = array(
				'low'  => 'https://raw.githubusercontent.com/autoloadnextpost/auto-load-next-post/master/.wordpress-org/banner-772x250.png',
				'high' => 'https://raw.githubusercontent.com/autoloadnextpost/auto-load-next-post/master/.wordpress-org/banner-1544x500.png'
			);

			// Apply warning to all sections if any.
			foreach ( $response->sections as $key => $section ) {
				$response->sections[ $key ] = $warning . $section;
			}

			return $response;
		} // END get_plugin_info()

		/**
		 * Rename the downloaded zip file.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @version 2.0.2
		 * @global  $wp_filesystem
		 * @param   string $source
		 * @param   string $remote_source
		 * @param   $upgrader
		 * @return  file|WP_Error
		 */
		public function upgrader_source_selection( $source, $remote_source, $upgrader ) {
			global $wp_filesystem;

			if ( strstr( $source, '/autoloadnextpost-auto-load-next-post-' ) ) {
				$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->config[ 'proper_folder_name' ] );

				if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
					return $corrected_source;
				} else {
					return new WP_Error( __( 'Unable to download source file.', 'alnp-beta-tester' ), 500 );
				}
			}

			return $source;
		} // END upgrader_source_selection()

		/**
		 * Return true if version string is a beta version.
		 *
		 * @access protected
		 * @static
		 * @since  2.0.2
		 * @param  string $version_str Version string.
		 * @return bool
		 */
		protected static function is_beta_version( $version_str ) {
			return strpos( $version_str, 'beta' ) !== false;
		} // END is_beta_version()

		/**
		 * Return true if version string is a Release Candidate.
		 *
		 * @access protected
		 * @static
		 * @since  2.0.2
		 * @param  string $version_str Version string.
		 * @return bool
		 */
		protected static function is_rc_version( $version_str ) {
			return strpos( $version_str, 'rc' ) !== false;
		} // END is_rc_version()

		/**
		 * Return true if version string is a stable version.
		 *
		 * @access protected
		 * @static
		 * @since  2.0.2
		 * @param  string $version_str Version string.
		 * @return bool
		 */
		protected static function is_stable_version( $version_str ) {
			return ! self::is_beta_version( $version_str ) && ! self::is_rc_version( $version_str );
		} // END is_stable_version()

	} // END class

} // END if class exists

return ALNP_Beta_Tester::instance();
