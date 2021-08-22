<?php
/**
 * Oxyrealm Bitwise
 *
 * @wordpress-plugin
 * Plugin Name:         Oxyrealm Bitwise
 * Plugin URI:          https://bitwise.oxyrealm.com
 * Description:         Oxygen Builder tweak
 * Version:             1.0.1
 * Requires at least:   5.6
 * Tested up to:        5.8
 * Requires PHP:        7.4
 * Author:              oxyrealm
 * Author URI:          https://oxyrealm.com
 * Text Domain:         oxyrealm-bitwise
 * Domain Path:         /languages
 *
 * @package             Bitwise
 * @author              oxyrealm <hello@oxyrealm.com>
 * @link                https://oxyrealm.com
 * @since               1.0.0
 * @copyright           2021 oxyrealm.com
 * @version             1.0.1
 */

namespace Oxyrealm\Modules\Bitwise;

defined( 'ABSPATH' ) || exit;

define( 'OXYREALM_BITWISE_VERSION', '1.0.1' );
define( 'OXYREALM_BITWISE_DB_VERSION', '001' );
define( 'OXYREALM_BITWISE_AETHER_MINIMUM_VERSION', '1.1.17' );

define( 'OXYREALM_BITWISE_FILE', __FILE__ );
define( 'OXYREALM_BITWISE_PATH', dirname( OXYREALM_BITWISE_FILE ) );
define( 'OXYREALM_BITWISE_MIGRATION_PATH', OXYREALM_BITWISE_PATH . '/database/migrations/' );
define( 'OXYREALM_BITWISE_URL', plugins_url( '', OXYREALM_BITWISE_FILE ) );
define( 'OXYREALM_BITWISE_ASSETS', OXYREALM_BITWISE_URL . '/dist' );

require_once __DIR__ . '/vendor/autoload.php';

use Oxyrealm\Aether\Assets;
use Oxyrealm\Aether\Utils;
use Oxyrealm\Aether\Utils\Migration;
use Oxyrealm\Aether\Utils\Oxygen;
use Oxyrealm\Loader\Aether;
use Oxyrealm\Loader\Update;

class Bitwise extends Aether {

	/** @var Update */
	public $skynet;

	public function __construct( $module_id ) {
		parent::__construct( $module_id );

		if ( ! $this->are_requirements_met( OXYREALM_BITWISE_FILE, OXYREALM_BITWISE_AETHER_MINIMUM_VERSION ) ) {
			return;
		}

		add_filter( 'plugin_action_links_' . plugin_basename( OXYREALM_BITWISE_FILE ), function ( $links ) {
			return Utils::plugin_action_links( $links, $this->module_id );
		} );

		register_activation_hook( OXYREALM_BITWISE_FILE, [ $this, 'plugin_activate' ] );
		register_deactivation_hook( OXYREALM_BITWISE_FILE, [ $this, 'plugin_deactivate' ] );

		add_action( 'plugins_loaded', [ $this, 'init_plugin' ], 100 );

		new Command;
	}

	public static function run( $module_id ) {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new Bitwise( $module_id );
		}

		return $instance;
	}

	public function init_plugin() {
		Assets::register_script( "{$this->module_id}-editor", OXYREALM_BITWISE_URL . '/dist/editor.js', [], OXYREALM_BITWISE_VERSION, true );
		Assets::register_style( "{$this->module_id}-editor", OXYREALM_BITWISE_URL . '/dist/editor.css', [], OXYREALM_BITWISE_VERSION );

		add_action( 'init', [ $this, 'boot' ] );
	}

	public function boot() {
		Assets::do_register();

		if ( Utils::is_request( 'ajax' ) && Oxygen::can() ) {
			// add_action( "wp_ajax_{$this->module_id}_data_glacier", [ $this, 'data_glacier' ] );
		}

		if ( Oxygen::can( true ) ) {
			if ( Utils::is_request( 'admin' ) ) {
				new Admin( $this->module_id );
			}
		}

		$this->plugin_update();

		if ( Oxygen::is_oxygen_editor() ) {
			// add_action( 'wp_footer', function () {
			// 	echo $this->get_template();
			// } );
			add_action( 'wp_enqueue_scripts', function () {
				wp_enqueue_style( "{$this->module_id}-editor" );
				wp_enqueue_script( "{$this->module_id}-editor" );
				wp_localize_script(
					"{$this->module_id}-editor",
					'bitwise',
					[
						'ajax_url'           => admin_url( 'admin-ajax.php' ),
						'nonce'              => wp_create_nonce( $this->module_id ),
						'module_id'          => $this->module_id,
						'debug_mode'         => defined( 'WP_DEBUG' ),
					]
				);
			}, 1000 );
		}
	}

	private function plugin_update(): void {
		$payload = [
			'version'     => OXYREALM_BITWISE_VERSION,
			'license'     => get_option( "{$this->module_id}_license_key" ),
			'beta'        => get_option( "{$this->module_id}_beta" ),
			'plugin_file' => OXYREALM_BITWISE_FILE,
			'item_id'     => 0,
			'store_url'   => 'https://oxyrealm.com',
			'author'      => 'oxyrealm',
			'is_require_license' => false,
		];

		$this->skynet = new Update( $this->module_id, $payload );

		if ( $this->skynet->isActivated() ) {
			$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
			if ( ! ( current_user_can( 'manage_options' ) && $doing_cron ) ) {
				$this->skynet->ignite();
			}
		}
	}

	public function plugin_activate(): void {
		if ( ! get_option( 'oxyrealm_bitwise_installed' ) ) {
			update_option( 'oxyrealm_bitwise_installed', time() );
		}

		$installed_db_version = get_option( 'oxyrealm_bitwise_db_version' );

		if ( ! $installed_db_version || intval( $installed_db_version ) !== intval( OXYREALM_BITWISE_DB_VERSION ) ) {
			Migration::migrate( OXYREALM_BITWISE_MIGRATION_PATH, "\\Oxyrealm\\Modules\\Bitwise\\Database\\Migrations\\", $installed_db_version ?: 0, OXYREALM_BITWISE_DB_VERSION );
			update_option( 'oxyrealm_bitwise_db_version', OXYREALM_BITWISE_DB_VERSION );
		}

		update_option( 'oxyrealm_bitwise_version', OXYREALM_BITWISE_VERSION );
	}

	public function plugin_deactivate(): void {
	}
}

$aether_m_bitwise = Bitwise::run( 'aether_m_bitwise' );
