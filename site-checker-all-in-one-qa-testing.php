<?php
/*
Plugin Name: Site Checker: All-in-One QA Testing, Speed, Link & Security Audit — Community
Plugin URI: https://wpsitechecker.com/
Description: WP Site Checker, every QA Tester's one-stop toolbox for all their site testing and optimization needs. Made by QA, for QA.
Version: 1.2.2
Text Domain: site-checker-all-in-one-qa-testing
Author: Genetech Solutions
Author URI: https://www.genetechsolutions.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Redirect to welcome page on first activation
register_activation_hook( __FILE__, 'sitechal_set_activation_redirect' );
function sitechal_set_activation_redirect() {
    add_option( 'sitechal_do_activation_redirect', true );
}

add_action( 'admin_init', 'sitechal_activation_redirect' );
function sitechal_activation_redirect() {
    if ( get_option( 'sitechal_do_activation_redirect', false ) ) {
        delete_option( 'sitechal_do_activation_redirect' );
        if ( ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=site-checker-all-in-one-qa-testing' ) );
            exit;
        }
    }
}

// Action for not showing notices from other plugins on our plugin pages
add_action( 'admin_head', 'sitechal_suppress_foreign_notices', 1 );

function sitechal_suppress_foreign_notices() {
    $screen = get_current_screen();
    if ( ! $screen ) return;

    $our_pages = [
        'toplevel_page_site-checker-all-in-one-qa-testing',
        'site-checker_page_sitechal-wordpress-checks',
        'site-checker_page_sitechal-wordpress-security-checks',
        'site-checker_page_sitechal-general-checks',
        'site-checker_page_sitechal-automation',
        'site-checker_page_sitechal-about-us',
        'site-checker_page_sitechal-frequently-used-features',
    ];

    if ( in_array( $screen->id, $our_pages, true ) ) {
        // Wipe all foreign notices
        remove_all_actions( 'admin_notices' );
        remove_all_actions( 'all_admin_notices' );
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'network_admin_notices' );

        // Re-add only our promo banner
        add_action( 'admin_notices', 'sitechal_promo_banner' );
    }
}

function sitechal_promo_banner() {
    echo '
        <div class="wp-site-checker-promo">
            <span><strong>Use "GOPRO" to avail 20% discount on Yearly Plan.</strong></span>
            <a href="https://wpsitechecker.com/pricing/?utm_source=adminarea&utm_medium=banner&utm_campaign=20_off_yearly" target="_blank">Get Offer →</a>
        </div>
    ';
}

// Register custom cron intervals
add_filter( 'cron_schedules', 'sitechal_add_cron_intervals' );
function sitechal_add_cron_intervals( $schedules ) {
    $schedules['biweekly'] = [
        'interval' => 14 * DAY_IN_SECONDS,
        'display'  => __( 'Every Two Weeks', 'site-checker-all-in-one-qa-testing' ),
    ];
    $schedules['monthly'] = [
        'interval' => 30 * DAY_IN_SECONDS,
        'display'  => __( 'Once a Month', 'site-checker-all-in-one-qa-testing' ),
    ];
    $schedules['quarterly'] = [
        'interval' => 90 * DAY_IN_SECONDS,
        'display'  => __( 'Every Three Months', 'site-checker-all-in-one-qa-testing' ),
    ];

    return $schedules;
}

// Load Composer autoloader only if not already loaded
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    $autoloader_file = __DIR__ . '/vendor/autoload.php';
    
    // Check if already loaded by checking for a known class or by tracking
    if (!defined('SITECHAL_AUTOLOADER_LOADED')) {
        require_once $autoloader_file;
        define('SITECHAL_AUTOLOADER_LOADED', true);
    }
}

/**
 * Show promo banner only on plugin pages
 */
add_action( 'admin_notices', function() {
    $screen = get_current_screen();
    
    // Define your plugin page IDs
    $plugin_pages = [
        'toplevel_page_site-checker-all-in-one-qa-testing',
        'site-checker_page_sitechal-wordpress-checks',
        'site-checker_page_sitechal-wordpress-security-checks',
        'site-checker_page_sitechal-general-checks',
        'site-checker_page_sitechal-automation',
        'site-checker_page_sitechal-frequently-used-features',
        'site-checker_page_sitechal-about-us',
    ];

    // Only show banner on plugin pages
    if ( ! in_array( $screen->id, $plugin_pages, true ) ) {
        return;
    }

    echo '
        <div class="wp-site-checker-promo">
            <span><strong>Use "GOPRO" to avail 20% discount on Yearly Plan.</strong></span>
            <a href="https://wpsitechecker.com/pricing/?utm_source=adminarea&utm_medium=banner&utm_campaign=20_off_yearly" target="_blank">Get Offer →</a>
        </div>
    ';
});

/**
 * Load custom CSS only on this plugin pages
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Check if we're on one of the plugin's pages
    $plugin_pages = [
        'toplevel_page_site-checker-all-in-one-qa-testing',
        'site-checker_page_sitechal-wordpress-checks',
        'site-checker_page_sitechal-wordpress-security-checks',
        'site-checker_page_sitechal-general-checks',
        'site-checker_page_sitechal-wordpress-license'
    ];

    if ( ! in_array( $hook, $plugin_pages, true ) ) {
        return;
    }
});

// Define constants
if (!defined('SITECHAL__DIR')) {
    define('SITECHAL__DIR', plugin_dir_path(__FILE__));
}
if (!defined('SITECHAL__URL')) {
    define('SITECHAL__URL', plugin_dir_url(__FILE__));
}

// Include necessary files
require_once SITECHAL__DIR . 'includes/plugin-ui.php';
require_once SITECHAL__DIR . 'includes/plugin-functions.php';

// Load zxcvbn library only if not already loaded
if (file_exists(plugin_dir_path(__FILE__) . 'includes/libraries/zxcvbn-php/vendor/autoload.php')) {
    if (!defined('SITECHAL_ZXCVBN_LOADED')) {
        require_once plugin_dir_path(__FILE__) . 'includes/libraries/zxcvbn-php/vendor/autoload.php';
        define('SITECHAL_ZXCVBN_LOADED', true);
    }
}

// Prevent activation if premium version is active
register_activation_hook( __FILE__, 'site_checker_free_activate' );
function site_checker_free_activate() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    // All possible premium plugin paths
    $premium_plugins = array(
        'Site Checker Premium - Plus/site-checker-all-in-one-qa-testing.php',
        'Site Checker Premium - Basic/site-checker-all-in-one-qa-testing.php',
        'Site Checker Premium - Agency/site-checker-all-in-one-qa-testing.php',
    );

    foreach ( $premium_plugins as $premium_plugin ) {
        if ( is_plugin_active( $premium_plugin ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die(
                esc_html__( 'Site Checker Premium is already active. Please deactivate it before activating the Community version.', 'site-checker-all-in-one-qa-testing' ),
                esc_html__( 'Plugin Activation Error', 'site-checker-all-in-one-qa-testing' ),
                array( 'back_link' => true )
            );
        }
    }
}

// Hook for adding menu
add_action('admin_menu', 'sitechal_add_admin_menu');

// Hook for enqueueing assets
if (!function_exists('sitechal_enqueue_assets')) {
    add_action('admin_enqueue_scripts', 'sitechal_enqueue_assets');
    
    function sitechal_enqueue_assets($hook) {
        // Load assets only on plugin pages
        if (strpos($hook, 'site-checker') === false) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style('site-checker-styles', SITECHAL__URL . 'assets/css/styles.css', [], '1.0.0');

        // Enqueue JS
        wp_enqueue_script('site-checker-scripts', SITECHAL__URL . 'assets/js/scripts.js', ['jquery'], '1.0.0', true);
        wp_localize_script('site-checker-scripts', 'sitechalAutomationData', [
            'cronNonce' => wp_create_nonce('sitechal_auto_update_cron'),
        ]);
    }
}

// Actions to Update Themes and Wordpress Versions
if (!function_exists('sitechal_update_single_theme_handler')) {
    add_action('admin_post_update_single_theme', 'sitechal_update_single_theme_handler');
    
    function sitechal_update_single_theme_handler() {
        if (!current_user_can('update_themes')) {
            wp_die(esc_html__('You do not have sufficient permissions to update themes.', 'site-checker-all-in-one-qa-testing'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update_theme_nonce')) {
            wp_die(esc_html__('Security check failed.', 'site-checker-all-in-one-qa-testing'));
        }

        if (empty($_POST['theme_slug'])) {
            wp_die(esc_html__('Missing theme slug', 'site-checker-all-in-one-qa-testing'));
        }

        $theme_slug = sanitize_text_field(wp_unslash($_POST['theme_slug']));

        // No direct require_once calls — instead, check and load via admin functions.
        if ( ! class_exists( 'Theme_Upgrader' ) ) {
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        $upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
        $result = $upgrader->upgrade( $theme_slug );

        if (is_wp_error($result)) {
            wp_die(
                esc_html__('Theme update failed:', 'site-checker-all-in-one-qa-testing') . ' ' . esc_html($result->get_error_message())
            );
        }

        wp_safe_redirect(wp_get_referer());
        exit;
    }
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sitechal_add_premium_link');

function sitechal_add_premium_link($links) {
    $premium_link = '<a href="https://wpsitechecker.com/pricing/" target="_blank" style="color: rgb(0, 163, 42);font-weight: 700;">Go Premium</a>';
    
    // Add the premium link at the beginning
    array_unshift($links, $premium_link);
    
    return $links;
}

if (!function_exists('sitechal_handle_wp_core_update')) {
    add_action('admin_post_qa_update_wp_core', 'sitechal_handle_wp_core_update');
    
    function sitechal_handle_wp_core_update() {
          if (
            isset($_SERVER['REQUEST_METHOD']) &&
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            current_user_can('update_core') &&
            isset($_POST['_wpnonce']) &&
            wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['_wpnonce'])),
                'qa_update_wp_core_action'
            )
        ){
            // Load class only if not already loaded
            if ( ! class_exists( 'Core_Upgrader' ) ) {
                include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            }

            $upgrader = new Core_Upgrader( new Automatic_Upgrader_Skin() );
            $result   = $upgrader->upgrade( 'latest' );

            if ( is_wp_error( $result ) ) {
                wp_die(
                    esc_html__( 'WordPress update failed:', 'site-checker-all-in-one-qa-testing' ) . ' ' .
                    esc_html( $result->get_error_message() )
                );
            }

            wp_safe_redirect( admin_url( 'tools.php?page=site-checker-dashboard' ) );
            exit;
        } else {
            wp_die(
                esc_html__( 'You are not allowed to update WordPress.', 'site-checker-all-in-one-qa-testing' )
            );
        }
    }
}

// Redirect "Go Premium" menu click to external pricing page
add_action( 'admin_init', 'sitechal_go_premium_redirect' );
function sitechal_go_premium_redirect() {

    if ( ! isset( $_GET['page'] ) ) {
        return;
    }

    if ( sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'sitechal-go-premium' ) {
        return;
    }

    wp_safe_redirect( 'https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=upgrade&utm_campaign=adminarea' );
    exit;
}

// Make "Go Premium" submenu open external pricing page in a new tab.
add_action( 'admin_footer', 'sitechal_go_premium_new_tab_link' );
function sitechal_go_premium_new_tab_link() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var premiumLinks = document.querySelectorAll('#adminmenu a[href*="page=sitechal-go-premium"]');
        premiumLinks.forEach(function (link) {
            link.classList.add('sitechal-go-premium-link');
            link.setAttribute('href', '<?php echo esc_js( 'https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=upgrade&utm_campaign=adminarea' ); ?>');
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    });
    </script>
    <?php
}

// Keep "Go Premium" submenu text green on all admin screens.
add_action( 'admin_head', 'sitechal_go_premium_menu_color' );
function sitechal_go_premium_menu_color() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <style>
    #adminmenu a[href*="page=sitechal-go-premium"],
    #adminmenu a.sitechal-go-premium-link {
        color: #00a32a !important;
        font-weight: 600;
    }
    #adminmenu a[href*="page=sitechal-go-premium"]:hover,
    #adminmenu a.sitechal-go-premium-link:hover {
        color: #46b450 !important;
    }
    </style>
    <?php
}


if (!function_exists('sitechal_enqueue_scripts')) {
    add_action('admin_enqueue_scripts', 'sitechal_enqueue_scripts');
    
    function sitechal_enqueue_scripts() {
        wp_enqueue_script('jspdf', plugin_dir_url(__FILE__) . 'assets/js/jspdf.umd.min.js', array(), '2.5.1', true);
        wp_enqueue_script('html2canvas', plugin_dir_url(__FILE__) . 'assets/js/html2canvas.min.js', array(), '1.4.1', true);
        wp_enqueue_script('pdfobject', plugin_dir_url(__FILE__) . 'assets/js/pdfobject.min.js', array(), '2.1.1', true );
    }
}
