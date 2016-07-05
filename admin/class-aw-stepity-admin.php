<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://enriquechavez.co
 * @since      1.0.0
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @author     Enrique Chavez <noone@tmeister.net>
 */
class Aw_Stepity_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     *
     * @var string The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     *
     * @var string The current version of this plugin.
     */
    private $version;

    private $aweber;

    private $aweber_ready;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        global $stepify_options;

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $stepify_options = get_option('stepify_settings');

        $this->aweber = new AweberProxy();

        $aweber_api_key = isset($stepify_options['aweber_api_key']) ? $stepify_options['aweber_api_key'] : null;
        $this->aweber_ready = $this->aweber->verify_auth_data($aweber_api_key);
    }

    public function add_submenus()
    {
        add_menu_page('Stepify', 'Stepify', 'manage_options', 'stepify', array($this, 'provider_options'), '');

        //add_submenu_page( 'edit.php?post_type=stepify-pact', 'Integrations', __('Settings', $this->stepify), 'manage_options', 'stepify-providers', array($this, 'provider_options') );
    }

    /**
     * Draw the Options pages (Tabs).
     *
     * @since 1.0.0
     */
    public function provider_options()
    {
        $this->providers = $this->get_providers_tabs();
        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        include_once 'partials/providers.php';
    }

    /**
     * Set the initial Providers list.
     *
     * @return [array] Array with the providers list.
     */
    public function get_providers_tabs()
    {
        $providers = array();
        $providers['general'] = __('General', $this->plugin_name);
        $providers['aweber'] = __('aWeber', $this->plugin_name);
        $providers['getresponse'] = __('GetResponse', $this->plugin_name);
        $providers['globalpopup'] = __('Global Popup', $this->plugin_name);

        return apply_filters('stepify_providers', $providers);
    }

    /**
     * Add the sections and fields according with the providers list.
     */
    public function add_providers_settings()
    {
        if (false == get_option('stepify_settings')) {
            add_option('stepify_settings');
        }

        foreach ($this->get_providers_settings() as $tab => $settings) {
            add_settings_section(
                'stepify_settings_'.$tab,
                __return_null(),
                '__return_false',
                'stepify_settings_'.$tab
            );

            foreach ($settings as $option) {
                $name = isset($option['name']) ? $option['name'] : '';

                add_settings_field(
                    'stepify_settings['.$option['id'].']',
                    $name,
                    method_exists($this, 'stepify_'.$option['type'].'_callback') ? array($this, 'stepify_'.$option['type'].'_callback') : array($this, 'stepify_missing_callback'),
                    'stepify_settings_'.$tab,
                    'stepify_settings_'.$tab,
                    array(
                        'section' => $tab,
                        'id' => isset($option['id'])      ? $option['id']      : null,
                        'desc' => !empty($option['desc'])  ? $option['desc']    : '',
                        'name' => isset($option['name'])    ? $option['name']    : null,
                        'size' => isset($option['size'])    ? $option['size']    : null,
                        'options' => isset($option['options']) ? $option['options'] : '',
                        'std' => isset($option['std'])     ? $option['std']     : '',
                        'min' => isset($option['min'])     ? $option['min']     : null,
                        'max' => isset($option['max'])     ? $option['max']     : null,
                        'step' => isset($option['step'])    ? $option['step']    : null,
                    )
                );
            }
        }

        // Creates our settings in the options table
        register_setting('stepify_settings', 'stepify_settings', array($this, 'stepify_settings_sanitize'));
    }

    /**
     * Inital fields for the default providers.
     *
     * @return array
     */
    private function get_providers_settings()
    {
    	global $stepify_options;
        /*
         * Initia Setting using filter to add the hability to add more providers.
         */

        $aweber_auth_url = sprintf('Please visit <a href="%s" target="_blank">this link</a> to grand access to your account and paste the Authorization code in the field.', $this->aweber->get_app_authorize_url());

        /*
         * aWeber Lists
         */
        $alists = array();

        if ($this->aweber_ready) {
            $lists = $this->aweber->get_user_lists();
            if (isset($lists->data['entries'])) {
                foreach ($lists->data['entries'] as $list) {
                    $alists[ $list['id'] ] = $list['name'];
                }
            }
        }

        /*
         * GetResponse Lists
         */
        $grlists = array();
        $gr_api_key = isset($stepify_options['getresponse_api_key']) ? $stepify_options['getresponse_api_key'] : null;

        if ($gr_api_key) {
            $get_response = new Get_Response_Proxy($gr_api_key);

            $campaigns = $get_response->get_campaigns();

            if ($campaigns) {
                $grlists = $this->parse_get_response_campaigns($campaigns);
            }
        }

        $stepify_prov_settings = array(

            /* General Settings */
            'general' => apply_filters('stepify_general_settings',
                array(
                    'aweber_list' => array(
                        'id' => 'mailing_provider',
                        'name' => __('Mailing Provider', 'stepify'),
                        'desc' => __('Please select the Mailing Provider where you want to store your leads.', 'stepify'),
                        'type' => 'select',
                        'options' => array(
                            'none' => __('None', 'stepify'),
                            'aweber' => __('aWeber', 'stepify'),
                            'getresponse' => __('GetResponse', 'stepify'),
                        ),
                    ),
                )
            ),

            /* aWeber Settings */
            'aweber' => apply_filters('stepify_aweber_settings',
                array(
                    'aweber_api_key' => array(
                        'id' => 'aweber_api_key',
                        'name' => __('Authorization code', $this->plugin_name),
                        'desc' => $aweber_auth_url,
                        'type' => 'text',
                    ),
                    'aweber_list' => array(
                        'id' => 'aweber_list',
                        'name' => __('aWeber Lists', 'stepify'),
                        'desc' => __('Choose the list where you want to add the leads.', 'stepify'),
                        'type' => 'select',
                        'options' => $alists,
                    ),
                )
            ),

            /* getResponse Settings */
            'getresponse' => apply_filters('stepify_getresponse_settings',
                array(
                    'getresponse_api_key' => array(
                        'id' => 'getresponse_api_key',
                        'name' => __('API Key code', $this->plugin_name),
                        'desc' => __('Please get your API Key in <a href="https://app.getresponse.com/account.html#api" target="_blank"> your GetResponse account</a>.', 'stepify'),
                        'type' => 'text',
                    ),
                    'getresponse_list' => array(
                        'id' => 'getresponse_list',
                        'name' => __('GetResponse Lists', 'stepify'),
                        'desc' => __('Choose the list where you want to add the leads.', 'stepify'),
                        'type' => 'select',
                        'options' => $grlists,
                    ),
                )
            ),

            /* Global Popup Settings */
            'globalpopup' => apply_filters('stepify_globalpopup_settings',
                array(
                    'global_popup_shortcode' => array(
                        'id' => 'global_popup_source',
                        'name' => __('Global Popup Shortcode', 'stepify'),
                        'desc' => __('Please add the Quiz shortcode to show across the site, ex:<br>[stepify type="id" id="564be7aea502f90f00eeb321"]','stepify'),
                        'type' => 'text'
                    )
                )
            )
        );

        return apply_filters('stepify_providers_settings', $stepify_prov_settings);
    }

    /**
     * Output a message to let the user know that there is no handler for that field.
     *
     * @param array $args
     *
     * @since 1.0.0
     */
    public function stepify_missing_callback($args)
    {
        printf(__('The callback function used for the <strong>%s</strong> setting is missing.', $this->plugin_name), $args['id']);
    }

    public function stepify_info_callback($args)
    {
        global $stepify_options;

        $html = sprintf('%s', $args['desc']);
        echo $html;
    }

    /**
     * Global Callback function to draw the checkbox option in the settings page.
     *
     * @param array $args [description]
     *
     * @since 1.0.0
     */
    public function stepify_checkbox_callback($args)
    {
        global $stepify_options;
        $checked = isset($stepify_options[ $args[ 'id' ] ]) ? checked(1, $stepify_options[ $args[ 'id' ] ], false) : '';
        $html = '<input type="checkbox" id="stepify_settings['.$args['id'].']" name="stepify_settings['.$args['id'].']" value="1" '.$checked.'/>';
        $html .= '<br><label for="stepify_settings['.$args['id'].']"> '.$args['desc'].'</label>';
        echo $html;
    }

    /**
     * Global Callback function to draw the text option in the settings page.
     *
     * @param array $args [description]
     *
     * @since 1.0.0
     */
    public function stepify_text_callback($args)
    {
        global $stepify_options;

        if (isset($stepify_options[ $args['id'] ])) {
            $value = $stepify_options[ $args['id'] ];
        } else {
            $value = isset($args['std']) ? $args['std'] : '';
        }

        $size = (isset($args['size']) && !is_null($args['size'])) ? $args['size'] : 'regular';
        $html = '<input type="text" class="'.$size.'-text" id="stepify_settings['.$args['id'].']" name="stepify_settings['.$args['id'].']" value="'.esc_attr(stripslashes($value)).'"/>';
        $html .= '<br><label for="stepify_settings['.$args['id'].']"> '.$args['desc'].'</label>';

        echo $html;
    }

    /**
     * Global Callback function to draw the password option in the settings page.
     *
     * @param array $args [description]
     *
     * @since 1.0.0
     */
    public function stepify_password_callback($args)
    {
        global $stepify_options;

        if (isset($stepify_options[ $args['id'] ])) {
            $value = $stepify_options[ $args['id'] ];
        } else {
            $value = isset($args['std']) ? $args['std'] : '';
        }

        $size = (isset($args['size']) && !is_null($args['size'])) ? $args['size'] : 'regular';
        $html = '<input type="password" class="'.$size.'-text" id="stepify_settings['.$args['id'].']" name="stepify_settings['.$args['id'].']" value="'.esc_attr($value).'"/>';
        $html .= '<label for="stepify_settings['.$args['id'].']"> '.$args['desc'].'</label>';

        echo $html;
    }

    public function stepify_select_callback($args)
    {
        global $stepify_options;
        if (isset($stepify_options[ $args['id'] ])) {
            $value = $stepify_options[ $args['id'] ];
        } else {
            $value = isset($args['std']) ? $args['std'] : '';
        }
        if (isset($args['placeholder'])) {
            $placeholder = $args['placeholder'];
        } else {
            $placeholder = '';
        }
        $html = '<select id="stepify_settings['.$args['id'].']" name="stepify_settings['.$args['id'].']" '.'data-placeholder="'.$placeholder.'" />';
        foreach ($args['options'] as $option => $name) :
            $selected = selected($option, $value, false);
        $html .= '<option value="'.$option.'" '.$selected.'>'.$name.'</option>';
        endforeach;
        $html .= '</select>';
        $html .= '<label for="stepify_settings['.$args['id'].']"> '.$args['desc'].'</label>';
        echo $html;
    }

    /**
     * Sanitize function.
     *
     * @param array $input
     *
     * @return array
     */
    public function stepify_settings_sanitize($input = array())
    {
        global $stepify_options;

        $stepify_options = is_array($stepify_options) ? $stepify_options : array();

        if (empty($_POST['_wp_http_referer'])) {
            return $input;
        }

        parse_str($_POST['_wp_http_referer'], $referrer);

        $settings = $this->get_providers_settings();
        $tab = isset($referrer['tab']) ? $referrer['tab'] : 'general';

        $input = $input ? $input : array();
        $input = apply_filters('stepify_settings_'.$tab.'_sanitize', $input);

        foreach ($input as $key => $value) {
            $type = isset($settings[$tab][$key]['type']) ? $settings[$tab][$key]['type'] : false;
            if ($type) {
                $input[$key] = apply_filters('stepify_settings_sanitize_'.$type, $value, $key);
            }
            $input[$key] = apply_filters('stepify_settings_sanitize', $input[$key], $key);
        }

        // Loop through the whitelist and unset any that are empty for the tab being saved
        if (!empty($settings[$tab])) {
            foreach ($settings[$tab] as $key => $value) {

                // settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
                if (is_numeric($key)) {
                    $key = $value['id'];
                }

                if (empty($input[$key])) {
                    unset($stepify_options[$key]);
                }
            }
        }

        // Merge our new settings with the existing
        $output = array_merge($stepify_options, $input);

        add_settings_error('stepify-notices', '', __('Settings updated.', $this->plugin_name), 'updated');

        return $output;
    }

    private function parse_get_response_campaigns($campaigns){
		$out = array();
		foreach ($campaigns as $key => $campaign) {
			$out[$key] = $campaign['name'];
		}
		return $out;
	}
}
