<?php

/**
 * Plugin Name: NGP VAN Integration
 * Plugin Author: George Stephanis
 */

// https://developers.ngpvan.com/van-api

class JessKing_NGP_VAN {

	public static function go() {
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );
	}

	public static function init() {
		add_shortcode( 'ngp-van-map', array( __CLASS__, 'frontend_render_map' ) );
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( 'ngp-van/map', array(
				'render_callback' => array( __CLASS__, 'frontend_render_map' ),
			) );
		}
	}

	public static function admin_init() {
		add_settings_section(
			'ngp-van',
			esc_html__( 'NGP VAN' ),
			array( __CLASS__, 'ngp_van_settings_section' ),
			'general'
		);

		add_settings_field(
			'ngp_van_app_name',
			sprintf( '<label for="ngp_van_app_name">%1$s</label>', __( 'Application Name' ) ),
			array( __CLASS__, 'ngp_van_app_name_cb' ),
			'general',
			'ngp-van'
		);
		add_settings_field(
			'ngp_van_api_key',
			sprintf( '<label for="ngp_van_app_name">%1$s</label>', __( 'API Key' ) ),
			array( __CLASS__, 'ngp_van_api_key_cb' ),
			'general',
			'ngp-van'
		);

		register_setting( 'general', 'ngp_van_options', array( __CLASS__, 'sanitize_options' ) );
	}

	public static function ngp_van_settings_section() {
		?>
		<p id="ngp-van-settings-section">
			<?php _e( 'Settings for NGP VAN integration&hellip;' ); ?>
		</p>
		<?php
	}

	public static function ngp_van_app_name_cb() {
		?>
		<input type="text" class="regular-text code" name="ngp_van_options[app_name]" value="<?php echo esc_attr( self::get_option( 'app_name' ) ); ?>" />
		<?php
	}

	public static function ngp_van_api_key_cb() {
		?>
		<input type="text" class="regular-text code" name="ngp_van_options[api_key]" value="<?php echo esc_attr( self::get_option( 'api_key' ) ); ?>" />
		<?php
	}

	public static function get_option( $key ) {
		$options = get_option( 'ngp_van_options' );

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		return null;
	}

	public static function sanitize_options( $options ) {
		$options = (array) $options;

		$options['app_name'] = $options['app_name'];
		$options['api_key'] = $options['api_key'];

		return $options;
	}

	public static function enqueue_block_editor_assets() {
		wp_enqueue_script(
			'ngp-van-block',
			plugins_url( '/js/ngp-van-block.js', __FILE__ ),
			array(
				'wp-blocks',
				'wp-element',
			),
			false,
			true
		);
		wp_localize_script(
			'ngp-van-block',
			'ngpVanBlock',
			array(
				'strings' => array(
					'label' => __( 'NGP VAN Map' ),
				)
			)
		);
	}

	public static function frontend_render_map( $args ) {
		$result = self::query_ngp_van_api( 'echoes', array(), 'POST', array(
			'message' => 'HI THERE NGP VAN!'
		) );

		$return = '<pre>' . print_r( $args, true ) . '</pre>';
		$return .= '<pre>' . print_r( $result, true ) . '</pre>';

		return $return;
	}


	public static function query_ngp_van_api( $endpoint, $args = array(), $method = 'GET', $body = null ) {
		return [ 'it' => 'works' ];

		// Hash the query and check if it's stored in a valid transient?

		$args['headers']['Content-type'] = 'application/json';
		$args['headers']['Authorization'] = 'Basic ' . base64_encode( self::get_option( 'app_name' ) . ':' . self::get_option( 'api_key' ) );
		$args['method'] = $method;
		$args['body'] = $body;

		$url = 'https://api.securevan.com/v4/' . ltrim( $endpoint, '/' );
		$response = wp_remote_request( $url, $args );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		return $data;
	}
}

JessKing_NGP_VAN::go();
