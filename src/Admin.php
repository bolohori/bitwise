<?php

namespace Oxyrealm\Modules\Bitwise;

use Oxyrealm\Aether\Admin as AetherAdmin;
use Oxyrealm\Aether\Utils\Notice;
use Oxyrealm\Loader\Update;

class Admin {
	private $module_id;

	public function __construct( $module_id ) {
		$this->module_id = $module_id;

		add_action( 'admin_menu', [ $this, 'admin_menu' ], 100 );
	}

	public function admin_menu(): void {
		$capability = 'manage_options';

		if ( current_user_can( $capability ) ) {
			$hook = add_submenu_page(
				AetherAdmin::$slug,
				__( 'Bitwise', 'oxyrealm-bitwise' ),
				__( 'Bitwise', 'oxyrealm-bitwise' ),
				$capability,
				$this->module_id,
				[
					$this,
					'plugin_page'
				]
			);

			add_action( 'load-' . $hook, [ $this, 'init_hooks' ] );
		}
	}

	public function init_hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts(): void {
		wp_localize_script( "{$this->module_id}-admin", 'bitwise', [
			'ajax_url'  => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( $this->module_id ),
			'module_id' => $this->module_id,
		] );
	}

	public function plugin_page(): void {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
		?>
		<h1 class="wp-heading-inline">Bitwise <span class="subtitle">Version: <?php echo OXYREALM_BITWISE_VERSION; ?> </span> </h1>
        <hr class="wp-header-end">
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo add_query_arg( [
				'page' => $this->module_id,
				'tab'  => 'settings',
			], admin_url( 'admin.php' ) ); ?>"
               class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"> Settings </a>
            <a href="<?php echo add_query_arg( [
				'page' => $this->module_id,
				'tab'  => 'license',
			], admin_url( 'admin.php' ) ); ?>"
               class="nav-tab <?php echo $active_tab == 'license' ? 'nav-tab-active' : ''; ?>"> License </a>
			<!-- <a href="<?php echo add_query_arg( [
				'page' => $this->module_id,
				'tab'  => 'faq',
			], admin_url( 'admin.php' ) ); ?>"
               class="nav-tab <?php echo $active_tab == 'faq' ? 'nav-tab-active' : ''; ?>"> FAQ </a> -->
			<a href="<?php echo add_query_arg( [
				'page' => AetherAdmin::$slug,
				'tab'  => 'main',
			], admin_url( 'admin.php' ) ); ?>"
               class="nav-tab"> Advanced </a>
			<a
                href="https://bitwise.oxyrealm.com"
                target="_blank"
                class="nav-tab"
                style="display: inline-flex;"
            >
                Documentation
                <svg class="icon outbound" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" x="0px" y="0px" viewBox="0 0 100 100" width="15" height="15" data-v-641633f9=""><path fill="currentColor" d="M18.8,85.1h56l0,0c2.2,0,4-1.8,4-4v-32h-8v28h-48v-48h28v-8h-32l0,0c-2.2,0-4,1.8-4,4v56C14.8,83.3,16.6,85.1,18.8,85.1z"></path><polygon fill="currentColor" points="45.7,48.7 51.3,54.3 77.2,28.5 77.2,37.2 85.2,37.2 85.2,14.9 62.8,14.9 62.8,22.9 71.5,22.9"></polygon></svg>
            </a>
        </h2>
		<?php
		switch ( $active_tab ) {
			case 'license':
				$this->license_tab();
				break;
			case 'settings':
			default:
				$this->setting_tab();
				break;
		}
	}

	public function setting_tab(): void {
	}

	public function license_tab(): void {
		if ( isset( $_POST['submit'] ) ) {

			if ( ! wp_verify_nonce( $_POST["{$this->module_id}_settings_form"], $this->module_id ) ) {
				Notice::error( 'Nonce verification failed', $this->module_id );
				echo( "<script>location.href = '" . add_query_arg( [
						'page' => $this->module_id,
						'tab'  => 'license',
					], admin_url( 'admin.php' ) ) . "'</script>" );
				exit;
			}

			$_request_license_key = sanitize_text_field( $_REQUEST['license_key'] );

			if ( $_request_license_key !== get_option( "{$this->module_id}_license_key" ) ) {
				/** @var Bitwise $aether_m_bitwise */
				global $aether_m_bitwise;

				if ( empty( $_request_license_key ) ) {
					$aether_m_bitwise->skynet->deactivate();
					update_option( "{$this->module_id}_license_key", null );

					Notice::success( 'Plugin license key de-activated successfully', $this->module_id );
				} else {
					$response = $aether_m_bitwise->skynet->activate( $_request_license_key );

					if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
						Notice::error( [ is_wp_error( $response ) ? $response->get_error_message() : 'An error occurred, please try again.' ], $this->module_id );
					} else {
						$license_data = json_decode( wp_remote_retrieve_body( $response ) );

						if ( $license_data->license != 'valid' ) {
							Notice::error( [ Update::errorMessage( $license_data->error ) ], $this->module_id );
						} else {
							update_option( "{$this->module_id}_license_key", $_request_license_key );
							Notice::success( 'Plugin license key activated successfully', $this->module_id );
						}
					}
				}

			}

			update_option( "{$this->module_id}_beta", sanitize_text_field( $_REQUEST['beta'] ?? false ) );

			Notice::success( 'License have been saved!', $this->module_id );
			echo( "<script>location.href = '" . add_query_arg( [
					'page' => $this->module_id,
					'tab'  => 'license',
				], admin_url( 'admin.php' ) ) . "'</script>" );
			exit;
		}

		$_license_key = get_option( "{$this->module_id}_license_key" );
		$_beta        = get_option( "{$this->module_id}_beta" );

		?>
        <form method="POST">
			<?php wp_nonce_field( $this->module_id, "{$this->module_id}_settings_form" ); ?>
            <table class="form-table" role="presentation">
                <tbody>

                <tr>
                    <th scope="row"><label>License Key</label></th>
                    <td>
                        <input name="license_key" type="password"
                               value="<?php echo esc_attr( $_license_key ); ?>"/>
                        <p class="description">Enter your <a
                                    href="https://go.oxyrealm.com/bitwise"
                                    target="_blank">license key</a> to get update</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Enable pre-release version</label></th>
                    <td>
                        <input name="beta" type="checkbox"
                               value="1" <?php if ( $_beta ) {
							echo "checked";
						} ?>>
                    </td>
                </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </p>
        </form>
		<?php
	}

}
