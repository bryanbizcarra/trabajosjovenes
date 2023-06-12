<?php
/**
 * Loads advanced premium features not available on the free version.
 *
 * @package GoFetch/Admin/Premium/Starter/Features
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Premium_Starter_Features {

	/**
	 * Enable debug mode.
	 */
	public static $debug_mode = false;

	/**
	 * @var The single instance of the class.
	 */
	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function __construct() {
		require_once 'dynamic/admin/class-gofetch-dynamic-settings.php';
		require_once 'class-gofetch-features_more__premium_only.php';
		require_once 'class-gofetch-admin-settings__premium_only.php';
		require_once 'class-gofetch-admin-meta-boxes__premium_only.php';
		require_once 'class-gofetch-rss-providers__premium_only.php';
		require_once 'class-gofetch-rss-providers__premium_only.php';
		require_once 'class-gofetch-scheduler__premium_only.php';

		// Require additional RSS provider dependencies.
		require_once 'provider/class-gofetch-rss-provider-jobmonkey__premium_only.php';
		require_once 'provider/class-gofetch-rss-provider-euroremotejobs__premium_only.php';

		$this->init_hooks();
	}

	/**
	 * Pro+ hooks.
	 */
	function init_hooks() {
		add_filter( 'goft_wpjm_meta_fields', array( $this, 'meta_fields' ), 11 );
		add_filter( 'goft_wpjm_settings_meta_fields_options', array( $this, 'output_meta_fields' ) );
		add_filter( 'goft_wpjm_settings', array( $this, 'proplus_settings' ), 11, 2 );

		add_filter( 'goft_wpjm_prepare_item', array( $this, 'maybe_scrape_meta' ), 10, 2 );
		add_filter( 'goft_wpjm_valid_scraped_item', array( $this, 'validate_matching_keywords' ), 11, 3 );

		add_action( 'gofetch_wpjm_importer', array( 'GoFetch_Scheduler', 'scheduler_manager' ) );
	}

	/**
	 * Output specific PRO+ settings by calling the related setting callback.
	 */
	public function proplus_settings( $fields, $type = '' ) {

		if ( $type && method_exists( $this, 'output_settings_' . $type ) ) {
			$callback = 'output_settings_' . $type;
			return call_user_func( array( $this, $callback ), $fields );
		}
		return $fields;
	}

	/**
	 * Outputs meta fields.
	 */
	public function output_meta_fields( $fields ) {

		$fields[] = array(
			'title' => __( 'Scrape Metadata', 'gofetch-wpjm' ),
			'type'  => 'select',
			'name'  => 'special[scrape][]',
			'extra' => array(
				'data-special' => 'scrape',
				'multiple'     => 'multiple',
				'class'        => 'select2-regular-text',
			),
			'tip'     => __( 'Check this option to force the importer to fetch (scrape) additional/more complete meta data (e.g: longer job descriptions, location, company name, company logo) directly from the provider site, for each job (!). Import will be much slower.', 'gofetch-wpjm' ) .
						'<br/><br/>' . __( 'The scraper will do its best to retrieve missing meta but its not guaranteed that it will always be successful.', 'gofetch-wpjm' ) .
						'<br/><br/><strong>(!)</strong> ' . __( 'If you are looking to get full job descriptions, please remember that some RSS feeds only provide excerpts to make sure users visit their site and/or to avoid copies, so please avoid abusing this feature (having multiple feeds extracting data from the same provider) and make sure the proper attribution to the job provider is always visible to avoid issues with copied descriptions without proper credit.', 'gofetch-wpjm' ),
			'choices' => array(),
			'tr'      => 'temp-tr-hide tr-special-scrape-options tr-advanced tr-advanced-hide',
		);
		return $fields;
	}

	/**
	 * Outputs the date interval settings.
	 */
	protected function output_settings_filter( $fields ) {

		$last_field[] = array_pop( $fields );
		$last_field[] = array_pop( $fields );
		$last_field[] = array_pop( $fields );
		$last_field[] = array_pop( $fields );
		$last_field[] = array_pop( $fields );


		$new_fields = array(
		array(
			'title'  => __( 'Date Span', 'gofetch-wpjm' ),
			'name'   => '_blank',
			'type'   => 'custom',
			'render' => array( $this, 'output_date_span' ),
			'tip'    => __( 'Choose a date interval to filter the job feed results. The date internal is only applied to the jobs your are currently importing. It is not saved in templates.', 'gofetch-wpjm' ),
			'tr'     => 'temp-tr-hide tr-date-span tr-advanced',
		),
		);
		return array_merge( $fields, $new_fields, $last_field );
	}

	/**
	 * Outputs the date interval settings.
	 */
	public function output_date_span( $output ) {

		$atts = array(
		'type'        => 'text',
		'id'          => 'from_date',
		'name'        => 'from_date',
		'class'       => 'span_date field_dependent date-intervals',
		'style'       => 'width: 120px;',
		'placeholder' => __( 'click to choose...', 'gofetch-wpjm' ),
		'readonly'    => true,
		);

		if ( ! empty( $_POST['from_date'] ) ) {
			$atts['value'] = sanitize_text_field( $_POST['from_date'] );
		}

		$output = __( 'From:', 'gofetch-wpjm' ) . ' ' . html( 'input', $atts );

		unset( $atts['value'] );

		$atts['id']   = 'to_date';
		$atts['name'] = 'to_date';

		$atts['placeholder'] = __( 'click to choose...', 'gofetch-wpjm' );

		if ( ! empty( $_POST['to_date'] ) ) {
			$atts['value'] = sanitize_text_field( $_POST['to_date'] );
		}

		$output .= ' ' . __( 'To:', 'gofetch-wpjm' ) . ' ' . html( 'input', $atts );

		$output .= html( 'a', array( 'class' => 'button clear_span_dates', 'data-goft_parent' => 'date-intervals' ), __( 'Clear', 'gofetch-wpjm' ) );

		return $output;
	}

	/**
	 * Outputs pro+ meta fields.
	 */
	public function meta_fields( $fields ) {
		global $goft_wpjm_options;

		$temp_fields = $fields;

		$fields = array(
			array(
				'title' => __( 'Featured', 'gofetch-wpjm' ),
				'type'  => 'checkbox',
				'name'  => 'meta[' . $goft_wpjm_options->setup_field_featured . ']',
				'tip'   => __( 'Check this option to feature all jobs being imported.', 'gofetch-wpjm' ),
				'extra' => array(
					'section'      => 'meta',
					'data-default' => '1',
				),
				'value' => '1',
			),
		);
		return array_merge( $fields, $temp_fields );
	}

	/**
	 * Map the scrape fields to the user selected mappings,
	 */
	protected function scrape_field_mappings( $params ) {
		global $goft_wpjm_options;

		$fields = array();

		$scrape_fields_mappings = array(
			'description' => 'post_content',
			'logo'     => $goft_wpjm_options->setup_field_company_logo,
			'salary'   => $goft_wpjm_options->setup_field_salary,
			'company'  => $goft_wpjm_options->setup_field_company_name,
			'location' => $goft_wpjm_options->setup_field_location,
		);

		foreach ( $scrape_fields_mappings as $key => $value ) {
			foreach ( $params['field_mappings'] as $m_key => $m_value ) {
				if ( $value === $m_value ) {
					$fields[ $key ] = $m_key;
				}
			}
		}
		return $fields;
	}

	/**
	 * Scrapes a website to get additional meta, if requested by the user.
	 *
	 * https://devhints.io/xpath
	 */
	public function maybe_scrape_meta( $item, $params, $retries = 0 ) {
		global $goft_wpjm_options;

		$debug_mode = GoFetch_Premium_Starter_Features::$debug_mode;

		add_filter( 'goft_wpjm_allowed_tags', function( $allowed_tags ) {
			unset( $allowed_tags['fieldset'] );
			return $allowed_tags;
		});

		if ( empty( $item['link'] ) || ! GoFetch_Helper::supports_scraping( $params ) || 'unknown' === $item['provider_id'] ) {

			// __LOG.
			// Maybe log import info.
			$vars = array(
				'context'     => 'GOFT :: SKIPING SCRAPING META',
				'params'      => $params,
				'provider_id' => $item['provider_id'],
			);
			BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

			// __END LOG.
			return $item;
		}

		$provider = GoFetch_RSS_Providers::get_providers( $item['provider_id'] );

		// Confirm that the provider has scraping options and return earlier, if not.
		// Skip scraping if user is trying to scrape only the description and the provider already supports it.
		if ( ! GoFetch_Helper::do_scrape( $params, $provider ) ) {
			return $item;
		}

		$url = $item['link'];

		do {

			$raw_data = wp_safe_remote_get(
				$url,
				array(
					'timeout'     => 10,
					'redirection' => 5,
					'sslverify'   => false,
					'headers'     => GoFetch_Helper::random_headers(),
				) );

			if ( is_wp_error( $raw_data ) ) {

					// Error is probably related with CORS (crossorigin) access.
				if ( false !== strpos( $url, 'https' ) ) {

					// Try again with CORS proxy.
					$url = 'http://crossorigin.me/' . $url;

					$raw_data = wp_safe_remote_get(
						$url,
						array(
							'timeout'     => 10,
							'redirection' => 5,
							'sslverify'   => false,
							'headers'     => GoFetch_Helper::random_headers(),
					) );
				}
			}

			$url = '';

			// Some providers like 'Careerjet' do a redirect for the final job page.
			if ( ! empty( $provider['special']['redirect_url'] ) && empty( $redirected ) ) {

				if ( ! empty( $provider['special']['redirect_url']['query'] ) ) {
					$query = $provider['special']['redirect_url']['query'];
				} else {
					$query = $provider['special']['redirect_url'];
				}

				$dom = new DOMDocument();

				libxml_use_internal_errors( true );

				$dom->loadHTML( wp_remote_retrieve_body( $raw_data ) );

				$xpath = new DOMXPath( $dom );

				$tags  = $xpath->query( $query );

				foreach ( $tags as $tag ) {
					$url = $tag->textContent;
					break;
				}

				// Find the URL on the current page body.
				if ( ! empty( $provider['special']['redirect_url']['delimiter'] ) ) {
					$delimiter = $provider['special']['redirect_url']['delimiter'];
					$parts = explode( $delimiter, $url );
					foreach ( $parts as $part ) {
						if ( filter_var( $part, FILTER_VALIDATE_URL ) !== false ) {
							$url = $part;
							break;
						}
					}
				}
				$redirected = true;

				libxml_clear_errors();
			}
		} while ( $url );

		$changes = array();

		if ( ! is_wp_error( $raw_data ) ) {

			$raw_data = wp_remote_retrieve_body( $raw_data );

			// Get Products.
			$dom = new DOMDocument();

			libxml_use_internal_errors( true );

			$html = GoFetch_Helper::mb_convert_encoding( $raw_data, 'HTML-ENTITIES', 'UTF-8' );

			$dom->loadHTML( $html );

			$xpath = new DOMXPath( $dom );

			// If we get a cloudflare error, retry.
			if ( GoFetch_Importer::content_is_cf_blocked( $html, $xpath ) ) {
				if ( $retries <= apply_filters( 'goft_wpjm_scrape_max_retries', 3 ) ) {
					// Wait a few seconds and retry.
					sleep( rand( 1, 2 ) );
					$this->maybe_scrape_meta( $item, $params, ++$retries );
					return $item;
				}
			}

			$scrap_items = apply_filters( 'goft_wpjm_provider_scrape_items', $provider['special']['scrape'], $item, $params );

			// Move the 'description' to last, to consider nodes that might be excluded.
			if ( isset( $scrap_items['description'] ) ) {
				$_temp = $scrap_items['description'];
				unset( $scrap_items['description'] );
				$scrap_items['description'] = $_temp;
			}

			// Debug scraper
			if ( $debug_mode ) {
				var_dump($item);
				var_dump($scrap_items);
			}
			//

			// Get the scrape fields mappings.
			$scrape_field_mappings = $this->scrape_field_mappings( $params );

			// Make sure we only scrape the fields requested by the user.
			foreach ( $scrap_items as $field => $data ) {

				$tags = array();

				if ( ! in_array( $field, $params['special']['scrape'] ) || apply_filters( 'goft_wpjm_skip_field_scrape', false, $field ) ) {
					continue;
				}

				$query = $data['query'];

				if ( ! empty( $data['evaluate'] ) ) {
					$value = $xpath->evaluate( $query );

					if ( $value ) {
						$tag = new stdClass();
						$tag->textContent = $value;

						$tags[] = $tag;
					}
				} else {
					$tags = $xpath->query( $query );
				}

				// Debug scraper
				if ( $debug_mode ) {
					var_dump("========= Field / Tags ========= ");
					var_dump($field);
					var_dump($tags);
				}
				//

				$content = '';

				foreach ( $tags as $tag ) {

					if ( 'description' === $field ) {

						// First, remove any elements set for exclusion.
						if ( ! empty( $data['exclude'] ) ) {

							foreach( $xpath->query( $data['exclude'] ) as $e ) {
								// Delete this node
								$e->parentNode->removeChild( $e );
							}
						}

						$content = $tag->ownerDocument->saveXML( $tag );

					} else {

						if ( 'logo' === $field ) {
							$temp_content = $tag->textContent;

							// Skip base64 images.
							if ( strpos( $temp_content, 'data:image' ) !== false ) {
								break;
							}

							$content = $temp_content;

							// Check if we got a valid image.
							$file_parts = pathinfo( $content );

							// If image is not valid and there's a lazyload query, try it.
							if ( empty( $file_parts['extension'] ) && ! empty( $data['query_lazyload'] ) ) {
								$tags_lazyload = $tags = $xpath->query( $data['query_lazyload'] );
								foreach ( $tags_lazyload as $tag_lazyload ) {
									$content_alt = $tag_lazyload->textContent;
								}
								if ( $content_alt ) {
									$content = $content_alt;
								}
							}

							// Debug scraper
							if ( $debug_mode ) {
								var_dump("========= LOGO ========= ");
								var_dump($content);
							}
							//

						} else{
							// Remove break lines.
							$content = trim( preg_replace( "/\r|\n/", "", $tag->textContent ) );

							// Remove non-alphanumeric chars.
							//$content = preg_replace( '/^\W+|\W+$/', '', $content );

							if ( 'salary' === $field ) {
								if ( ! empty( $data['currency'] ) ) {
									$content .= ' USD';
								}
							}
						}
					}

					// Trim the text and remove any javascript code.
					$content = trim( preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $content ) );

					if ( '-' === $content ) {
						$content = '';
					}

					// Debug scraper.
					if ( $debug_mode ) {
						var_dump("========= field / content ========= ");
						var_dump($field);
						var_dump($content);
					}
					//
				}

				if ( $content ) {

					if ( 'description' === $field ) {

						$content = GoFetch_Importer::format_description( $content );

					} elseif ( 'logo' === $field ) {

						if ( $data['bgimage'] ) {
							$re = '/url\((.*?)\)/m';
							$str = $content;

							preg_match_all( $re, $str, $matches, PREG_SET_ORDER, 0 );

							if ( ! empty( $matches[0][1] ) ) {
								$content = $matches[0][1];
							}
						}

						// Build absolute path if we get a relative path.
						if ( '/' === $content[0]  ) {
							$provider_parts = parse_url( $item['link'] );
							// For cases like '//jobs.theguardian.com/google.png'
							if ( strpos( $content, $provider_parts['host'] ) >= 0 && false !== strpos( $content, '//' ) ) {
								$content = sprintf( '%1$s:%2$s', $provider_parts['scheme'], $content );

							// For cases like '/google.png'
							} else {
								$content = esc_url_raw( sprintf( '%1$s://%2$s%3$s', $provider_parts['scheme'], $provider_parts['host'], $content ) );
							}
						} else {

							if ( ! empty( $data['base_url'] ) || ! empty( $data['value'] ) ) {

								if ( ! empty( $data['base_url'] ) ) {
									$_url = $data['base_url'];
								} else {
									$_url = $data['value'];
								}
								$content = untrailingslashit( $_url ) . $content;
							}
						}

						if ( $content ) {
							$content = $this->maybe_add_protocol( $content );
						}
					}

					$content = GoFetch_Importer::strip_tags( $content );

					// Keep the changed fields in a special key.
					$item['other']['scrape'][ $field ] = 1;

					// Get the mapped field.
					if ( ! empty( $scrape_field_mappings[ $field ] ) ) {
						$mapped_field = $scrape_field_mappings[ $field ];

						// Enrich both the original field as well as the mapped field.
						$item[ $mapped_field ] = $content;
					}

					// Enrich both the original field as well as the mapped field.
					$item[ $field ] = $content;

					// Debug scraper.
					if ( $debug_mode ) {
						var_dump("-- Sanitized content -- ");
						var_dump($content);
						var_dump("-- Mapped field -- ");
						var_dump($mapped_field);
					}

					$changes[] = $field;
				}
			}
			libxml_clear_errors();
		}

		// __LOG.
		// Maybe log import info.
		$vars = array(
			'context'  => 'GOFT :: SCRAPING META',
			'params'   => $params,
			'raw_data' => ! is_wp_error( $raw_data ) ? 'Y' : $raw_data->get_error_message(),
			'item'     => $item,
			'changes'  => $changes,
		);
		BC_Framework_Debug_Logger::log( $vars, $goft_wpjm_options->debug_log );

		if ( $debug_mode ) {
			exit;
		}

		// Use to invalidate an item after being scraped (e.g: to check for positive/negative keywords).
		if ( ! apply_filters( 'goft_wpjm_valid_scraped_item', true, $item, $params ) ) {
			return false;
		}

		// __END LOG.
		return $item;
	}

	/**
	 * Validate/invalidate an item by checking if it contains positive/negative keywords.
	 */
	public function validate_matching_keywords( $valid, $item, $params ) {
		global $goft_wpjm_options;

		$keywords                    = $params['keywords'];
		$keywords_comparison         = $params['keywords_comparison'];
		$keywords_exclude            = $params['keywords_exclude'];
		$keywords_exclude_comparison = $params['keywords_exclude_comparison'];

		if ( ! $keywords && ! $keywords_exclude ) {
			return $valid;
		}

		if ( $keywords ) {
			$keywords = explode( ',', $keywords );
			$keywords = stripslashes_deep( $keywords );
		}

		if ( $keywords_exclude ) {
			$keywords_exclude = explode( ',', $keywords_exclude );
			$keywords_exclude = stripslashes_deep( $keywords_exclude );
		}

		$content = '';

		if ( 'all' === $goft_wpjm_options->keyword_matching || 'title' === $goft_wpjm_options->keyword_matching ) {
			$content .= $item['title'];
		}

		if ( 'all' === $goft_wpjm_options->keyword_matching || 'content' === $goft_wpjm_options->keyword_matching ) {
			$content .= ' ' . $item['description'];
		}

		$content = trim( $content );

		if ( ( $keywords || $keywords_exclude ) && $content ) {

			// Positive keywords.
			if ( $keywords && ! GoFetch_Helper::match_keywords( $content, $keywords, $keywords_comparison ) ) {
				$valid = false;
			}

			// Negative keywords.
			if ( $keywords_exclude && GoFetch_Helper::match_keywords( $content, $keywords_exclude, $keywords_exclude_comparison ) ) {
				$valid = false;
			}
		}
		return $valid;
	}

	/**
	 * Add protocol to URL if missing.
	 */
	public function maybe_add_protocol( $url ) {
		if ( ! preg_match( '~^(?:f|ht)tps?://~i', $url ) ) {
			$url = "http://{$url}";
		}
		return $url;
	}

}

GoFetch_Premium_Starter_Features::instance();
