<?php
/**
 * Plugin Name: Auto Load Next Post: Beta Tester
 * Plugin URI: https://github.com/AutoLoadNextPost/alnp-beta-tester
 * Version: 2.0.0
 * Description: Run bleeding edge versions of Auto Load Next Post from the GitHub repo. This will replace your installed version of Auto Load Next Post with the latest tagged prerelease on GitHub - use with caution, and not on production sites. You have been warned.
 * Author: Auto Load Next Post
 * Author URI: https://autoloadnextpost.com
 * Developer: Sébastien Dumont
 * Developer URI: https://sebastiendumont.com
 * GitHub Plugin URI: https://github.com/AutoLoadNextPost/alnp-beta-tester
 *
 * Text Domain: alnp-beta-tester
 * Domain Path: /languages/
 *
 * Requires at least: 4.5
 * Tested up to: 4.9.2
 *
 * Based on WP_GitHub_Updater by Joachim Kudish.
 * Forked from WooCommerce Beta Tester by Mike Jolley and Claudio Sanches.
 *
 * Copyright: © 2018 Sébastien Dumont
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		private static $version = '2.0.0';

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
			_doing_it_wrong( __FUNCTION__, __( 'Foul!', 'alnp-beta-tester' ), $this->version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @access public
		 * @since  2.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Foul!', 'alnp-beta-tester' ), $this->version );
		}

		/**
		 * Constructor
		 *
		 * @access public
		 * @static
		 * @since  1.0.0
		 */
		public function __construct() {
			$this->config = array(
				'plugin_file'        => 'auto-load-next-post/auto-load-next-post.php',
				'slug'               => 'auto-load-next-post',
				'proper_folder_name' => 'auto-load-next-post',
				'api_url'            => 'https://api.github.com/repos/AutoLoadNextPost/auto-load-next-post',
				'github_url'         => 'https://github.com/AutoLoadNextPost/Auto-Load-Next-Post',
				'requires'           => '4.5',
				'tested'             => '4.9.2'
			);

			add_action( 'plugin_loaded', array( $this, 'flush_update_cache' ) );
			add_action( 'plugin_loaded', array( $this, 'check_alnp_installed' ) );
			add_action( 'init', array( $this, 'load_text_domain' ), 0 );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'api_check' ) );
			add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
			add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 3 );
		} // END __construct()

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
			delete_site_transient( 'update_plugins' );
			delete_site_transient( 'auto_load_next_post_latest_tag' ); // Previous beta tester value
			delete_site_transient( 'auto-load-next-post_latest_tag' ); // Previous beta tester value
			delete_site_transient( md5( 'auto-load-next-post' ) . '_latest_tag' );
		} // END flush_update_cache()

		/**
		 * Checks if Auto Load Next Post is installed.
		 *
		 * @access public
		 * @since  2.0.0
		 * @return bool
		 */
		public function check_alnp_installed() {
			if ( ! defined( 'AUTO_LOAD_NEXT_POST_VERSION' ) ) {
				add_action( 'admin_notices', array( $this, 'alnp_not_installed' ) );
				return false;
			}
		} // END check_alnp_installed()

		/**
		 * Auto Load Next Post is Not Installed Notice.
		 *
		 * @access public
		 * @since  2.0.0
		 * @return void
		 */
		public function alnp_not_installed() {
			echo '<div class="error"><p>' . sprintf( __( 'Auto Load Next Post: Beta Tester requires %s to be installed.', 'alnp-beta-tester' ), '<a href="https://autoloadnextpost.com/" target="_blank">Auto Load Next Post</a>' ) . '</p></div>';
		} // END alnp_not_installed()

		/**
		 * Update the required plugin data arguments.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return array
		 */
		public function set_update_args() {
			$plugin_data                    = $this->get_plugin_data();
			$this->config[ 'plugin_name' ]  = $plugin_data['Name'];
			$this->config[ 'version' ]      = $plugin_data['Version'];
			$this->config[ 'author' ]       = $plugin_data['Author'];
			$this->config[ 'homepage' ]     = $plugin_data['PluginURI'];
			$this->config[ 'new_version' ]  = $this->get_latest_prerelease();
			$this->config[ 'last_updated' ] = $this->get_date();
			$this->config[ 'description' ]  = $this->get_description();
			$this->config[ 'zip_url' ]      = 'https://github.com/AutoLoadNextPost/Auto-Load-Next-Post/zipball/' . $this->config[ 'new_version' ];
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
		 * @access public
		 * @since  1.0.0
		 * @return string $date the date
		 */
		public function get_date() {
			$_date = $this->get_github_data();
			return ! empty( $_date->updated_at ) ? date( 'Y-m-d', strtotime( $_date->updated_at ) ) : false;
		} // END get_date()

		/**
		 * Get plugin description.
		 *
		 * @access public
		 * @since  1.0.0
		 * @return string $description the description
		 */
		public function get_description() {
			$_description = $this->get_github_data();
			return ! empty( $_description->description ) ? $_description->description : false;
		} // END get_description()

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
		 * @access public
		 * @since  1.0.0
		 * @param  object $transient the plugin data transient
		 * @return object $transient updated plugin data transient
		 */
		public function api_check( $transient ) {
			// Check if the transient contains the 'checked' information
			// If not, just return its value without hacking it
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			// Clear our transient.
			delete_site_transient( md5( $this->config['slug'] ) . '_latest_tag' );

			// Update tags.
			$this->set_update_args();

			// Check the version and decide if it's new.
			$update = version_compare( $this->config['new_version'], $this->config['version'], '>' );

			if ( $update ) {
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
		 * Get Plugin info.
		 *
		 * @access public
		 * @since  1.0.0
		 * @param  bool   $false    always false
		 * @param  string $action   the API function being performed
		 * @param  object $args     plugin arguments
		 * @return object $response the plugin info
		 */
		public function get_plugin_info( $false, $action, $response ) {
			// Check if this call for the API is for the right plugin.
			if ( ! isset( $response->slug ) || $response->slug != $this->config['slug'] ) {
				return false;
			}

			// Update tags
			$this->set_update_args();

			$response->slug          = $this->config['slug'];
			$response->plugin        = $this->config['slug'];
			$response->name          = $this->config['plugin_name'];
			$response->plugin_name   = $this->config['plugin_name'];
			$response->version       = $this->config['new_version'];
			$response->author        = $this->config['author'];
			$response->homepage      = $this->config['homepage'];
			$response->requires      = $this->config['requires'];
			$response->tested        = $this->config['tested'];
			$response->downloaded    = 0;
			$response->last_updated  = $this->config['last_updated'];
			$response->sections      = array( 'description' => $this->config['description'] );
			$response->download_link = $this->config['zip_url'];

			return $response;
		} // END get_plugin_info()

		/**
		 * Rename the downloaded zip file.
		 *
		 * @access public
		 * @since  1.0.0
		 * @global $wp_filesystem
		 * @param  string $source
		 * @param  string $remote_source
		 * @param  $upgrader
		 * @return file|WP_Error
		 */
		public function upgrader_source_selection( $source, $remote_source, $upgrader ) {
			global $wp_filesystem;

			if ( strstr( $source, '/AutoLoadNextPost-Auto-Load-Next-Post-' ) ) {
				$corrected_source = trailingslashit( $remote_source ) . trailingslashit( $this->config[ 'proper_folder_name' ] );

				if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
					return $corrected_source;
				} else {
					return new WP_Error( __( 'Unable to download source file.', 'alnp-beta-tester' ), 500 );
				}
			}

			return $source;
		} // END upgrader_source_selection()

	} // END class

} // END if class exists

return ALNP_Beta_Tester::instance();
