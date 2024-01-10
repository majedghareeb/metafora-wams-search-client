<?php
// If Gravity Forms isn't loaded, bail.
if (!class_exists('GFForms')) {
	die();
}

/**
 * Class WAMS_GF_Field_Search
 *
 * Handles the behavior of Search field.
 *
 * @since Unknown
 */
class WAMS_GF_Field_Search extends GF_Field
{

	/**
	 * Defines the field type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var string The field type.
	 */
	public $type = 'wams_search';

	public function __construct($data = array())
	{
		parent::__construct($data);
		// add_action('gform_editor_js', array($this, 'editor_script'));
		// add_filter('gform_tooltips', array($this, 'tooltips'));
		add_action('wp_ajax_wams_gf_search', array($this, 'ajax_search'));
		add_action('wp_ajax_nopriv_wams_gf_search', array($this, 'ajax_search'));
		// add_action('gform_enqueue_scripts', array($this, 'enqueue_styles'), 10, 2);
	}

	/**
	 * Defines the field title to be used in the form editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFCommon::get_field_type_title()
	 *
	 * @return string The field title. Translatable and escaped.
	 */
	public function get_form_editor_field_title()
	{
		return esc_attr__('Search', 'gravityforms-search');
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description()
	{
		return esc_attr__('Search is performed when text entered in field.', 'gravityforms-search');
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon()
	{
		return WAMS_GF_SEARCH_URL . '/images/search.svg';
	}

	/**
	 * Defines the field settings available within the field editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array The field settings available for the field.
	 */
	function get_form_editor_field_settings()
	{
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'size_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'default_value_setting',
			'placeholder_setting',
			'description_setting',
			'wams_search_setting',
			'css_class_setting',
			'autocomplete_setting',
		);
	}

	public function get_form_editor_inline_script_on_page_render()
	{

		$script = "jQuery(document).bind( 'gform_load_field_settings', function(event, field, form){" . PHP_EOL;
		$client_types = WAMS_Search_Field_Addon::get_client_types();
		foreach ($client_types as $key => $value) {
			$script .= "jQuery( '#wams_client_type_" . $key . "_value' ).attr( 'checked', field.wams_client_type_" . $key . " == true);" . PHP_EOL;
		}
		$script .= "jQuery( '#wams_search_per_page_value' ).val( field.wams_search_per_page );" . PHP_EOL;
		$script .= "jQuery( '#wams_search_result_format_value' ).val( field.wams_search_result_format );" . PHP_EOL;
		$script .= "});";

		return $script;
	}

	/**
	 * Defines if conditional logic is supported in this field type.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormDetail::inline_scripts()
	 * @used-by GFFormSettings::output_field_scripts()
	 *
	 * @return bool true
	 */
	public function is_conditional_logic_supported()
	{
		return true;
	}

	/**
	 * Returns the field input.
	 *
	 * @since  Unknown
	 * @access public
	 *	 *
	 * @param array      $form  The Form Object.
	 * @param string     $value The value of the input. Defaults to empty string.
	 * @param null|array $entry The Entry Object. Defaults to null.
	 *
	 * @return string The HTML markup for the field.
	 */
	public function get_field_input($form, $value = '', $entry = null)
	{

		// if (is_array($value)) {
		// 	$value = '';
		// }
		$value = esc_attr($value);
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$form_id  = $form['id'];
		$id       = intval($this->id);
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$size          = $this->size;
		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';
		$class_suffix  = $is_entry_detail ? '_admin' : '';
		$class         = $size . $class_suffix;
		$class         = esc_attr($class);

		$instruction_div = '';

		$placeholder_attribute  = $this->get_field_placeholder_attribute();
		$required_attribute     = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute      = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$aria_describedby       = $this->get_aria_describedby();
		$autocomplete_attribute = $this->enableAutocomplete ? $this->get_field_autocomplete_attribute() : '';
		$per_page 	= $this->wams_search_per_page;
		$tabindex = $this->get_tabindex();
		$allowed_types = [];
		$client_types = WAMS_Search_Field_Addon::get_client_types();
		foreach ($client_types as $k => $v) {
			$key = 'wams_client_type_' . $k;
			if (isset($this->{$key}) && $this->{$key} == 1) {
				$allowed_types[] = $v;
			}
		}

		$script = '<script>
        wams_search_' . $field_id . '_working = false;
        var wams_search_' . $field_id . '_timer;
        jQuery( document ).ready( function($) {
            jQuery( "#search_' . $field_id . '" ).on( "keyup", function(){
                clearTimeout( wams_search_' . $field_id . '_timer );
                wams_search_' . $field_id . '_timer = setTimeout( wams_search_' . $field_id . '_done_typing, 500 );
                
                function wams_search_' . $field_id . '_done_typing() {
                    var query = $( "#search_' . $field_id . '" ).val();
                    jQuery( "#wams-gf-search-' . $field_id . '-results" ).html( "" );
                    if ( query.length >= 2 && !wams_search_' . $field_id . '_working ) {
                        wams_search_' . $field_id . '_working = true;
						jQuery( "#field_' . $form_id . '_' . $id . ' .ginput_container_wams_search" ).addClass( "wams-gf-search-loading" );
                        var data = {
                            action: "wams_gf_search",
                            nonce: "' . wp_create_nonce('wams_gf_search_' . $field_id) . '",
                            field_id: "' . $field_id . '",
                            form_id: "' . $form_id . '",
							per_page: "' . $per_page . '",
							client_types: "' . join(',', $allowed_types) . '",
                            search: query};
                        jQuery.post( "' . admin_url('admin-ajax.php') . '", data, function( response ) {
							var results = JSON.parse( response );
							console.log(results.length);
							jQuery( "#field_' . $form_id . '_' . $id . ' .ginput_container_wams_search" ).removeClass( "wams-gf-search-loading" );
                            wams_search_' . $field_id . '_working = false;
                            if ( response == null || results == 0 ) {
								jQuery("#search_' . $field_id . '").val("").delay(1000);
                                jQuery( "#wams-gf-search-' . $field_id . '-results" ).html( "<div class=\'wams-gf-search-noresults\'>' . addslashes(wp_kses_post(apply_filters('gravityforms_search_no_results', __('No results', 'gravityforms-search'), $form_id, $id))) . '</div>" );
                            } else {
                                $.each(results, function(index, result) {
                                    jQuery( "#wams-gf-search-' . $field_id . '-results" ).append( result );
                        		});
                            }
                        });
						 
                    } else {
                        //console.log( "Not long enough" );
                    }
                }

            });

        });
        </script>';

		$field = sprintf("<div class='ginput_container ginput_container_wams_search ginput_container_text'><input id='search_%s' value= '$value' type='text' {$tabindex} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$aria_describedby} {$autocomplete_attribute} %s/><i class='bi bi-search'></i>{$instruction_div}</div>", $field_id, $disabled_text);

		$results = '<select class="wams-gf-search-results" name="input_' . $id . '" id="wams-gf-search-' . $field_id . '-results"><option value="">Please search for Client</option></select>';

		return $script . $field . $results;
	}

	/**
	 * Gets the value of the submitted field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormsModel::get_field_value()
	 * @uses    GF_Field::get_value_submission()
	 * @uses    GF_Field_Phone::sanitize_entry_value()
	 *
	 * @param array $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool  $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values. Defaults to true.
	 *
	 * @return array|string
	 */
	public function get_value_submission($field_values, $get_from_post_global_var = true)
	{

		$value = parent::get_value_submission($field_values, $get_from_post_global_var);
		$value = $this->sanitize_entry_value($value, $this->formId);

		return $value;
	}

	/**
	 * Sanitizes the entry value.
	 *
	 * @since Unknown
	 * @access public
	 *
	 * @used-by GF_Field_Phone::get_value_save_entry()
	 * @used-by GF_Field_Phone::get_value_submission()
	 *
	 * @param string $value   The value to be sanitized.
	 * @param int    $form_id The form ID of the submitted item.
	 *
	 * @return string The sanitized value.
	 */
	public function sanitize_entry_value($value, $form_id)
	{
		$value = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
		return $value;
	}

	/**
	 * Gets the field value when an entry is being saved.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormsModel::prepare_value()
	 * @uses    GF_Field_Phone::sanitize_entry_value()
	 * @uses    GF_Field_Phone::$phoneFormat
	 *
	 * @param string $value      The input value.
	 * @param array  $form       The Form Object.
	 * @param string $input_name The input name.
	 * @param int    $lead_id    The Entry ID.
	 * @param array  $lead       The Entry Object.
	 *
	 * @return string The field value.
	 */
	public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
	{
		$value = $this->sanitize_entry_value($value, $form['id']);
		return $value;
	}

	public function convert_unicode_to_arabic($unicode_string = '')
	{
		$arabic_string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
			return html_entity_decode('&#x' . $match[1] . ';', ENT_COMPAT, 'UTF-8');
		}, $unicode_string);
		return $arabic_string;
	}

	public function ajax_search()
	{
		if (wp_verify_nonce($_POST['nonce'], 'wams_gf_search_' . $_POST['field_id'])) {
			// Do search
			$search_term = sanitize_text_field($_POST['search']);
			$form_id = get_option('wams_input_forms_settings')['client_add_new_form'];
			$client_type_field_id = get_option('wams_input_forms_settings')['client_type_field_id'] ?? 5;
			// echo $form_id;
			$field['choices'] = [];
			if ($form_id) {
				$sorting = array();
				$paging = array(
					'page_size' => $_POST['per_page'],
				);
				$allowed_types = explode(',', sanitize_text_field($_POST['client_types']));

				$total_count = 0;
				// $client_types = WAMS_Search_Field_Addon::get_client_types();
				// foreach ($client_types as $key => $value) {
				// 	$key = 'wams_client_type_' . $key;
				// 	if (isset($this->{$key}) && $this->{$key} == 1) {
				// 		$allowed_types[] = $value;
				// 	}
				// }
				$search_criteria = array(
					'status'        => 'active',
					'field_filters' => array(
						'mode' => 'any',
						array(
							'key'   => '1',
							'operator' => 'contains',
							'value' => $search_term
						),
						array(
							'key'   => 'id',
							'operator' => 'contains',
							'value' => $search_term
						),
					)
				);

				// $search_criteria['status'] = 'active';
				// $search_criteria['field_filters'][] = array('key' => '1', 'operator' => 'contains', 'value' => $search_term);
				// $search_criteria['field_filters'][] = array('key' => 'id', 'operator' => 'contains', 'value' => $search_term);
				// if (!empty($allowed_types)) {
				// 	foreach ($allowed_types as $allowed_type) {
				// 		$search_criteria['field_filters'][] = array('key' => $client_type_field_id, 'operator' => 'contains', 'value' => json_encode());
				// 	}
				// }
				// $search_criteria = array();
				$entries = GFAPI::get_entries($form_id, $search_criteria, $sorting, $paging, $total_count);
				$return = ['<option value="0"></option>'];
				if ($total_count > 0) {

					foreach ($entries as $entry) {
						$choices[] = array(
							'text' => $entry['1'],
							'value' => $entry['id'],
						);
						$client_types = $this->convert_unicode_to_arabic(rgar($entry, $client_type_field_id, ''));
						$client_types = str_replace(['[', ']', '"'], '', $client_types);
						// foreach ($allowed_types as $allowed_type) {
						// 	if (str_contains($client_types, $allowed_type)) {
						// 		$return[] = '<option value="' . $entry['id'] . '">' . $entry['id'] . ':' . $entry['1'] . ' -- ' . $client_types . '</option>';
						// 		break;
						// 	}
						// }
						$return[] = '<option value="' . $entry['id'] . '">' . $entry['1'] . ' | ' . $client_types . '</option>';

						// $return[] = '<a onclick="chooseClient();" >' . $entry['1'] . '</a>';

					}
				}
				echo json_encode($return);
			}
		}
		wp_die();
	}

	function enqueue_styles($form, $is_ajax)
	{
		foreach ($form['fields'] as $field) {
			if ($field['type'] == 'wams_search') {
				wp_enqueue_style('gravityforms-search', WAMS_GF_SEARCH_URL . 'assets/style.css');
				return;
			}
		}
	}
}

// Register the phone field with the field framework.
GF_Fields::register(new WAMS_GF_Field_Search());
