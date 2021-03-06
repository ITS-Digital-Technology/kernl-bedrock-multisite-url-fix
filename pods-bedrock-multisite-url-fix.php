<?php
/**
 * Plugin Name: PODS Bedrock Multisite URL fixer
 * Description: WP plugin for to fix Multisite URL for Bedrock based sites
 */

namespace Roots\Bedrock;

if ( ! is_multisite() ) {
    return;
}

class URLFixer {
    /** @var Roots\Bedrock\URLFixer Singleton instance */
    private static $instance = null;

    /**
     * Singleton.
     *
     * @return Roots\Bedrock\URLFixer
     */
    public static function instance() {
        if ( null === self::$instance )
            self::$instance = new self();

        return self::$instance;
    }

    /**
     * Add filters to verify / fix URLs.
     */
    public function add_filters() {
        add_filter( 'option_home', array( $this, 'fix_home_url' ) );
        add_filter( 'option_siteurl', array( $this, 'fix_site_url' ) );
        add_filter( 'network_site_url', array( $this, 'fix_network_site_url' ), 10, 3 );
    }

    /**
     * Ensure that home URL does not contain the /wp subdirectory.
     *
     * @param string $value the unchecked home URL
     * @return string the verified home URL
     */
    public function fix_home_url( $value ) {
        if ( '/wp' === substr( $value, -3 ) ) {
            $value = substr( $value, 0, -3 );
        }
        return $value;
    }

    /**
     * Ensure that site URL contains the /wp subdirectory.
     *
     * @param string $value the unchecked site URL
     * @return string the verified site URL
     */
    public function fix_site_url( $value ) {
        if ( '/wp' !== substr( $value, -3 ) ) {
            $value .= '/wp';
        }
        return $value;
    }

    /**
     * Ensure that the network site URL contains the /wp subdirectory.
     *
     * @param string $url    the unchecked network site URL with path appended
     * @param string $path   the path for the URL
     * @param string $scheme the URL scheme
     * @return string the verified network site URL
     */
    public function fix_network_site_url( $url, $path, $scheme ) {
        $path = ltrim( $path, '/' );
        $url = substr( $url, 0, strlen( $url ) - strlen( $path ) );

        if ( 'wp/' !== substr( $url, -3 ) ) {
            $url .= 'wp/';
        }

        return $url . $path;
    }
}


/*
    Host condition block to determine which URL fix to apply
    1. Valet
    2. everything else
*/

if (defined('WP_HOST') && WP_HOST == 'valet') {
    URLFixer::instance()->add_filters();

} else {

    add_filter('network_site_url', function($url, $path, $scheme) {
        $urls_to_fix = [
            '/wp-admin/network/',
            '/wp-login.php',
            '/wp-activate.php',
            '/wp-signup.php',
        ];

        foreach ($urls_to_fix as $maybe_fix_url) {
            $fixed_wp_url = '/wp' . $maybe_fix_url;
            if (stripos($url, $maybe_fix_url) !== false && stripos($url, $fixed_wp_url) === false ) {
                $url = str_replace($maybe_fix_url, $fixed_wp_url, $url);
            }
        }

        return $url;
    }, 10, 3);
}
