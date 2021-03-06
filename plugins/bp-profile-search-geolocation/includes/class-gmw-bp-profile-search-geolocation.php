<?php
/**
 * GEO my WP BP Profile Search geolocation class
 *
 * @author Eyal Fitoussi
 *
 * @created 3/2/2019
 *
 * @since 3.3
 *
 * @package gmw-bp-profile-search-integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * GMW_BP_Profile_Search_Geolocation
 *
 * @since 3.3
 */
class GMW_BP_Profile_Search_Geolocation {

	/**
	 * __construct function.
	 */
	public function __construct() {

		add_action( 'bps_before_search_form', array( $this, 'generate_location_fields' ), 50 );
		add_action( 'bps_before_filters', array( $this, 'generate_location_field_filter' ), 50 );
		add_filter( 'gmw_bpmdg_form_data', array( $this, 'modify_form_data' ), 50, 2 );
		add_filter( 'gmw_kleo_geo_form_data', array( $this, 'modify_form_data' ), 50, 2 );
		add_filter( 'bps_add_fields', array( $this, 'add_location_field' ), 9 );

		// Proceed with query filter only if BP Members Directory Geolocation is not installed.
		// When installed, we will use its built-in query filter.
		if ( ! class_exists( 'GMW_BP_Members_Directory_Geolocation_Addon' ) ) {
			add_action( 'bp_user_query_uid_clauses', array( $this, 'modify_search_query' ), 500, 2 );
		}
	}

	/**
	 * Generate the location field in BP Profile Search admin.
	 *
	 * This field will serve as a placeholder in the front-end only
	 *
	 * and will be dynamically replaced with the field generated
	 *
	 * using the function generate_location_fields().
	 *
	 * @param  [type] $fields [description].
	 *
	 * @return [type]         [description]
	 */
	public function add_location_field( $fields ) {

		$field = new stdClass();

		$field->group          = 'GEO my WP Location';
		$field->id             = 'gmw_bpsgeo_location';
		$field->code           = 'gmw_location_ph';
		$field->name           = __( 'Location', 'geo-my-wp' );
		$field->description    = '';
		$field->type           = 'gmw_location_ph';
		$field->format         = 'text';
		$field->search         = '';
		$field->sort_directory = 'bps_xprofile_sort_directory';
		$field->get_value      = 'bps_xprofile_get_value';
		$field->options        = array();
		$field->value          = '';

		$fields[] = $field;

		return $fields;
	}

	/**
	 * Generate the location field element for the form.
	 *
	 * The location fields are generated before the form inside a hidden element.
	 *
	 * This hidden element is then being replaced with the original location field
	 *
	 * That is generated by BP Profile search inside the form.
	 *
	 * @param  array $bps_form BPS form.
	 *
	 * @return [type]           [description]
	 */
	public function generate_location_fields( $bps_form ) {

		?>
		<style type="text/css">
			.kleo-main-header {
				display: none;
			}
		</style>
		<?php
		// Get options.
		$bps_form_id = absint( $bps_form->id );
		$bps_options = bps_meta( $bps_form_id );
		$loc_enabled = false;

		// check if location field exists in the form.
		foreach ( $bps_options['field_code'] as $key => $code ) {

			if ( 'gmw_location_ph' === $code ) {
				$loc_enabled = true;
				$field_id    = absint( $key );
			}
		}

		// Abort if location field does not exist the form.
		if ( ! $loc_enabled ) {
			return;
		}

		// Get the location field options.
		$geo_options    = $bps_options['template_options'][ $bps_options['template'] ];
		$address_ph     = ! empty( $geo_options['gmw_bpsgeo_placeholder'] ) ? esc_attr( $geo_options['gmw_bpsgeo_placeholder'] ) : '';
		$address_ac     = ! empty( $geo_options['gmw_bpsgeo_address_autocomplete'] ) ? ' gmw-address-autocomplete' : '';
		$radius_options = ! empty( $geo_options['gmw_bpsgeo_radius'] ) ? explode( ',', trim( $geo_options['gmw_bpsgeo_radius'] ) ) : array( '5', '25', '50', '100' );
		$default_radius = esc_attr( end( $radius_options ) );

		// Get submitted values.
		$form_values   = bps_get_request( 'search' );
		$bpsgeo_values = ! empty( $form_values['gmw_location'] ) ? $form_values['gmw_location'] : array(
			'address'  => '',
			'distance' => '',
			'units'    => '',
		);

		$address_value = ! empty( $bpsgeo_values['address'] ) ? esc_attr( $bpsgeo_values['address'] ) : '';
		?>
		<div class="gmw-bpsgeo-location-fields-wrap" data-form_id="<?php echo $bps_form_id; // WPCS: XSS ok. ?>" style="display:none">

			<label for="gmw-bpsgeo-address-field-<?php echo $bps_form_id; // WPCS: XSS ok. ?>" class="bps-label">
				<strong>
					<?php echo ! empty( $bps_options['field_label'][ $field_id ] ) ? esc_html( $bps_options['field_label'][ $field_id ] ) : ''; ?>
				</strong>
			</label>

			<br>

			<div class="gmw-bpsgeo-location-fields-inner gmw-flexed-wrapper">

				<div id="gmw-bpsgeo-address-field-wrap-<?php echo $bps_form_id; // WPCS: XSS ok. ?>" class="gmw-bpsgeo-address-field-wrap gmw-bpsgeo-location-field-wrap bps-textbox">

					<input 
						type="text"
						id="gmw-bpsgeo-address-field-<?php echo $bps_form_id; // WPCS: XSS ok. ?>"
						class="gmw-bpsgeo-address-field<?php echo $address_ac; // WPCS: XSS ok. ?> form-control"
						name="gmw_location[address]"
						placeholder="<?php echo $address_ph; // WPCS: XSS ok. ?>"
						value="<?php echo $address_value; // WPCS: XSS ok. ?>"
					>
					<?php if ( ! empty( $geo_options['gmw_bpsgeo_locator_button'] ) ) { ?>
						<i class="gmw-bpsgeo-locator-button gmw-locator-button inside gmw-icon-target-light"></i>
					<?php } ?>			
				</div>

				<?php if ( count( $radius_options ) > 1 ) { ?>

					<div class="gmw-bpsgeo-distance-field-wrap gmw-bpsgeo-location-field-wrap bps-selectbox">

						<select class="gmw-bpsgeo-distance-field form-control" name="gmw_location[distance]">

							<option value="" selected="selected">
								<?php esc_html_e( 'Within', 'geo-my-wp' ); ?>	
							</option>

							<?php
							foreach ( $radius_options as $option ) {

								$option   = esc_attr( $option );
								$selected = ( ! empty( $bpsgeo_values['distance'] ) && $option === $bpsgeo_values['distance'] ) ? 'selected="selected"' : '';

								echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>'; // WPCS: XSS ok.
							}
							?>
						</select>
					</div>

				<?php } else { ?>

					<input 
						type="hidden" 
						name="gmw_location[distance]" 
						value="<?php echo $default_radius; // WPCS: XSS ok. ?>"
						class="gmw-bpsgeo-distance-field"
					>
				<?php } ?>

				<?php if ( 'both' === $geo_options['gmw_bpsgeo_units'] ) { ?>

					<div class="gmw-bpsgeo-units-field-wrap bps-selectbox gmw-bpsgeo-location-field-wrap">

						<select class="gmw-bpsgeo-units-field form-control" name="gmw_location[units]">

							<option value="imperial" selected="selected">
								<?php esc_html_e( 'Mi', 'geo-my-wp' ); ?>		
							</option>

							<option value="metric" <?php selected( $bpsgeo_values['units'], 'metric' ); ?>>
								<?php esc_html_e( 'Km', 'geo-my-wp' ); ?>
							</option>

						</select>

					</div>

				<?php } else { ?>
					<input 
						type="hidden"
						class="gmw-bpsgeo-units-field"
						name="gmw_location[units]"
						value="<?php echo esc_attr( $geo_options['gmw_bpsgeo_units'] ); ?>"
					>
				<?php } ?>
			</div>

			<p class="description">
				<?php echo ! empty( $bps_options['field_desc'][ $field_id ] ) ? esc_html( $bps_options['field_desc'][ $field_id ] ) : ''; ?>
			</p>

			<input 
				type="hidden"
				id="gmw-bpsgeo-lat-<?php echo $bps_form_id; // WPCS: XSS ok. ?>"
				class="gmw-bpsgeo-lat gmw-lat"
				name="gmw_location[lat]"
				value="<?php echo ! empty( $bpsgeo_values['lat'] ) ? esc_attr( $bpsgeo_values['lat'] ) : ''; ?>"
			>
			<input 
				type="hidden"
				id="gmw-bpsgeo-lng-<?php echo $bps_form_id; // WPCS: XSS ok. ?>"
				class="gmw-bpsgeo-lng gmw-lng"
				name="gmw_location[lng]"
				value="<?php echo ! empty( $bpsgeo_values['lng'] ) ? esc_attr( $bpsgeo_values['lng'] ) : ''; ?>"
			>
		</div>
		<br>
		<?php
		if ( ! wp_script_is( 'gmw-bpsgeo', 'enqueued' ) ) {
			wp_enqueue_script( 'gmw-bpsgeo' );
		}
	}

	/**
	 * Modify the form data of the BP Members Directory Geolocation extension ( when activated )
	 *
	 * By passing the location field values from BP Profile Search form.
	 *
	 * @param  array $form_data form data of BP Members Directory Geolocation extension.
	 *
	 * @return [type]            [description]
	 */
	public function modify_form_data( $form_data, $gmw_data ) {

		$filter = current_filter();

		if ( 'gmw_bpmdg_form_data' === $filter ) {

			$prefix = 'bpbdg';

		} elseif ( 'gmw_kleo_geo_form_data' === $filter ) {

			if ( 'member' !== $gmw_data->component ) {
				return $form_data;
			}

			$prefix = 'kleo_geo';

		} else {
			return $form_data;
		}

		$form_values = bps_get_request( 'search' );

		// Abort if address was not provied in the form.
		if ( empty( $form_values['gmw_location']['address'] ) ) {
			return $form_data;
		}

		$values = $form_values['gmw_location'];
		$ajax   = defined( 'DOING_AJAX' );

		/**
		 * Modify the address field on page load or during ajax but when all the values match
		 *
		 * Between the BP Members Directory Geolocation extension and the BP Profile Fields Geolocation forms.
		 *
		 * This means that the orderby filter was changed.
		 *
		 * When the form values between the 2 plugins do not match it means that the BP Members Directory Geolocation
		 *
		 * form was submitted and in this case we don't override the values from BP Profile Fields Geolocation.
		 */
		if ( ! $ajax || ( $ajax && $values['address'] === $form_data['address'] && $values['units'] === $form_data['units'] ) && $values['distance'] === $form_data['radius'] ) {

			$form_data['prefix']  = 'bpsgeo';
			$form_data['address'] = $values['address'];
			$form_data['lat']     = $values['lat'];
			$form_data['lng']     = $values['lng'];
			$form_data['radius']  = $values['distance'];
			$form_data['units']   = $values['units'];
		}

		return $form_data;
	}

	/**
	 * Modify memebrs search query to include memebrs based on location.
	 *
	 * This filter takes place when BP Members Directory Geolocation extension is not installed.
	 *
	 * Otherwise, we use the query of BP Members Directory Geolocation extension
	 *
	 * by modifying the form values above using the function above.
	 *
	 * @param  array  $clauses  original BP query clauses.
	 * @param  object $query    original query object.
	 *
	 * @return array  clauses
	 */
	public function modify_search_query( $clauses, $query ) {

		$form_values = bps_get_request( 'search' );

		// Abort is no address was provided.
		if ( empty( $form_values['gmw_location']['address'] ) ) {
			return $clauses;
		}

		global $wpdb;

		$values     = $form_values['gmw_location'];
		$table_name = $wpdb->base_prefix . 'gmw_locations';

		// Use coordinates if provided in submitted values.
		if ( ! empty( $values['lat'] ) && ! empty( $values['lng'] ) ) {

			$lat = $values['lat'];
			$lng = $values['lng'];

			// Otherwise, geocode the address.
		} else {

			$location_data = gmw_geocoder( $values['address'] );

			if ( empty( $location_data ) || isset( $location_data['error'] ) ) {
				return;
			}

			$lat = $location_data['lat'];
			$lng = $location_data['lng'];
		}

		$earth_radius = 'metric' === $values['units'] ? 6371.0088 : 3958.7613;

		$sql = array(
			'select'  => '',
			'where'   => array(),
			'having'  => '',
			'orderby' => '',
		);

		$sql['select'] = $wpdb->prepare(
			"
			SELECT object_id, ROUND( %s * acos( cos( radians( %s ) ) * cos( radians( gmw_locations.latitude ) ) * cos( radians( gmw_locations.longitude ) - radians( %s ) ) + sin( radians( %s ) ) * sin( radians( gmw_locations.latitude ) ) ),1 ) AS distance 
			FROM {$wpdb->base_prefix}gmw_locations gmw_locations",
			array( $earth_radius, $lat, $lng, $lat )
		);

		$sql['where'] = "WHERE object_type = 'user'";

		if ( ! empty( $values['distance'] ) ) {
			$sql['having'] = $wpdb->prepare( 'Having distance <= %s OR distance IS NULL', $values['distance'] );
		}

		$sql['orderby'] = 'ORDER BY distance';

		// Get users id based on location.
		$results = $wpdb->get_col( implode( ' ', $sql ), 0 ); // WPCS: db call ok, unprepared sql ok, cache ok.

		// Abort if no users were found.
		if ( empty( $results ) ) {

			$clauses['where'][] = '1 = 0';

			return $clauses;
		}

		// Get the user ID column based on the orderby type.
		$column = in_array( $query->query_vars['type'], array( 'active', 'newest', 'popular', 'online' ), true ) ? 'user_id' : 'ID';

		$users_id           = implode( ', ', esc_sql( $results ) );
		$clauses['where'][] = "u.{$column} IN ( {$users_id } )";

		return $clauses;
	}

	/**
	 * Generate location filter text.
	 *
	 * @param  array $fields form fields.
	 *
	 * @return [type]         [description]
	 */
	public function generate_location_field_filter( $fields ) {

		$form_values = bps_get_request( 'search' );

		// Abort if not searching by location.
		if ( empty( $form_values['gmw_location']['address'] ) ) {
			return;
		}

		$values      = $form_values['gmw_location'];
		$bps_options = bps_meta( $form_values['bp_profile_search'] );
		$geo_options = $bps_options['template_options'][ $bps_options['template'] ];

		$mi_label = __( 'Miles', 'geo-my-wp' );
		$km_label = __( 'km', 'geo-my-wp' );
		$label    = 'metric' === $values['units'] ? $km_label : $mi_label;

		if ( ! empty( $values['distance'] ) ) {
			/* translators: %1$s: distance, %2$s: units, %3$s: address */
			$text = sprintf( esc_html__( 'within %1$s %2$s of %3$s', 'geo-my-wp' ), $values['distance'], $label, $values['address'] );
		} else {
			/* translators: %1$s: address */
			$text = sprintf( esc_html__( 'nearby %1$s', 'geo-my-wp' ), $values['address'] );
		}

		$filter          = new stdClass();
		$filter->label   = ! empty( $geo_options['gmw_bpsgeo_label'] ) ? esc_html( $geo_options['gmw_bpsgeo_label'] ) : __( 'Location', 'geo-my-wp' );
		$filter->value   = $text;
		$filter->values  = array();
		$filter->options = array();
		$filter->filter  = '';
		$filter->order   = 0;

		$fields->fields[] = $filter;
	}
}
new GMW_BP_Profile_Search_Geolocation();
?>
