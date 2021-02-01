<?php

namespace passster;

class PS_Admin
{
    /**
     * Setup the passster admin area
     *
     * @return void
     */
    public static function init()
    {
        $admin_notice = get_option( 'passster_admin_notice_shown' );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_admin_scripts' ) );
        $general_settings = wp_parse_args( get_option( 'passster_general_settings' ), self::get_defaults( 'passster_general_settings' ) );
        if ( 'on' === $general_settings['toggle_beaver_builder'] ) {
            if ( in_array( 'beaver-builder-lite-version/fl-builder.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'bb-plugin/fl-builder.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                require_once PASSSTER_PATH . '/src/pagebuilder/beaverbuilder/class-ps-beaver-loader.php';
            }
        }
        if ( 'on' === $general_settings['toggle_elementor'] ) {
            if ( did_action( 'elementor/loaded' ) ) {
                require_once PASSSTER_ABSPATH . 'src/pagebuilder/elementor/class-ps-elementor.php';
            }
        }
        /* activate cookie if ajax disabled */
        
        if ( 'on' === $general_settings['toggle_ajax'] ) {
            $general_settings['toggle_cookie'] = 'on';
            update_option( 'passster_general_settings', $general_settings );
        }
        
        $settings = new PS_Settings();
        $is_pro = 'premium';
        $settings->add_section( array(
            'id'    => 'passster_general_settings',
            'title' => __( 'Options', 'content-protector' ),
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'   => 'passster_advanced_cookie_title',
            'type' => 'title',
            'name' => '<h3>' . __( 'Cookie', 'content-protector' ) . '</h3>',
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'      => 'toggle_cookie',
            'type'    => 'toggle',
            'default' => 'on',
            'name'    => __( 'Use Cookie', 'content-protector' ),
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'      => 'passster_cookie_duration',
            'type'    => 'number',
            'name'    => __( 'Cookie Duration', 'content-protector' ),
            'desc'    => __( 'Duration (in days) for your cookie. Once a cookie expires, the user will have to enter the password again.', 'content-protector' ),
            'default' => '2',
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'   => 'passster_advanced_compatibility_mode',
            'type' => 'title',
            'name' => '<h3>' . __( 'Compatibility Mode', 'content-protector' ) . '</h3>',
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'      => 'toggle_ajax',
            'type'    => 'toggle',
            'default' => 'off',
            'name'    => __( 'Reload after successful validation', 'content-protector' ),
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'   => 'passster_advanced_amp_title',
            'type' => 'title',
            'name' => '<h3>' . __( 'AMP', 'content-protector' ) . '</h3>',
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'      => 'toggle_amp',
            'type'    => 'toggle',
            'default' => 'off',
            'name'    => __( 'Activate AMP Support', 'content-protector' ),
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'   => 'passster_advanced_third_party_title',
            'type' => 'title',
            'name' => '<h3>' . __( 'Third-Party Support', 'content-protector' ) . '</h3>',
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'      => 'toggle_vc',
            'type'    => 'toggle',
            'default' => 'off',
            'name'    => __( 'Activate WPBakery Pagebuilder', 'content-protector' ),
            'premium' => $is_pro,
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'      => 'toggle_elementor',
            'type'    => 'toggle',
            'default' => 'off',
            'name'    => __( 'Activate Elementor', 'content-protector' ),
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'   => 'third_party_shortcodes',
            'type' => 'textarea',
            'name' => __( 'Third-Party Shortcodes', 'content-protector' ),
            'desc' => __( 'Add a comma separated list of shortcodes you want to use inside of Passster. Make sure to add the exact shortcode, in case of Contact Form 7 e.x: [contact-form-7 id="44" title="Contact form 1"]. An Example entry could be: [shortcode-1],[shortcode-2],[shortcode-3].<br><b>Only needed if you use use ajax for verification</b>. ', 'content-protector' ),
        ) );
        /*
        $settings->add_field(
        	'passster_general_settings',
        	array(
        		'id'      => 'toggle_beaver_builder',
        		'type'    => 'toggle',
        		'default' => 'on',
        		'name'    => __( 'Activate Beaver Builder', 'content-protector' ),
        	)
        );
        */
        $settings->add_field( 'passster_general_settings', array(
            'id'   => 'passster_advanced_delete_title',
            'type' => 'title',
            'name' => '<h3>' . __( 'Uninstall', 'content-protector' ) . '</h3>',
        ) );
        $settings->add_field( 'passster_general_settings', array(
            'id'   => 'passster_advanced_delete_options',
            'type' => 'checkbox',
            'name' => __( 'Delete Plugin Options On Uninstall', 'content-protector' ),
            'desc' => __( 'If checked, all plugin options will be deleted if the plugin is unstalled.', 'content-protector' ),
        ) );
        $settings->add_section( array(
            'id'    => 'passster_advanced_settings',
            'title' => __( 'External Services', 'content-protector' ),
        ) );
        $settings->add_field( 'passster_advanced_settings', array(
            'id'   => 'passster_recaptcha_title',
            'type' => 'title',
            'name' => '<h3>' . __( 'Google Recaptcha', 'content-protector' ) . '</h3>',
        ) );
        $settings->add_field( 'passster_advanced_settings', array(
            'id'      => 'passster_recaptcha_type',
            'type'    => 'select',
            'name'    => __( 'Recaptcha Version', 'content-protector' ),
            'options' => array(
            'v3' => 'V3 (invisible Captcha)',
            'v2' => 'V2 (Checkbox)',
        ),
        ) );
        $settings->add_field( 'passster_advanced_settings', array(
            'id'      => 'passster_recaptcha_site_key',
            'type'    => 'text',
            'name'    => __( 'Site Key', 'content-protector' ),
            'desc'    => __( 'Add your Google ReCaptcha Site Key', 'content-protector' ),
            'premium' => $is_pro,
        ) );
        $settings->add_field( 'passster_advanced_settings', array(
            'id'      => 'passster_recaptcha_secret',
            'type'    => 'text',
            'name'    => __( 'Secret', 'content-protector' ),
            'desc'    => __( 'Add your Google ReCaptcha Secret', 'content-protector' ),
            'premium' => $is_pro,
        ) );
        $settings->add_field( 'passster_advanced_settings', array(
            'id'      => 'passster_recaptcha_language',
            'type'    => 'text',
            'name'    => __( 'Language', 'content-protector' ),
            'desc'    => __( 'Add your language shortcode. For example "en" for english or "de" for german. ', 'content-protector' ),
            'default' => 'en',
            'premium' => $is_pro,
        ) );
        $settings->add_field( 'passster_advanced_settings', array(
            'id'   => 'passster_bitly_title',
            'type' => 'title',
            'name' => '<h3>' . __( 'Bitly', 'content-protector' ) . '</h3>',
        ) );
        $settings->add_field( 'passster_advanced_settings', array(
            'id'      => 'passster_bitly_access_key',
            'type'    => 'text',
            'name'    => __( 'Bitly Access Token', 'content-protector' ),
            'desc'    => __( 'Add your bitly access token. You can get one here: <a target="_blank" href="https://bitly.com/">bitly.com</a>', 'content-protector' ),
            'premium' => $is_pro,
        ) );
    }
    
    /**
     * Add admin assets
     *
     * @return void
     */
    public static function add_admin_scripts()
    {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
        wp_enqueue_style(
            'passster-admin-css',
            PASSSTER_URL . '/assets/admin/passster-admin.css',
            '1.0',
            'all'
        );
        wp_enqueue_script(
            'passster-admin-js',
            PASSSTER_URL . '/assets/admin/passster-admin' . $suffix . '.js',
            array( 'jquery' ),
            '1.0',
            false
        );
        wp_localize_script( 'passster-admin-js', 'ps_admin_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ) );
    }
    
    /**
     * Register post type "password lists"
     *
     * @return void
     */
    public static function register_password_lists()
    {
    }
    
    /**
     * Set column headers filr filr post type
     *
     * @param  array $columns array of columns.
     * @return array
     */
    public static function set_columns( $columns )
    {
    }
    
    /**
     * Add content to registered columns for filr post type.
     *
     * @param  string $column name of the column.
     * @param  int    $post_id current id.
     * @return void
     */
    public static function set_columns_content( $column, $post_id )
    {
    }
    
    /**
     * Return default based on option name.
     *
     * @param string $option_name name of the option.
     * @return array
     */
    public static function get_defaults( $option_name )
    {
        switch ( $option_name ) {
            case 'passster_general_settings':
                $settings = array(
                    'toggle_cookie'                    => 'on',
                    'toggle_ajax'                      => 'off',
                    'passster_cookie_duration'         => 2,
                    'toggle_amp'                       => 'off',
                    'toggle_vc'                        => 'off',
                    'toggle_elementor'                 => 'off',
                    'toggle_beaver_builder'            => 'off',
                    'passster_advanced_delete_options' => 'off',
                );
                return $settings;
                break;
            case 'passster_advanced_settings':
                $settings = array(
                    'passster_recaptcha_site_key' => '',
                    'passster_recaptcha_type'     => 'v3',
                    'passster_recaptcha_secret'   => '',
                    'passster_recaptcha_language' => '',
                    'passster_bitly_access_key'   => '',
                );
                return $settings;
                break;
        }
    }

}