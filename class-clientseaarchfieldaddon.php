<?php
GFForms::include_addon_framework();

class WAMS_Search_Field_Addon extends GFAddOn
{

    protected $_version = WAMS_GF_SEARCH_VERSION;
    protected $_min_gravityforms_version = '2.4';
    protected $_slug = 'gravityforms-search';
    protected $_path = 'gravityforms-search/gravityforms-search.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Search Field for Gravity Forms';
    protected $_short_title = 'Search Field';

    private static $_instance = null;

    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function pre_init()
    {
        parent::pre_init();

        if ($this->is_gravityforms_supported() && class_exists('GF_Field')) {
            require_once('includes/class-wams-search-gf-field.php');
        }
    }

    public function init_admin()
    {
        parent::init_admin();

        // add_filter('gform_tooltips', array($this, 'tooltips'));
        add_action('gform_field_standard_settings', array($this, 'field_standard_settings'), 10, 2);
        //add_action( 'gform_editor_js', array( $this, 'editor_script' ) );

    }

    public function field_standard_settings($position, $form_id)
    {
        if ($position == 20) {
?>
<li class="wams_search_setting field_setting">
    <label for="field_admin_label">
        <?php esc_html_e('Select Client Type(s)', 'gravityforms-search'); ?>
    </label>
    <?php
                $client_types = self::get_client_types();
                foreach ($client_types as $client_type_key => $client_type_value) {
                    $type = str_replace(' ', '_', $client_type_value);
                ?>
    <input type="checkbox" id="wams_client_type_<?php echo esc_attr($client_type_key); ?>_value"
        onclick="SetFieldProperty('wams_client_type_<?php echo esc_attr($client_type_key); ?>', this.checked);" />
    <label for="wams_client_type_<?php echo esc_attr($client_type_key); ?>_value"
        class="inline"><?php echo esc_html($client_type_value); ?></label><br />
    <?php } ?>
</li>
<li class="wams_search_setting field_setting">
    <label for="field_admin_label">
        <?php esc_html_e('Max results', 'gravityforms-search'); ?>
    </label>
    <input id="wams_search_per_page_value" type="text" onkeyup="SetFieldProperty('wams_search_per_page', this.value );"
        onchange="SetFieldProperty('wams_search_per_page', this.value );" />
</li>
<?php
        }
    }
    public static function get_client_types()
    {
        $wams_input_forms_settings = get_option('wams_input_forms_settings');

        $clients_form_id = $wams_input_forms_settings ? $wams_input_forms_settings['client_add_new_form'] : false;
        $client_type_field_id = $wams_input_forms_settings ? $wams_input_forms_settings['client_type_field_id'] : false;
        // $client_type_field_id = isset($wams_input_forms_settings['client_type_field_id']) ? get_option('wams_input_fields_settings')['client_type_field_id'] : 7;
        $types =  [];
        if ($clients_form_id && $client_type_field_id) {
            $type = GFAPI::get_field($clients_form_id, $client_type_field_id);
            $choices = ($type['choices']);
            foreach ($choices as $choice) {
                $types[] = $choice['value'];
            }
        }
        return ($types);
    }
    /**
     * Include CSS when the form contains this field.
     *
     * @return array
     */
    public function styles()
    {
        $styles = array(
            array(
                'handle'  => 'wams_field_search',
                'src'     => $this->get_base_url() . '/assets/style.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array('field_types' => array('wams_search'))
                )
            )
        );
        return array_merge(parent::styles(), $styles);
    }
}