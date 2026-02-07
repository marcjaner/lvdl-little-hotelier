<?php

namespace Lvdl\LittleHotelier\Admin;

use Lvdl\LittleHotelier\Service\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsPage {
	private SettingsRepository $settings_repo;

	public function __construct( SettingsRepository $settings_repo ) {
		$this->settings_repo = $settings_repo;
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_options_page(): void {
		add_options_page(
			__( 'LVDL Little Hotelier', 'lvdl-little-hotelier' ),
			__( 'LVDL Little Hotelier', 'lvdl-little-hotelier' ),
			'manage_options',
			'lvdl-little-hotelier',
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'lvdl_lh_settings_group',
			SettingsRepository::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'lvdl_lh_main',
			__( 'Booking Configuration', 'lvdl-little-hotelier' ),
			'__return_false',
			'lvdl-little-hotelier'
		);

		$this->add_field( 'channel_code', __( 'Channel Code', 'lvdl-little-hotelier' ), 'text' );
		$this->add_field( 'base_url_template', __( 'Base URL Template', 'lvdl-little-hotelier' ), 'url' );
		$this->add_field( 'region', __( 'Region', 'lvdl-little-hotelier' ), 'select' );
		$this->add_field( 'currency', __( 'Default Currency', 'lvdl-little-hotelier' ), 'text' );
		$this->add_field( 'locale', __( 'Default Locale', 'lvdl-little-hotelier' ), 'text' );
		$this->add_field( 'max_stay_days', __( 'Max Stay Days', 'lvdl-little-hotelier' ), 'number' );
		$this->add_field( 'enable_precheck', __( 'Enable Rates Precheck', 'lvdl-little-hotelier' ), 'checkbox' );
		$this->add_field( 'cache_duration', __( 'Cache Duration (seconds)', 'lvdl-little-hotelier' ), 'number' );
	}

	private function add_field( string $key, string $label, string $type ): void {
		add_settings_field(
			'lvdl_lh_' . $key,
			$label,
			array( $this, 'render_field' ),
			'lvdl-little-hotelier',
			'lvdl_lh_main',
			array(
				'key'  => $key,
				'type' => $type,
			)
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'LVDL Little Hotelier Settings', 'lvdl-little-hotelier' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'lvdl_lh_settings_group' );
				do_settings_sections( 'lvdl-little-hotelier' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param array<string,string> $args
	 */
	public function render_field( array $args ): void {
		$settings = $this->settings_repo->get_settings();
		$key      = $args['key'];
		$type     = $args['type'];
		$value    = $settings[ $key ] ?? '';
		$name     = SettingsRepository::OPTION_NAME . '[' . $key . ']';
		$id       = 'lvdl_lh_' . $key;

		if ( 'checkbox' === $type ) {
			printf(
				'<input type="checkbox" id="%1$s" name="%2$s" value="yes" %3$s />',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( 'yes', (string) $value, false )
			);
			return;
		}

		if ( 'select' === $type && 'region' === $key ) {
			$options = array(
				'apac' => 'APAC',
				'emea' => 'EMEA',
				'amer' => 'AMER',
			);

			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $options as $option_key => $label ) {
				echo '<option value="' . esc_attr( $option_key ) . '" ' . selected( (string) $value, $option_key, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
			return;
		}

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" />',
			esc_attr( $type ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * @param array<string,mixed> $input
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$defaults  = $this->settings_repo->defaults();
		$sanitized = $defaults;

		$sanitized['channel_code']      = sanitize_text_field( (string) ( $input['channel_code'] ?? '' ) );
		$sanitized['base_url_template'] = esc_url_raw( (string) ( $input['base_url_template'] ?? '' ) );
		$sanitized['region']            = sanitize_text_field( (string) ( $input['region'] ?? 'apac' ) );
		$sanitized['currency']          = strtoupper( sanitize_text_field( (string) ( $input['currency'] ?? $defaults['currency'] ) ) );
		$sanitized['locale']            = sanitize_text_field( (string) ( $input['locale'] ?? $defaults['locale'] ) );
		$sanitized['max_stay_days']     = max( 1, absint( $input['max_stay_days'] ?? $defaults['max_stay_days'] ) );
		$sanitized['enable_precheck']   = ( isset( $input['enable_precheck'] ) && 'yes' === $input['enable_precheck'] ) ? 'yes' : 'no';
		$sanitized['cache_duration']    = max( 900, absint( $input['cache_duration'] ?? $defaults['cache_duration'] ) );

		if ( ! in_array( $sanitized['region'], array( 'apac', 'emea', 'amer' ), true ) ) {
			$sanitized['region'] = 'apac';
		}

		return $sanitized;
	}
}
