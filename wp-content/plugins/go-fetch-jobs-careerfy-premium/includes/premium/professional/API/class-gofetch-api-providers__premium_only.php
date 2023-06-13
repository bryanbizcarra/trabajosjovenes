<?php
/**
 * Importer classes for providers that use an API to provide jobs.
 *
 * @package GoFetch/Admin/Premium/Pro+/API Providers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The abtract class for any feed based on an API.
 */
abstract class GoFetch_API_Feed_Provider {

	/**
	 * The provider unique string ID.
	 *
	 * @string $id
	 */
	protected $id;

	/**
	 * The API URL.
	 *
	 * @string $api_url
	 */
	protected $api_url;

	/**
	 * Wether the API requires tracking update for the job links
	 *
	 * @string $tracking_update
	 */
	protected $tracking_update;

	/**
	 * Returns base config for the provider.
	 */
	public function config( $providers = array() ) {
		return apply_filters( 'goft_wpjm_providers', $providers );
	}

	/**
	 * Get the provider config.
	 */
	protected function get_config( $key = '' ) {
		$config = $this->config();

		$data = $config[ $this->id ];

		if ( ! $key ) {
			return $data;
		} else if ( ! empty( $data[ $key ] ) ) {
			return $data[ $key ];
		}
		return array();
	}

	/**
	 * The method that connects and pulls the data from the API feed.
	 */
	protected function get_api_data( $url, $xml_data = false, $api_args = array() ) {

		$api_args = apply_filters( 'goft_wpjm_fetch_feed_api_args', wp_parse_args( $api_args, array( 'timeout' => 10 ) ), $this->id );

		$result = wp_remote_get( esc_url_raw( $url ), $api_args );

		if ( ! is_wp_error( $result ) ) {

			$response_headers = wp_remote_retrieve_headers( $result );

			// Double check if we're getting XML content.
			if ( ! empty( $response_headers['content-type'] ) && strpos( $response_headers['content-type'], 'text/xml' ) >= 0 ) {
				$xml_data = true;
			}

			if ( $xml_data ) {

				$json = wp_remote_retrieve_body( $result );

				if ( ! GoFetch_Helper::is_json( $json ) ) {
					if ( $simple_xml = simplexml_load_string( $json ) ) {
						$json = wp_json_encode( $simple_xml );
					}
				}
			} else {
				$json = wp_remote_retrieve_body( $result );
			}

			$result = json_decode( $json, true );

			if ( isset( $result['message'] ) || isset( $result['error'] ) || isset( $result['errors'] ) || isset( $result['publisher'][0] ) ) {
				global $goft_wpjm_options;

				$error = ! empty( $result['error'] ) ? $result['error'] : ( ! empty( $result['message'] ) ? $result['message'] : ( isset( $result['errors'] ) ? $result['errors'] :  $result['publisher'][0] ) );

				if ( is_array( $error ) ) {
					$error = implode( ', ', $error );
				}

				$setting_errors = '';

				$provider = GoFetch_RSS_Providers::get_providers( $this->id );

				foreach ( $provider['API']['required_fields'] as $field => $option ) {
					if ( ! $goft_wpjm_options->$option ) {
						$setting_errors .= html( 'span', sprintf( '- %s is empty!<br/>', $field ) );
					}
				}

				if ( $setting_errors ) {
					$setting_errors .= html( 'span', sprintf( __( 'Please fill in the required field(s) in the <a href="%s" style="color: #fff !important;">options page</a>.', 'gofetch-wpjm' ), esc_url( admin_url( 'admin.php?page=go-fetch-jobs-wpjm-settings' ) ) ) );
				}

				if ( ! empty( $setting_errors ) ) {
					$setting_errors = html( 'p class="goft_wpjm no-jobs-found feed"', $setting_errors );
				}

				return new WP_Error( 'api_feed_error', $error . $setting_errors );

			} elseif ( ! empty( $result ) ) {
				return $result;
			}
			return array();
		}
		return new WP_Error( 'api_feed_error', __( 'Could not read API feed. Please wait a few seconds and try again.', 'gofetch-wpjm' ) );
	}

	/**
	 * The method that pulls the jobs from the API feed.
	 */
	abstract public function fetch_feed_items( $items, $url, $provider );

	/**
	 * The condition to be met to be able to execute methods on the class.
	 */
	protected function condition( $provider = '' ) {
		global $post;

		if ( ! $provider ) {
			$metadata = get_post_meta( $post->ID, '_goft_source_data', true );

			if ( empty( $metadata['feed_url'] ) ) {
				return false;
			}
			$provider = $metadata['feed_url'];
		}

		$provider_id = str_replace( array( 'api.', '/api' ), '', $this->id );

		return ( false !== strpos( $provider, $provider_id ) );
	}

	/**
	 * The method for retrieving the API URL.
	 */
	protected function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Register the valid REST routes.
	 */
	public function register_rest_routes( $routes ) {
		$args = array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_api_jobs' ),
			'permission_callback' => function ( $request ) {

				// The token that provides access to the endpoint.
				$valid_token = md5( 'goft-wpjm-api-jobs' );

				$headers = $request->get_headers();

				if ( empty( $headers['x_goft_wpjm_rapi_token'] ) ) {
					return;
				}
				$token = array_pop( $headers['x_goft_wpjm_rapi_token'] );

				return ( $token === $valid_token );
			},
		);
		register_rest_route( 'goft/v1', '/api_jobs', $args );
	}

	/**
	 * Some providers will expire their jobs so we need to fetch the jobs again in real time to update the tracking ID.
	 */
	public function update_job_tracking() {
		global $post;
?>
		<script type="text/javascript">
			jQuery(function($) {

				var data = {
					post_id: '<?php echo (int) $post->ID; ?>'
				}, headers = {
					'X-goft-WPJM-RAPI-Token': '<?php echo md5( 'goft-wpjm-api-jobs' ); ?>'
				};

				$.ajax({
					type:    'GET',
					url:     '<?php echo esc_url_raw( get_rest_url( null, 'goft/v1/api_jobs' ) ); ?>',
					data:    data,
					headers: headers,
					success: function(res) {
						if ( ! res || typeof res['data'] === 'undefined' ) {
							return;
						}
						var job = res['data'];
						$('.goftj-logo-exernal-link').attr( 'href', job['link'] );
						$('.job_application .goftj-logo-exernal-link').html( job['link'] );

						if ( typeof job['link_atts']['javascript'] !== 'undefined' ) {
							$.each( job['link_atts']['javascript'].each, function(i, v) {
								$('.goftj-logo-exernal-link').attr( i, job['link_atts']['javascript'] );
							});
						}
					},
					error: function(res){
						//
					}
				}, 'json');
			});

		</script>
<?php
	}

	/**
	 * Fetch the jobs from the original feed to get updated links for the current job.
	 */
	public function get_api_jobs() {
		global $goft_wpjm_options;

		$post_id = (int) sanitize_text_field( $_REQUEST['post_id'] );

		$interval = (int) $this->tracking_update * 60;

		if ( $last_checked = get_post_meta( $post_id, '_goft_api_last_checked', true ) ) {
			$diff = round( ( current_time( 'timestamp' ) - $last_checked ) / 3600 );
		}

		// Skip if update interval is not set or yet met.
		if ( ! $this->tracking_update || ( ! empty( $diff ) && $diff <= $interval ) ) {
			return;
		}

		$jobkey  = get_post_meta( $post_id, '_goft_unique_jobkey', true );
		$jobfeed = get_post_meta( $post_id, '_goft_jobfeed', true );
		$item    = get_post_meta( $post_id, '_goft_wpjm_original_item', true );

		if ( ! $jobfeed || ! $item ) {
			return;
		}

		$jobs = $this->fetch_feed( $jobfeed );

		if ( empty( $jobs ) || is_wp_error( $jobs ) ) {
			return;
		}

		$provider['id'] = $item['provider_id'];
		$result = $this->fetch_feed_items( $jobs, $jobfeed, $provider );

		if ( $filtered_item = wp_list_filter( $result['items'], array( 'jobkey' => $jobkey ) ) ) {
			update_post_meta( $post_id, '_goft_api_last_checked', current_time( 'timestamp' ) );

			$matched_item = array_pop( $filtered_item );

			// Update the link with the new data.
			if ( ! empty( $matched_item['link'] ) ) {
				$matched_item['link'] = apply_filters( 'goft_wpjm_api_updated_job_link', $matched_item['link'], $post_id, $provider['id'] );
			}

			wp_send_json_success( $matched_item );
		} else {
			// Expire the post if option is selected.
			if ( $goft_wpjm_options->adview_expire_jobs ) {
				$post_arr = array(
					'ID'          => $post_id,
					'post_status' => $goft_wpjm_options->setup_expired_status,
				);
				wp_update_post( $post_arr );
			}
		}
		wp_send_json_error();
	}

}
