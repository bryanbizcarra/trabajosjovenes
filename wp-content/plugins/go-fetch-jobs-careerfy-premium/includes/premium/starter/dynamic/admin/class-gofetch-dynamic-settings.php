<?php
/**
 * Specific premium admin settings that depend on the current job theme/plugin setup.
 *
 * @package GoFetch/Premium/Pro/WPJM/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class GoFetch_Pro_Dynamic_Specific_Settings {

	public function __construct() {
		global $goft_wpjm_options;

		add_action( $goft_wpjm_options->setup_job_category . '_edit_form_fields', array( $this, 'taxonomy_keyword_mapping' ), 10, 2 );
		add_action( $goft_wpjm_options->setup_job_type . '_edit_form_fields', array( $this, 'taxonomy_keyword_mapping' ), 10, 2 );
		add_action( 'edited_' . $goft_wpjm_options->setup_job_category, array( $this, 'save_taxonomy_keyword_mapping' ), 10, 2 );
		add_action( 'edited_' . $goft_wpjm_options->setup_job_type, array( $this, 'save_taxonomy_keyword_mapping' ), 10, 2 );

		add_filter( 'manage_edit-' . $goft_wpjm_options->setup_job_category . '_columns', array( $this, 'taxonomy_custom_column' ) );
		add_filter( 'manage_edit-' . $goft_wpjm_options->setup_job_type . '_columns', array( $this, 'taxonomy_custom_column' ) );
		add_filter( 'manage_' . $goft_wpjm_options->setup_job_category . '_custom_column', array( $this, 'taxonomy_custom_column_value' ), 10, 3 );
		add_filter( 'manage_' . $goft_wpjm_options->setup_job_type . '_custom_column', array( $this, 'taxonomy_custom_column_value' ), 10, 3 );
	}

	/**
	 * Output the keywords/taxonomy mapping field.
	 */
	public function taxonomy_keyword_mapping( $taxonomy ) {
		$keymap = get_term_meta( $taxonomy->term_id, 'keyword_map', true );
?>
		<tr class="form-field term-<?php echo esc_attr( $taxonomy->slug ); ?>-keyword-map-wrap">
			<th scope="row"><label for="<?php echo esc_attr( $taxonomy->slug ); ?>"><?php echo esc_html__( 'GFJ Keyword Mappings', 'gofetch-wpjm' ); ?></label></th>
			<td>
				<textarea name="term_keyword_map" id="keyword-map-<?php echo esc_attr( $taxonomy->slug ); ?>" rows="5" cols="50" class="large-text"><?php echo esc_html( $keymap ); ?></textarea>
				<p class="description"><?php echo esc_html__( 'Comma separated list of keywords that map to this term.', 'gofetch-wpjm' ); ?></p>
					<br/>
						<p class="description"><?php echo esc_html__( "Jobs containing these keywords will be assigned to this term*", 'gofetch-wpjm' ); ?>
							<br/><small><?php echo esc_html__( "(*) depending on your 'Smart Assign' rule", 'gofetch-wpjm' ); ?></small>
						</p>
				</p>
			</td>
		</tr>
<?php
	}

	/**
	 * Save the keywords/taxonomy mapping field.
	 */
	public function save_taxonomy_keyword_mapping( $term_id ) {
		if ( isset( $_POST['term_keyword_map'] ) ) {
			update_term_meta( $term_id, 'keyword_map', sanitize_textarea_field( $_POST['term_keyword_map'] ) );
		}
	}


	/**
	 * Displays an extra column on the taxonomies list.
	 */
	public function taxonomy_custom_column( $columns ) {

		$new_columns = array();

		foreach ( $columns as $key => $col ) {
			if ( 'posts' !== $key ) {
				$new_columns[ $key ] = $col;
			} else {
				$new_columns['gofj_keyword_map'] = __( 'GOFJ Mappings', 'gofetch-wpjm' );
				$new_columns[ $key ] = $col;
			}
		}
		return $new_columns;
	}

	/**
	 * Displays the meta value for the extra taxonomies list column.
	 */
	public function taxonomy_custom_column_value( $content, $column_name, $term_id ) {

		$mappings = get_term_meta( $term_id, 'keyword_map', true );

		switch ( $column_name ) {

			case 'gofj_keyword_map':
				$content = $mappings;
				break;

		}
		return $content;
	}

}
$GLOBALS['goft_wpjm']['pro_dynamic_settings'] = new GoFetch_Pro_Dynamic_Specific_Settings();
