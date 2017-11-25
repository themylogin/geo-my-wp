<?php // Exit if accessed directlyif ( ! defined( 'ABSPATH' ) ) {	exit; }/** * GMW_Form_Init class *  * Create the form object and display its elements *  * @since 2.6.1 *  * @author FitoussiEyal *  */class GMW_Form {    /**     * The form being displayed     *     * @since 2.6.1     *      * @access public     */    public $form;    	/**	 * Elements that GEO my WP form shortcodes accepts	 *	 * Can be filtered via apply_filters( 'gmw_shortcode_allowed_elements' );	 * 	 * @var array	 */	public $allowed_form_elements = array( 		'search_form', 		'map', 		'search_results', 		'form' 	);	/**	 * Object permalink modifier filter.	 *	 * pass the filter hook that is being used to modify the permalink of the object.	 *	 * This will be used to modify each permalink in the results loop and append the address to it so it 	 *	 * could be used when viwing a single object page.	 *	 * for example the filter for the post permalink is 'the_permalink'	 * 	 * @var boolean | string	 * 	 */	public $object_permalink_hook = false;	/**	 * Address Filters	 * 	 * @var boolean	 */	public $address_filter = false;	/**	 * locations table	 * @var string	 */	protected $locations_table = 'gmw_locations';	/**	 * Locationmeta table	 * @var string	 */	protected $location_meta_table = 'gmw_locationmeta';	/**     * gmw_location database fields that will be pulled in the search query     *     * The fields can be modified using the filter 'gmw_location_query_db_fields'     *      * @var array     */    public $db_fields = array(        //'',        'ID as location_id',        'object_type',        'object_id',        'featured as featured_location',        'user_id',        'latitude as lat',        'longitude as lng',        'street',        'city',        'region_name',        'postcode',        'country_code',        'address',        'formatted_address',        'map_icon'    );    /**     * $objects_id     *     * Holder for the object IDs of the locations pulled from      *     * GEO my WP DB     *      * @var array     */    public $objects_id;    /**     * $locations_data     *     * Holder for the locations data pulled from GEO my WP DB     *      * @var array     */    public $locations_data = array();    /**     * $map_locations     *     * Holder for the location data that will pass to the map generator     *      * @var array     */   	public $map_locations = array();   	/**   	 * $query   	 *   	 * holder for the search query performed for each object    	 *    	 * @var array|object   	 */    public $query = array();    /**     * [$show_results description]     * @var boolean     */    public $show_results = false;	/**     * get_object_data     *     * This is where some data that will pass to the map info-window is generated     *     * as ach object might generate this data differently.      *     * This method will run in the_location() method and will have the $object availabe to use.     *     * For posts types, for example, we will use the function as below:     *     * return array(     *      'url'   => get_permalink( $post_id ),     *      'title' => get_the_title( $post_id ),     *      'image' => get_the_post_thumbnail( $post_id )     * );     *     * @param  [type] $location [description]     * @return [type]           [description]     */    public function get_object_data( $object ) {        return array(            'url'    	=> '#',            'title'  	=> false,            'image_url' => false,            'image'		=> false        );    }    /**	 * Create a custom search query in child class to filter the search results based on location.	 *	 * As an example you can look in the file: geo-my-wp/plugins/posts-locator/includes/class-posts-locator-form.php	 * 	 * @return [type] [description]	 */	public function search_query() {		return array();	}	public function before_search_results() {}	public function after_search_results() {}	/**	 * [__construct description]	 *	 * verify some data and generate default values.	 * 	 * @param array $attr shortcode attributes 	 * @param array $form the form being processed	 */	function __construct( $attr, $form ) {				$this->form = $form;		// get current form element ( form, map, results... )		$this->form['current_element'] = key( $attr );		if ( $this->form['current_element'] == 'results' ) {			$this->form['current_element'] = 'search_results';		}		// verify that the form element is lagit		if ( ! in_array( $this->form['current_element'], $this->allowed_form_elements ) ) {			return trigger_error( __( 'Invalid form type.', 'GMW' ), E_USER_NOTICE );		}		// shortcode attributes		$this->form['params'] = $attr;		// object_type		$this->form['object_type'] = GMW()->addons[$form['addon']]['object_type'];		// get from default values		$this->setup_defaults();	}		/**	 * Verify default form args	 * 		 * @return array 	 */	public function setup_defaults() {		global $gmw_options;				// Get current page slug. Home page and singular slug is different than other pages.		$paged = ( is_front_page() || is_single() ) ? 'page' : 'paged';					$this->form['elements'] 		 = ! empty( $this->form['params']['elements'] ) ? explode( ',', $this->form['params']['elements'] ) : array();		$this->form['url_px']			 = GMW()->url_prefix;		//$this->form['is_mobile']	     = GMW()->is_mobile;		$this->form['region'] 		     = gmw_get_option( 'general_settings', 'country_code', 'US' );		$this->form['language']		     = gmw_get_option( 'general_settings', 'language_code', 'EN' );		$this->form['ajaxurl']			 = GMW()->ajax_url; 			$this->form['submitted'] 		 = false;		$this->form['page_load_action']  = false;			$this->form['form_values']		 = array();		$this->form['user_position'] 	 = false;		$this->form['lat']			     = false;		$this->form['lng']			     = false;		$this->form['org_address']	     = false;		$this->form['get_per_page']      = false;		$this->form['units_array'] 	     = false;		$this->form['radius']		 	 = false;		$this->form['display_map']	 	 = false;		$this->form['display_list'] 	 = false;		$this->form['paged'] 			 = get_query_var( $paged ) ? get_query_var( $paged ) : 1;		$this->form['per_page']		     = -1;		$this->form['labels']		 	 = gmw_get_labels( $this->form );		$this->form['ul_icon']		 	 = ! empty( $this->form['results_map']['your_location_icon'] ) ? $this->form['results_map']['your_location_icon'] : 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png';		$this->form['has_locations']	 = false;		$this->form['results']		     = array();		$this->form['results_count']	 = 0;		$this->form['total_results'] 	 = 0;		$this->form['max_pages']		 = 0;		$this->form['in_widget'] 		 = ! empty( $this->form['params']['widget'] ) ? true : false;				// look for visitor's current position		if ( FALSE !== ( $user_location = gmw_get_user_current_location() ) ) {						$this->form['user_position'] = array(				'address' 	=> $user_location->address,				'lat'		=> $user_location->lat,				'lng'		=> $user_location->lng,				'map_icon'	=> $this->form['ul_icon']			);		} 			// check if form submitted		if ( ! empty( $_GET['action'] ) && $_GET['action'] == $this->form['url_px'].'post' && ! empty( $_GET[$this->form['url_px'].'form'] ) ) {			$this->form['submitted']  	= true;			$this->form['form_values']  = $this->get_form_values();			$this->form['display_map'] 	= $this->form['form_submission']['display_map'];			$this->form['display_list'] = $this->form['form_submission']['display_results'];		// otherwise check if page load results is set		} elseif ( $this->form['current_element'] != 'search_results' && $this->form['page_load_results']['all_locations'] ) {			$this->form['page_load_action'] = true;			$this->form['form_values']  	= $this->get_form_values();			$this->form['display_map'] 		= $gmw['form_submission']['display_map'] = $this->form['page_load_results']['display_map'];			$this->form['display_list'] 	= $this->form['page_load_results']['display_results'];		} 				// for older version. to prevent PHP warnings.		$this->form['search_results']['results_page'] = $this->form['form_submission']['results_page'];		$this->form['search_results']['display_map']  = $this->form['display_map'];		// can modify form values		$this->form = apply_filters( "gmw_default_form_values", 					    $this->form );		$this->form = apply_filters( "gmw_{$this->form['prefix']}_default_form_values", $this->form );	}	/**	 * Get submitted form values from URL	 * 	 * @return [type] [description]	 */	public function get_form_values() {		$output = array();		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {							$query_string = str_replace( $this->form['url_px'], '', $_SERVER['QUERY_STRING'] );			parse_str( $query_string, $output );		}		return $output;	}	/**	 * Get search results page	 * 	 * @return [type] [description]	 */	public function get_results_page() {		// if already contains URL do nothing		if ( ! empty( $this->form['form_submission']['results_page'] ) && strpos( $this->form['form_submission']['results_page'], 'http' ) !== FALSE ){			return $this->form['form_submission']['results_page'];		}		// if this is page ID		if ( ! empty( $this->form['form_submission']['results_page'] ) ) {			return get_permalink( $this->form['form_submission']['results_page'] );		}		// if no page ID set and its in widget, get the results page from settings page		if ( $this->form['in_widget'] ) {						return $this->form['form_submission']['results_page'] = get_permalink( GMW()->options['general_settings']['results_page'] );			} 				// otherwise false.		return false;	}	/**	 * Display search form	 * 	 * @return void	 */	public function search_form() {		//enable/disable form filter		if ( apply_filters( "gmw_{$this->form['ID']}_disable_search_form", false ) ) {			return;		}				// verify search form tempalte		if ( empty( $this->form['search_form']['form_template'] ) || $this->form['search_form']['form_template'] == '-1' ) {			return;		}				// get search form template files		$search_form = gmw_get_template( $this->form['slug'], 'search-forms', $this->form['search_form']['form_template'] );		// enqueue style only once		if ( ! wp_style_is( $search_form['stylesheet_handle'], 'enqueued' ) ) {			wp_enqueue_style( $search_form['stylesheet_handle'], $search_form['stylesheet_url'], array( 'gmw-frontend' ), GMW_VERSION );		}		// temporary for older versions.		// This function should be used in the search form		$this->form['form_submission']['results_page'] = $this->get_results_page();		do_action( "gmw_before_search_form", $this->form, $this );		do_action( "gmw_{$this->form['prefix']}_before_search_form", $this->form, $this );		$gmw = $this->form;		$gmw_form = $this;		include( $search_form['content_path'] );		do_action( "gmw_after_search_form", $this->form, $this );		do_action( "gmw_{$this->form['prefix']}_after_search_form", $this->form, $this );	}	/**	 * Display the map anywhere on the page using the shortcode	 * 	 * @return void 	 */	public function map() {		gmw_results_map( $this->form, false );	}	/**	 * Generate the map element	 * 	 * @return [type] [description]	 */	public function map_element() {		// disable map dynamically		if ( ! apply_filters( 'gmw_trigger_map', true, $this->form ) ) {			return;		}				$map_args = array(			'map_id' 	 	   => $this->form['ID'],			'map_type'		   => $this->form['addon'],			'prefix'		   => $this->form['prefix'],			'info_window_type' => ( ! empty( $this->form['info_window']['iw_type'] ) ) ? $this->form['info_window']['iw_type'] : 'normal',			'group_markers'    => ! empty( $this->form['results_map']['markers_display'] ) ? $this->form['results_map']['markers_display'] : 'normal',			'draggable_window' => isset( $this->form['info_window']['draggable_use'] ) ? true : false		);				$map_options = array(			'zoomLevel' => $this->form['results_map']['zoom_level'],			'mapTypeId'	=> $this->form['results_map']['map_type']		);					$user_position = array(			'lat'			=> $this->form['lat'],			'lng'		 	=> $this->form['lng'],			'address' 	 	=> $this->form['org_address'],			'map_icon'	 	=> $this->form['ul_icon'],			'iw_content'	=> $this->form['labels']['info_window']['your_location'],			'iw_open'	 	=> ! empty( $this->form['results_map']['yl_icon'] ) ? true : false		);		//generate the map		gmw_new_map_element( $map_args, $map_options, $this->map_locations, $user_position, $this->form );	}	/**	 * Verify some data before running the search query	 * 	 * @return void	 */	public function pre_search_query() {		$this->form = apply_filters( 'gmw_pre_search_query_args', $this->form, $this );		// run search query on form submission or page load results		if ( $this->form['submitted'] || $this->form['page_load_action'] )  {			// on page load results			if ( $this->form['page_load_action'] ) {								$this->page_load_results();						// Otherwise, on form submission			// make sure that the form that was submitted is the one we query and display			} elseif ( $this->form['ID'] == absint( $this->form['form_values']['form'] ) ) {								$this->form_submission();			// abort if none of the above			} else {								return;			}						// run the search query using child class			$results = $this->search_query();			$this->form['has_locations'] = ! empty( $results ) ? true : false;			// generate map if needed			if ( $this->form['display_map'] != 'na' && ( $this->form['has_locations'] || ! empty( $this->form['results_map']['no_results_enabled'] ) ) ) {				$this->map_element();			} 			// load main JavaScript and Google API			if ( ! wp_script_is( 'gmw', 'enqueued') ) {				wp_enqueue_script( 'gmw' );			}			$this->show_results = true;		// Otherwise, do something custom		} else {						do_action( 'gmw_main_shortcode_custom_function', $this->form, $this );			do_action( 'gmw_'.$this->form['prefix'].'_main_shortcode_custom_function', $this->form, $this );		}	}	/**	 * form_submission	 * 	 * Generate some data on form submitted 	 * 	 * before search query takes place	 * 	 * @version 3.0	 * 	 * @return [type] [description]	 */	public function form_submission() {				// get form values		$form_values = $this->form['form_values'];		$this->form['radius'] 	    = isset( $form_values['distance'] ) ? $form_values['distance'] : 500;			$this->form['org_address']  = ( isset( $form_values['address'] ) && array_filter( $form_values['address'] ) ) ? implode( ' ', $form_values['address'] ) : '';		$per_page 					= isset( $this->form['form_submission']['per_page'] ) ? current( explode( ",", $this->form['form_submission']['per_page'] ) ) : -1;		$this->form['get_per_page'] = isset( $form_values['per_page'] ) ? $form_values['per_page' ] : $per_page;		$this->form['units_array']  = gmw_get_units_array( isset( $form_values['units'] ) ? $form_values['units'] : 'imperial' );						 		// Get lat/lng if exist in URL		if ( ! empty( $form_values['lat'] ) && ! empty( $form_values['lng'] ) ) {			$this->form['lat'] = $form_values['lat'];			$this->form['lng'] = $form_values['lng'];			 		// Otherwise look for an address to geocode		} elseif ( ! empty( $this->form['org_address'] ) ) {					// include geocoder			include_once( GMW_PATH . '/includes/gmw-geocoder.php' );			if ( function_exists( 'gmw_geocoder' ) ) {				$this->geocoded_location = $this->form['location'] = gmw_geocoder( $this->form['org_address'] );			}			// if geocode was unsuccessful return error message			if ( isset( $this->form['location']['error'] ) ) {								return;						} else {								$this->form['lat'] = $this->form['location']['lat'];				$this->form['lng'] = $this->form['location']['lng'];			}		} 		// filter the form values before running search query		$this->form = apply_filters( "gmw_form_submitted_before_results", 						  $this->form );		$this->form = apply_filters( "gmw_{$this->form['prefix']}_form_submitted_before_results", $this->form );	}	/**	 * page_load_results	 * 	 * Generate some data on page load 	 * 	 * before search query takes place	 * 	 * @version 3.0	 * 	 * @return [type] [description]	 */	public function page_load_results() {		$page_load_options			= $this->form['page_load_results'];		$this->form['org_address']  = '';		$this->form['get_per_page'] = ! empty( $form_values['per_page'] ) ? $form_values['per_page'] : current( explode( ",", $page_load_options['per_page'] ) );		$this->form['radius'] 		= ! empty( $page_load_options['radius'] ) ? $page_load_options['radius'] : 200;		$this->form['units_array']  = gmw_get_units_array( $this->form['page_load_results']['units'] );				// display results based on user's current location		if ( $page_load_options['user_location'] && ! empty( $this->form['user_position'] ) ) {			// get user's current location			$this->form['org_address'] = $this->form['user_position']['address'];			$this->form['lat'] 		   = $this->form['user_position']['lat'];			$this->form['lng'] 		   = $this->form['user_position']['lng'];				//Otherwise look for an address filter		} elseif ( ! empty( $page_load_options['address_filter'] ) ) {			// get the addres value			$this->form['org_address'] = sanitize_text_field( $page_load_options['address_filter'] );			// include the geocoder			include( GMW_PATH . '/includes/gmw-geocoder.php' );			// try to geocode the address			if ( function_exists( 'gmw_geocoder' ) ) {				$this->form['location'] = gmw_geocoder( $this->form['org_address'] );			}			//if geocode was unsuccessful return error message			if ( isset( $this->form['location']['error'] ) ) {				//return $this->no_results( $this->form['location']['error'] );				return false;			} else {				$this->form['lat'] = $this->form['location']['lat'];				$this->form['lng'] = $this->form['location']['lng'];			}		}				// filter the form value before query		$this->form = apply_filters( "gmw_page_load_results_before_results", 						 $this->form );		$this->form = apply_filters( "gmw_{$this->form['prefix']}_page_load_results_before_results", $this->form );	}		/**	 * get_address_filters	 * 	 * Get address fields to filter the search query	 *	 * @since 3.0	 * 	 * @return [type] [description]	 */	public function get_address_filters() {		$address_filters = array();		// if on page load results		if ( $this->form['page_load_action'] ) {						if ( ! empty( $this->form['page_load_results']['city_filter'] ) ) {				$address_filters['city'] = $this->form['page_load_results']['city_filter'];			}			if ( ! empty( $this->form['page_load_results']['state_filter'] ) ) {				$address_filters['region_name'] = $this->form['page_load_results']['state_filter'];			}			if ( ! empty( $this->form['page_load_results']['zipcode_filter'] ) ) {				$address_filters['postcode'] = $this->form['page_load_results']['zipcode_filter'];			}			if ( ! empty( $this->form['page_load_action']['country_filter'] ) ) {				$address_filters['country_code'] = $this->form['page_load_results']['country_filter'];			}		} 				return $address_filters;	}	/**	 * pre_get_locations_data	 * 	 * Prepare data before quering locations	 * 	 * @return [type] [description]	 */	public function pre_get_locations_data() {		$args = array(			'object_type' 	 => $this->form['object_type'],			'lat'		  	 => $this->form['lat'],			'lng'		  	 => $this->form['lng'],			'radius'	  	 => ! empty( $this->form['radius'] ) ? $this->form['radius'] : false,			'units'		  	 => $this->form['units_array']['units'],			'db_fields'		 => $this->db_fields,		);		// address filters		$address_filters = $this->get_address_filters();		$location_meta = ! empty( $this->form['search_results']['location_meta'] ) ? $this->form['search_results']['location_meta'] : false;			// query locations from database		$output = GMW_Location::get_locations_data( $args, $address_filters, $location_meta, $this->locations_table, $this->db_fields, $this->form );		$this->locations_data = $output['locations_data'];		$this->objects_id     = $output['objects_id'];		return $this->objects_id;	}		/**	 * Append the address, coords distance and units to the permalink of the locaiton in the loop	 *	 * This information can be useful when viwing an sinlge object page ( post, member... ) linked from the search results.	 *	 * We can use this data to display it on the map or calculate directions and so on.	 * 	 * @param  string $url the original URL	 * 	 * @return string modified URL with address	 */    public function append_address_to_permalink( $url ) {    	    	// abort if no address    	if ( empty( $this->form['org_address'] ) ) {    		return $url;    	}    	    	    	// get the permalink args    	$url_args = array(			'address' 	=> str_replace( ' ', '+', $this->form['org_address'] ),			'lat'	  	=> $this->form['lat'],			'lng'	  	=> $this->form['lng'],    	);    	// append the address to the permalink    	return esc_url( apply_filters( "gmw_{$this->form['prefix']}_location_permalink", $url. '?'.http_build_query( $url_args ), $url, $url_args ) );    }    /**	 * Generate location data to pass to the map.	 *	 * array contains latitude, longitude, map icon and info window content.	 *	 * @return array 	 */	public function map_location( $location, $info_window = false ) {		return apply_filters( 'gmw_form_map_location_args', array(			'ID'			 => $location->location_id,			'object_type'	 => $location->object_type,			'lat'			 => $location->lat,			'lng'			 => $location->lng,			'map_icon'		 => apply_filters( 'gmw_'.$this->form['prefix'].'_map_icon', 'https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld='.$location->location_count.'|FF776B|000000', $location->map_icon, $location, $this->form, $this ),			'info_window_content' => $info_window		), $this->form, $this );	}	/**	 * The location	 *	 * This method must run within the object results loop. It could be posts loop, members loop and so on.	 *	 * This method provides features attached to each location/object in the loop	 *	 * You need to call it using parent::the_location( $object ) in your child class.	 * 	 	 * @return $object modified object	 */	public function the_location( $object_id, $object ) {				// setup class tag		$object->location_class = 'single-'.$this->form['object_type'];		// check if this is first location in the loop		if ( empty( $this->form['location_count'] ) ) {			// count loop to be able to set the last location at the end of this function			$this->form['loop_count'] = 1;			//start counting the locations			( int ) $this->form['location_count'] = ( $this->form['paged'] == 1 ) ? 1 : ( $this->form['get_per_page'] * ( $this->form['paged'] - 1 ) ) + 1;			do_action( 'gmw_form_the_location_first', $object, $this->form, $this );						$object->location_class .= ' first-location';		} else {			// increase count			$this->form['loop_count'] ++;			$this->form['location_count']++;		}		// location count to display in map markers and list of results		$object->location_count = $this->form['location_count'];        // if location exists, merge it with the object         if ( ! empty( $this->locations_data[$object_id] ) ) {	        	        $location = $this->locations_data[$object_id];						foreach ( $location as $key => $value ) {								// add location data into object				$object->$key = $value;			}			if ( $object->featured_location ) {				$object->location_class .= ' featured-location';			}			// append address to each permalink in the loop	       	if ( apply_filters( 'gmw_append_address_to_permalink', true, $object->object_type, $this ) && ! empty( $this->object_permalink_hook ) ) {	        	add_filter( $this->object_permalink_hook, array( $this, 'append_address_to_permalink' ) );	        }			// get location meta from database if needed	        if ( ! empty( $this->form['search_results']['location_meta'] ) ) {	        	$object->location_meta = gmw_get_location_meta( $object->location_id, $this->form['search_results']['location_meta'] );	        }			// if displaying map, collect some data to pass to the map script	        if ( $this->form['display_map'] != 'na' ) {	        	$object_data = $this->get_object_data( $object );	        	$info_window = gmw_get_info_window_content( $object, $object_data, $this->form );	        	$this->map_locations[] = $this->map_location( $object, $info_window );	        }	    }        //check if last location in the loop        if ( $this->form['loop_count'] == $this->form['results_count'] ) {        	        	$object->location_class .= ' last-location';        	// filter the location when loop ends			apply_filters( 'gmw_form_the_location_last', $object, $this->form, $this );			// unset loop count. We don't need it outside the loop			unset( $this->form['loop_count'] );        }     	// filter each location in the loop		$object = apply_filters( 'gmw_form_the_location', $object, $this->form, $this );        return $object;	}	/**	 * Check if locations exists	 * @return boolean [description]	 */	public function has_locations() {		return $this->form['has_locations'] ? true : false;	}	/**	 * Display the search results.	 *  	 * @return void  	 	*/	public function search_results() {				if ( ! $this->show_results ) {			return;		}   		// get results template file		$results_template = gmw_get_template( $this->form['slug'], 'search-results', $this->form['search_results']['results_template'] );		// enqueue stylesheet if not already enqueued		if ( ! wp_style_is( $results_template['stylesheet_handle'], 'enqueued' ) ) {			wp_enqueue_style( $results_template['stylesheet_handle'], $results_template['stylesheet_url'] );		}		$this->before_search_results();		// if locations found						do_action( 'gmw_have_locations_start', $this->form, $this );		do_action( 'gmw_have_'.$this->form['prefix'].'_locations_start', $this->form, $this );				// generate no results message		if ( ! $this->form['has_locations'] ) {			$this->form['no_results_message'] = $this->no_results_message();		}		$gmw       = $this->form;		$gmw_form  = $this;      	$gmw_query = $this->query;      	// temporary to support older versions of the plugin.      	// This global should now be at the beggining of the results template file.      	global $members_template;        include( $results_template['content_path'] );  				do_action( 'gmw_have_locations_end', $this->form, $this );		do_action( 'gmw_have_'.$this->form['prefix'].'_locations_end', $this->form, $this ); 		$this->after_search_results();	}	/**     * Generate "No results" message     *      * @return [type] [description]     */    public function no_results_message() {		// display geocoder error if failed. Otherwise, show no results message		if ( ! empty( $this->form['location']['error'] ) ) {			$message = $this->form['location']['error'];		} elseif ( empty( $message ) ) {			$message = $this->form['labels']['search_results'][$this->form['prefix'].'_no_results'];		}		return apply_filters( 'gmw_no_results_message', $message, $this->form );	}	/**	 * Display the form elements.	 * 			 * @return void	 */	public function output() {				// do something before teh output		do_action( "gmw_shortcode_start", 				          $this->form );		do_action( "gmw_{$this->form['prefix']}_shortcode_start", $this->form );		// if using the "elements" shortcode attribute to display the form		if ( $this->form['current_element'] == 'form' && ! empty( $this->form['elements'] ) ) {			if ( in_array( 'map', $this->form['elements'] ) ) {				$this->form['display_map'] = 'shortcode';			}			if ( in_array( 'search_results', $this->form['elements'] ) ) {				$this->form['display_list'] = true;			} else {				$this->form['display_list'] = false;			}			// loop through and generate the elements			foreach( $this->form['elements'] as $element ) {				if ( ! in_array( $element, array( 'search_form', 'map', 'search_results' ) ) ) {					continue;				}				if ( method_exists( $this, $element ) ) {										if ( $element == 'search_results' || ( $element == 'map' && ! $this->form['display_list'] ) ) {						$this->pre_search_query();					}					$this->$element();				}			}		// otherwise, generate in normal order		} else { 			// display search form 			if ( $this->form['current_element'] == 'search_form' || $this->form['current_element'] == 'form' ) {				$this->search_form();			}			// display map using shortcode			if ( $this->form['current_element'] == 'map' && $this->form['display_map'] == 'shortcode' ) {								$this->map();				if ( ! $this->form['display_list'] ) {					$this->pre_search_query();				}			}			// display search results			if ( $this->form['display_list'] && in_array( $this->form['current_element'], array( 'form', 'search_results' ) ) ) {				$this->pre_search_query();				if ( $this->show_results ) {					$this->search_results();				}			}		}		// do something after the output		do_action( "gmw_shortcode_end", 						$this->form );		do_action( "gmw_{$this->form['prefix']}_shortcode_end", $this->form );	}}