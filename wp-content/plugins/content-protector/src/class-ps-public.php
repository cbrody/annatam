<?php

namespace passster;

use  Phpass\Hash ;
class PS_Public
{
    /**
     * Contains instance or null
     *
     * @var object|null
     */
    private static  $instance = null ;
    /**
     * Constructor for PS_Public
     */
    public function __construct()
    {
        add_shortcode( 'content_protector', array( $this, 'render_shortcode' ) );
        add_shortcode( 'passster', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'add_public_scripts' ) );
        add_action( 'wp_ajax_validate_input', array( $this, 'validate_input' ) );
        add_action( 'wp_ajax_nopriv_validate_input', array( $this, 'validate_input' ) );
        add_filter( 'the_content', array( $this, 'filter_the_content' ) );
        add_filter(
            'passster_compatibility_actions',
            array( $this, 'add_compatibilities' ),
            10,
            2
        );
        add_filter( 'et_builder_load_actions', array( $this, 'add_divi_support' ) );
    }
    
    /**
     * Returns instance of PS_Public.
     *
     * @return object
     */
    public static function get_instance()
    {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Validate ajax given input.
     *
     * @return void
     */
    public function validate_input()
    {
        // check nonce.
        
        if ( !wp_verify_nonce( $_POST['nonce'], 'ps-password-nonce' ) ) {
            $response = array(
                'error' => 'Security check failed.',
            );
            print wp_json_encode( $response );
            exit;
        }
        
        // prepare validation.
        $hash = new Hash();
        $type = sanitize_text_field( $_POST['type'] );
        $post_id = sanitize_text_field( $_POST['post_id'] );
        $protection = sanitize_text_field( $_POST['protection'] );
        // check partly.
        
        if ( !isset( $_POST['partly'] ) || empty($_POST['partly']) ) {
            $partly = false;
        } else {
            $partly = sanitize_text_field( $_POST['partly'] );
        }
        
        // check protection.
        if ( !isset( $protection ) || empty($protection) ) {
            $protection = false;
        }
        // Elementor content?
        $elementor_content = $_POST['elementor_content'];
        // prepare content.
        $post = get_post( $post_id );
        $content = apply_filters( 'passster_compatibility_actions', $post->post_content, $post_id );
        switch ( $type ) {
            case 'password':
                $input = sanitize_text_field( $_POST['input'] );
                
                if ( isset( $input ) && !empty($input) ) {
                    $password = sanitize_text_field( $_POST['password'] );
                    
                    if ( $hash->checkPassword( $input, $password ) === true ) {
                        
                        if ( 'full' !== $protection ) {
                            $content = PS_Helper::get_shortcode_content( $content, $input );
                            // If Elementor content available.
                            if ( isset( $elementor_content ) && !empty($elementor_content) ) {
                                $content = PS_Helper::base64_url_decode( $elementor_content );
                            }
                            
                            if ( $partly ) {
                                $partly_content = preg_match( '/{partly}+.*.{partly}/', $content, $matches );
                                $content = str_replace( $matches[0], '', $content );
                            }
                        
                        }
                        
                        do_action( 'passster_validation_success', $input );
                        $response = array(
                            'success' => true,
                            'content' => apply_filters( 'the_content', $content ),
                        );
                        print wp_json_encode( $response );
                        exit;
                    } elseif ( $hash->checkPassword( $input, $partly ) === true ) {
                        $content = PS_Helper::get_partly_content( $content );
                        $response = array(
                            'success' => true,
                            'content' => apply_filters( 'the_content', $content ),
                        );
                        print wp_json_encode( $response );
                        exit;
                    }
                
                }
                
                break;
            case 'captcha':
                $captcha = sanitize_text_field( $_POST['captcha'] );
                
                if ( isset( $captcha ) && !empty($captcha) ) {
                    
                    if ( 'full' !== $protection ) {
                        $content = PS_Helper::get_shortcode_content( $content, 'captcha' );
                        // If Elementor content available.
                        if ( isset( $elementor_content ) && !empty($elementor_content) ) {
                            $content = PS_Helper::base64_url_decode( $elementor_content );
                        }
                    }
                    
                    $response = array(
                        'success' => true,
                        'content' => apply_filters( 'the_content', $content ),
                    );
                    print wp_json_encode( $response );
                    exit;
                }
                
                break;
        }
        // invalid return error.
        $response = array(
            'error' => get_theme_mod( 'passster_form_error_text', __( 'Invalid password.', 'content-protector' ) ),
        );
        print wp_json_encode( $response );
        exit;
    }
    
    /**
     * Render the passster shortcode
     *
     * @param  array  $atts array of attributes.
     * @param  string $content the current content.
     * @return string
     */
    public function render_shortcode( $atts, $content = null )
    {
        // check if valid before restrict anything.
        $valid = PS_Conditional::is_valid( $atts );
        
        if ( $valid ) {
            $content = apply_filters( 'the_content', wpautop( $content ) );
            return apply_filters( 'passster_content', $content );
        }
        
        // do nothing if no atts.
        if ( !isset( $atts ) || empty($atts) ) {
            return;
        }
        $hash = new Hash();
        // form.
        // partly used?
        $partly = '';
        if ( isset( $atts['partly'] ) ) {
            $partly = $hash->hashPassword( $atts['partly'] );
        }
        // full protection used?
        $protection = '';
        if ( isset( $atts['protection'] ) ) {
            $protection = 'full';
        }
        // password.
        
        if ( isset( $atts['password'] ) ) {
            $form = PS_Form::get_password_form();
            $form = str_replace( array(
                '[PASSSTER_PASSWORD]',
                '[PASSSTER_TYPE]',
                '[PASSSTER_PARTLY]',
                '[PASSSTER_PROTECTION]'
            ), array(
                $hash->hashPassword( $atts['password'] ),
                'password',
                $partly,
                $protection
            ), $form );
        }
        
        
        if ( isset( $atts['captcha'] ) ) {
            $form = PS_Form::get_captcha_form();
            $form = str_replace( '[PASSSTER_PROTECTION]', $protection, $form );
        }
        
        // Elementor content?.
        
        if ( isset( $atts['elementor-protected-content'] ) ) {
            $form = str_replace( '[PASSSTER_ELEMENTOR_CONTENT]', PS_Helper::base64_url_encode( $atts['elementor-protected-content'] ), $form );
        } else {
            $form = str_replace( '[PASSSTER_ELEMENTOR_CONTENT]', '', $form );
        }
        
        // headline.
        
        if ( isset( $atts['headline'] ) ) {
            $form = str_replace( '[PASSSTER_FORM_HEADLINE]', $atts['headline'], $form );
        } else {
            $form = str_replace( '[PASSSTER_FORM_HEADLINE]', get_theme_mod( 'passster_form_instructions_headline', __( 'Protected Area', 'content-protector' ) ), $form );
        }
        
        // instruction.
        
        if ( isset( $atts['instruction'] ) ) {
            $form = str_replace( '[PASSSTER_FORM_INSTRUCTIONS]', $atts['instruction'], $form );
        } else {
            $form = str_replace( '[PASSSTER_FORM_INSTRUCTIONS]', get_theme_mod( 'passster_form_instructions_text', __( 'This content is password-protected. Please verify with a password to unlock the content.', 'content-protector' ) ), $form );
        }
        
        // placeholder.
        
        if ( isset( $atts['placeholder'] ) ) {
            $form = str_replace( '[PASSSTER_PLACEHOLDER]', $atts['placeholder'], $form );
        } else {
            $form = str_replace( '[PASSSTER_PLACEHOLDER]', get_theme_mod( 'passster_form_instructions_placeholder', __( 'Enter your password..', 'content-protector' ) ), $form );
        }
        
        // button.
        
        if ( isset( $atts['button'] ) ) {
            $form = str_replace( '[PASSSTER_BUTTON_LABEL]', $atts['button'], $form );
        } else {
            $form = str_replace( '[PASSSTER_BUTTON_LABEL]', get_theme_mod( 'passster_form_button_label', __( 'Submit', 'content-protector' ) ), $form );
        }
        
        // modify id.
        
        if ( isset( $atts['id'] ) ) {
            $form = str_replace( '[PASSSTER_ID]', 'ps-' . $atts['id'], $form );
        } else {
            $form = str_replace( '[PASSSTER_ID]', 'ps-' . wp_rand( 10, 1000 ), $form );
        }
        
        // hide or not.
        
        if ( isset( $atts['hide'] ) && true == $atts['hide'] ) {
            $form = str_replace( '[PASSSTER_HIDE]', ' passster-hide', $form );
        } else {
            $form = str_replace( '[PASSSTER_HIDE]', '', $form );
        }
        
        // set AMP header.
        if ( isset( $atts['amp'] ) ) {
            PS_Helper::set_amp_headers( $atts['amp'], $atts['password'] );
        }
        return $form;
    }
    
    /**
     * Filters the_content with Passster.
     *
     * @param string $content given content.
     * @return string
     */
    public function filter_the_content( $content )
    {
        $post_id = get_the_id();
        $activate_protection = get_post_meta( $post_id, 'passster_activate_protection', true );
        if ( !$activate_protection ) {
            return $content;
        }
        // build atts array to validate.
        $atts = array();
        $protection_type = get_post_meta( $post_id, 'passster_protection_type', true );
        switch ( $protection_type ) {
            case 'password':
                $password = get_post_meta( $post_id, 'passster_password', true );
                $atts['password'] = $password;
                $shortcode = '[passster password="' . $password . '" protection="full"]{content}[/passster]';
                break;
            case 'captcha':
                $atts['captcha'] = true;
                $shortcode = '[passster captcha="true" protection="full"]{content}[/passster]';
                break;
        }
        // check if valid before restrict anything.
        $valid = PS_Conditional::is_valid( $atts );
        if ( $valid ) {
            return $content;
        }
        // replace placeholder with content.
        $shortcode = str_replace( '{content}', $content, $shortcode );
        return do_shortcode( $shortcode );
    }
    
    /**
     * Enqueue scripts for shortcode
     *
     * @return void
     */
    public function add_public_scripts()
    {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
        $advanced_options = wp_parse_args( get_option( 'passster_advanced_settings' ), PS_ADMIN::get_defaults( 'passster_advanced_settings' ) );
        $general_options = wp_parse_args( get_option( 'passster_general_settings' ), PS_ADMIN::get_defaults( 'passster_general_settings' ) );
        wp_enqueue_style(
            'passster-public',
            PASSSTER_URL . '/assets/public/passster-public' . $suffix . '.css',
            '3.3.8',
            'all'
        );
        wp_enqueue_script(
            'passster-cookie',
            PASSSTER_URL . '/assets/public/cookie.js',
            array( 'jquery' ),
            '3.3.8',
            false
        );
        wp_enqueue_script(
            'passster-captcha',
            PASSSTER_URL . '/assets/public/captcha.js',
            array(),
            '3.3.8',
            false
        );
        wp_enqueue_script(
            'passster-public',
            PASSSTER_URL . '/assets/public/passster-public' . $suffix . '.js',
            array( 'jquery', 'passster-cookie', 'passster-captcha' ),
            '3.3.8.8',
            false
        );
        // pre-render shortcodes if options set.
        $shortcodes = array();
        
        if ( isset( $general_options['third_party_shortcodes'] ) && !empty($general_options['third_party_shortcodes']) ) {
            $shortcodes_in_options = explode( ',', $general_options['third_party_shortcodes'] );
            if ( is_array( $shortcodes_in_options ) ) {
                foreach ( $shortcodes_in_options as $shortcode ) {
                    $shortcodes[$shortcode] = do_shortcode( $shortcode );
                }
            }
        }
        
        wp_localize_script( 'passster-public', 'ps_ajax', array(
            'ajax_url'      => admin_url() . 'admin-ajax.php',
            'days'          => $general_options['passster_cookie_duration'],
            'use_cookie'    => $general_options['toggle_cookie'],
            'no_ajax'       => $general_options['toggle_ajax'],
            'nonce'         => wp_create_nonce( 'ps-password-nonce' ),
            'post_id'       => get_the_id(),
            'captcha_error' => get_theme_mod( 'passster_form_error_text', __( 'Sorry, your captcha solution was wrong.', 'content-protector' ) ),
            'recaptcha_key' => '',
            'shortcodes'    => $shortcodes,
        ) );
        // if amp used.
        if ( isset( $general_options['toggle_amp'] ) && 'on' == $general_options['toggle_amp'] ) {
            wp_enqueue_script(
                'passster-amp',
                'https://cdn.ampproject.org/v0/amp-form-0.1.js',
                array( 'jquery' ),
                '3.2',
                false
            );
        }
        // if password type hint used.
        $password_typing = get_theme_mod( 'passster_form_instructions_password_typing' );
        if ( isset( $password_typing ) && true === $password_typing ) {
            wp_enqueue_script(
                'password-typing',
                PASSSTER_URL . '/assets/public/password-typing.js',
                array( 'jquery' ),
                '3.2',
                false
            );
        }
    }
    
    /**
     * Adding compatibility modifications before ajax output.
     *
     * @param  string $content current content.
     * @return string
     */
    public function add_compatibilities( $content, $post_id )
    {
        // Tablepress.
        
        if ( class_exists( 'TablePress' ) ) {
            \TablePress::$controller = \TablePress::load_controller( 'frontend' );
            \TablePress::$controller->init_shortcodes();
        }
        
        // prepare for Visual Composer.
        if ( class_exists( 'WPBMap' ) ) {
            \WPBMap::addAllMappedShortcodes();
        }
        if ( class_exists( '\\Elementor\\Plugin' ) ) {
            if ( \Elementor\Plugin::$instance->db->is_built_with_elementor( $post_id ) ) {
                $content = \Elementor\Plugin::$instance->frontend->get_builder_content( $post_id, true );
            }
        }
        return $content;
    }
    
    /**
     * Add ajax support for Divi builder.
     *
     * @param array $actions array of allowed actions.
     * @return array
     */
    public function add_divi_support( $actions )
    {
        $actions[] = 'validate_input';
        return $actions;
    }

}