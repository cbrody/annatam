<?php

if ( !function_exists( 'ps_fs' ) ) {
    /**
     * Initialze freemius
     *
     * @return array
     */
    function ps_fs()
    {
        global  $ps_fs ;
        
        if ( !isset( $ps_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $ps_fs = fs_dynamic_init( array(
                'id'             => '1938',
                'slug'           => 'content-protector',
                'type'           => 'plugin',
                'public_key'     => 'pk_9d9d6d17bd34372b199f36e37dd4b',
                'is_premium'     => false,
                'premium_suffix' => '',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                'days'               => 7,
                'is_require_payment' => false,
            ),
                'menu'           => array(
                'slug'           => 'passster',
                'override_exact' => true,
                'contact'        => false,
                'support'        => false,
                'parent'         => array(
                'slug' => 'passster',
            ),
            ),
                'is_live'        => true,
            ) );
        }
        
        return $ps_fs;
    }
    
    // Init Freemius.
    ps_fs();
    // Signal that SDK was initiated.
    do_action( 'ps_fs_loaded' );
    /**
     * Return freemius settings URL
     *
     * @return string
     */
    function ps_fs_settings_url()
    {
        return admin_url( 'options-general.php?page=passster' );
    }
    
    ps_fs()->add_filter( 'connect_url', 'ps_fs_settings_url' );
    ps_fs()->add_filter( 'after_skip_url', 'ps_fs_settings_url' );
    ps_fs()->add_filter( 'after_connect_url', 'ps_fs_settings_url' );
    ps_fs()->add_filter( 'after_pending_connect_url', 'ps_fs_settings_url' );
}

/**
 * Clean up passster settings after uninstallation
 *
 * @return void
 */
function passster_cleanup()
{
    $advanced_options = get_option( 'passster_advanced_settings' );
    if ( isset( $advanced_options ) ) {
        
        if ( 'on' === $advanced_options['passster_advanced_delete_options'] ) {
            $options = array( 'passster_general_settings', 'passster_advanced_settings' );
            
            if ( is_multisite() ) {
                foreach ( $options as $option ) {
                    delete_site_option( $option );
                }
            } else {
                foreach ( $options as $option ) {
                    delete_option( $option );
                }
            }
            
            global  $wpdb ;
            /* delete all customer groups */
            $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type='password_lists'" );
            /* delete all assigned meta */
            $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'passster_%'" );
        }
    
    }
}

ps_fs()->add_action( 'after_uninstall', 'passster_cleanup' );