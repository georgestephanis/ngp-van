<?php

/**
 * Plugin Name: NGP VAN Integration
 * Plugin Author: George Stephanis
 */

// https://developers.ngpvan.com/van-api

class JessKing_NGP_VAN {

	/**
	 * Initial kickoff method for class.  Adds the hooks and such.
	 */
	public static function go() {
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Runs on init.  Sets up the front-end render functions for the dynamic in-page content.
	 */
	public static function init() {
		add_shortcode( 'ngp-van-map', array( __CLASS__, 'frontend_render_map' ) );
		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( 'ngp-van/map', array(
				'render_callback' => array( __CLASS__, 'frontend_render_map' ),
			) );
		}
	}

	/**
	 * Sets up the admin ui for Gutenberg and options panels and the like.
	 */
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
			sprintf( '<label for="ngp_van_api_key">%1$s</label>', __( 'API Key' ) ),
			array( __CLASS__, 'ngp_van_api_key_cb' ),
			'general',
			'ngp-van'
		);
		add_settings_field(
			'googlemaps_api_key',
			sprintf( '<label for="googlemaps_api_key">%1$s</label>', __( 'Google Maps API Key' ) ),
			array( __CLASS__, 'googlemaps_api_key_cb' ),
			'general',
			'ngp-van'
		);

		register_setting( 'general', 'ngp_van_options', array( __CLASS__, 'sanitize_options' ) );
	}

	/**
	 * Set up option panel for settings.
	 */
	public static function ngp_van_settings_section() {
		?>
		<p id="ngp-van-settings-section">
			<?php _e( 'Settings for NGP VAN integration&hellip;' ); ?>
		</p>
		<?php
	}

	/**
	 * Set up app name option display.
	 */
	public static function ngp_van_app_name_cb() {
		?>
		<input type="text" class="regular-text code" name="ngp_van_options[app_name]" value="<?php echo esc_attr( self::get_option( 'app_name' ) ); ?>" />
		<?php
	}

	/**
	 * Set up api key option display.
	 */
	public static function ngp_van_api_key_cb() {
		?>
		<input type="text" class="regular-text code" name="ngp_van_options[api_key]" value="<?php echo esc_attr( self::get_option( 'api_key' ) ); ?>" />
		<?php
	}

	/**
	 * Set up googlemapsapi key option display.
	 */
	public static function googlemaps_api_key_cb() {
		?>
		<input type="text" class="regular-text code" name="ngp_van_options[googlemaps_key]" value="<?php echo esc_attr( self::get_option( 'googlemaps_key' ) ); ?>" />
        <br /><small><a href="https://developers.google.com/maps/documentation/javascript/get-api-key"><?php esc_html_e( 'Get a Google Maps API Key &rarr;' ); ?></a></small>
		<?php
	}

	/**
	 * Return the requested stored option.
	 *
	 * @param $key
	 * @return null
	 */
	public static function get_option( $key ) {
		$options = get_option( 'ngp_van_options' );

		if ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		}

		return null;
	}

	/**
	 * Sanitize and save the options.
	 *
	 * @param $options
	 * @return array
	 */
	public static function sanitize_options( $options ) {
		$options = (array) $options;

		// To do: actually sanitize these.  Escape them, etc.
		$options['app_name']       = $options['app_name'];
		$options['api_key']        = $options['api_key'];
		$options['googlemaps_key'] = $options['googlemaps_key'];

		return $options;
	}

	/**
	 * Gutenberg admin ui.
	 */
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

	/**
	 * Shortcode / Block rendering for the events map.
	 *
	 * @param $args
	 * @return string
	 */
	public static function frontend_render_map( $args ) {
		$slug = substr( md5( json_encode( $args ) ), 0, 12 );
		$result = self::query_ngp_van_api( 'echoes', array(), 'POST', array(
			'message' => 'HI THERE NGP VAN!'
		) );

		$return = '<pre>' . print_r( $args, true ) . '</pre>';
		$return .= '<pre>' . print_r( $result, true ) . '</pre>';

		// If there isn't a Google Maps API key, don't display the map.
		if ( $googlemaps_key = self::get_option( 'googlemaps_key' ) ) {
			$return .= sprintf('<div id="map-%1$s" class="ngp-van-map"></div>', $slug);
			$return .= "<script>
				var lancaster = { lat: 40.039722, lng: -76.304444 },
					map, marker, infowindow;
				
				function initMap() {
					map = new google.maps.Map( document.getElementById('map-{$slug}'), {
						center: lancaster,
						zoom: 9,
						disableDefaultUI: true
					});
					infowindow = new google.maps.InfoWindow({
						content: '<div id=\"content\"><p>Jess King for Congress!</p></div>'
					});
					marker = new google.maps.Marker({
						position: lancaster,
						title: 'Lancaster',
						map: map
					});
					marker.addListener( 'click', function() {
						infowindow.open( map, marker );
					});
				}
				</script>";

			wp_enqueue_style('ngp-van-map', plugins_url('/css/ngp-van-map.css', __FILE__));
			wp_enqueue_script('googlemaps', sprintf('https://maps.googleapis.com/maps/api/js?key=%s&callback=initMap', $googlemaps_key));
		} elseif ( current_user_can( 'manage_options' ) ) {
		    // Do a callout to nag the user to get a Google Maps API Key
		    $return .= '<h1><a href="' . admin_url( 'options-general.php#ngp-van-settings-section' ) . '">Hey, ' . wp_get_current_user()->display_name . ' -- Add a Google Maps API Key to get a map based rendering.</a></h1>';
        }

		$return .= "<h3>Upcoming Events:</h3>";
		$return .= "<ul><li>Thing one</li><li>Thing two</li><li>Thing three</li></ul>";

		return $return;
	}

	/**
	 * Method to add new supporters via a form.
	 *
	 * @param $fname
	 * @param $lname
	 * @param $email
	 * @param array $args
	 * @return array|mixed|object
	 */
	public static function add_supporter( $fname, $lname, $email, $args = array() ) {
		$data = array(
			'firstName' => $fname,
			'lastName'  => $lname,
			'emails'    => array(
				array(
					'email' => $email,
				)
			),
			'addresses' => array(),
		);

		if ( ! empty( $args['zip'] ) ) {
			$data['addresses'][] = array(
				'zipOrPostalCode' => $args['zip'],
			);
		}

		$response = self::query_ngp_van_api( 'people/findOrCreate', $data, 'POST' );

		if ( $response['vanId'] ) {
			// Add further data via:
			/*
			$canvassData = array(
				'responses' => array(
					array(
						'activistCodeId' => 1234,
						'action' => 'Apply',
						'type' => 'ActivistCode'
					),
				),
			);
			self::query_ngp_van_api( sprintf( 'people/%d/canvassResponses', $response['vanId'] ), $canvassData, 'POST' );
			*/
		}

		return $response;
	}

    /**
     * @return array|mixed|object
     */
    public static function get_upcoming_events() {
	    $events = self::query_ngp_van_api( 'events', array(
            // startingAfter doesn't include the specified date, so to include today we need to say after yesterday.
            'startingAfter' => date( 'Y-m-d', time() - DAY_IN_SECONDS ),
            '$top'          => 50,
            '$expand'       => 'locations',
            'ngp_van_mode'  => 1,
        ) );

	    // Sample response data: https://developers.ngpvan.com/van-api#events-get-events

        // To do: add pagination parsing to handle lumps larger than 50 items

	    return $events->items;
    }

	/**
	 * General method for communicating with the NGP VAN api server.
	 *
	 * @param $endpoint
	 * @param array $args
	 * @param string $method
	 * @param null $body
	 * @return array|mixed|object
	 */
	public static function query_ngp_van_api( $endpoint, $args = array(), $method = 'GET', $body = null ) {
		// Temporarily short circuit for testing.
		return [ 'it' => 'works' ];

		// Hash the query and check if it's stored in a valid transient?

        $mode = 0;
        if ( ! empty( $args['ngp_van_mode'] ) ) {
            $mode = intval( $args['ngp_van_mode'] );
        }
		$args['headers']['Content-type'] = 'application/json';
		$args['headers']['Authorization'] = 'Basic ' . base64_encode( self::get_option( 'app_name' ) . ':' . self::get_option( 'api_key' ) . '|' . $mode );
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
