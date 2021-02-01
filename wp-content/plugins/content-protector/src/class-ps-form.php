<?php

namespace passster;

class PS_Form
{
    /**
     * Get instance of PS_Form
     *
     * @return void
     */
    public static function get_instance()
    {
        $form = new PS_Form();
        return $form;
    }
    
    /**
     * Get password form markup and replace placeholders
     *
     * @return string
     */
    public static function get_password_form()
    {
        ob_start();
        include apply_filters( 'passster_password_form', PASSSTER_PATH . '/src/templates/password-form.php' );
        $password_form = ob_get_contents();
        ob_end_clean();
        $password_form_placeholders = array(
            '[PASSSTER_AUTH]'        => 'passster_password',
            '[PASSSTER_CURRENT_URL]' => \get_the_permalink(),
            '[PS_AJAX_LOADER]'       => PASSSTER_URL . '/assets/public/ps-loader.svg',
        );
        foreach ( $password_form_placeholders as $placeholder => $string ) {
            $password_form = str_replace( $placeholder, $string, $password_form );
        }
        return $password_form;
    }
    
    /**
     * Get captcha form markup and replace placeholders
     *
     * @return string
     */
    public static function get_captcha_form()
    {
        ob_start();
        include apply_filters( 'passster_captcha_form', PASSSTER_PATH . '/src/templates/captcha-form.php' );
        $captcha_form = ob_get_contents();
        ob_end_clean();
        $captcha_form_placeholders = array(
            '[PASSSTER_AUTH]'        => 'passster_captcha',
            '[PASSSTER_CURRENT_URL]' => \get_the_permalink(),
            '[PASSSTER_PLACEHOLDER]' => get_theme_mod( 'passster_form_instructions_placeholder', __( 'Enter the solution', 'content-protector' ) ),
        );
        foreach ( $captcha_form_placeholders as $placeholder => $string ) {
            $captcha_form = str_replace( $placeholder, $string, $captcha_form );
        }
        return $captcha_form;
    }
    
    /**
     * Get recaptcha form markup and replace placeholders
     *
     * @return string
     */
    public static function get_recaptcha_form()
    {
    }
    
    /**
     * Get recaptcha v2 form markup and replace placeholders
     *
     * @return string
     */
    public static function get_recaptcha_v2_form()
    {
    }

}