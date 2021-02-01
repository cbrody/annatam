<?php

namespace passster;

class PS_Helper
{
    /**
     * Set AMP headers
     *
     * @param string $auth the current $_POST argument.
     * @param string $password the current password.
     * @return void
     */
    public static function set_amp_headers( $auth, $password )
    {
        if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
            
            if ( !empty($_POST) ) {
                header( 'Content-type: application/json' );
                header( 'Access-Control-Allow-Credentials: true' );
                header( 'Access-Control-Allow-Origin: ' . \get_bloginfo( 'url' ) . '.cdn.ampproject.org' );
                header( 'AMP-Access-Control-Allow-Source-Origin: ' . \get_bloginfo( 'url' ) );
                header( 'Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin' );
                header( 'AMP-Redirect-To: ' . \get_the_permalink() );
                header( 'Access-Control-Expose-Headers: AMP-Redirect-To, AMP-Access-Control-Allow-Source-Origin' );
                if ( $password === $_POST['passster_password'] ) {
                    setcookie(
                        'passster',
                        $password,
                        time() + 2592000,
                        '/'
                    );
                }
            }
        
        }
    }
    
    /**
     * Get string between two strings.
     *
     * @param string $string string to find.
     * @param string $start start string.
     * @param string $end end string.
     * @return string
     */
    public static function get_string_between( $string, $start, $end )
    {
        $string = ' ' . $string;
        $ini = strpos( $string, $start );
        if ( $ini == 0 ) {
            return '';
        }
        $ini += strlen( $start );
        $len = strpos( $string, $end, $ini ) - $ini;
        return substr( $string, $ini, $len );
    }
    
    /**
     * Preg matches shortcode and return cleaned output.
     *
     * @param string $content given content.
     * @return string
     */
    public static function get_shortcode_content( $content, $password )
    {
        preg_match( '/\\[passster+.*."' . $password . '".*?[\\]]/', $content, $matches );
        preg_match( '/\\[passster+.*."true".*?[\\]]/', $content, $captcha_matches );
        $string = '';
        
        if ( isset( $matches ) && !empty($matches) ) {
            foreach ( $matches as $match ) {
                $string = self::get_string_between( $content, $match, '[/passster]' );
            }
        } elseif ( isset( $captcha_matches ) && !empty($captcha_matches) ) {
            foreach ( $captcha_matches as $ca_match ) {
                $string = self::get_string_between( $content, $ca_match, '[/passster]' );
            }
        }
        
        return $string;
    }
    
    /**
     * Preg matches shortcode and return cleaned output.
     *
     * @param string $content given content.
     * @return string
     */
    public static function get_partly_content( $content )
    {
        preg_match( '/{partly}+.*.{partly}/', $content, $matches );
        $string = '';
        foreach ( $matches as $match ) {
            $string .= str_replace( '{partly}', '', $match );
        }
        return apply_filters( 'the_content', $string );
    }
    
    /**
     * URL encode with base64
     *
     * @param string $input current input url.
     * @return string
     */
    public static function base64_url_encode( $input )
    {
        return strtr( base64_encode( $input ), '+/=', '-_,' );
    }
    
    /**
     * URL decode with base64
     *
     * @param string $input current base64 encoded url.
     * @return string
     */
    public static function base64_url_decode( $input )
    {
        return base64_decode( strtr( $input, '-_,', '+/=' ) );
    }
    
    /**
     * Get short URL from bitly.
     *
     * @param string $unlock_link unlock URL from Passster.
     * @return string
     */
    public static function get_bitly_url( $unlock_link )
    {
        $advanced_options = wp_parse_args( get_option( 'passster_advanced_settings' ), PS_ADMIN::get_defaults( 'passster_advanced_settings' ) );
        $url = 'https://api-ssl.bitly.com/v3/shorten?longUrl=' . $unlock_link . '&access_token=' . $advanced_options['passster_bitly_access_key'];
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        $result = curl_exec( $ch );
        $response = json_decode( $result );
        return $response->data->url;
    }

}