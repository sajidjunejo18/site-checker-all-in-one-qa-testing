<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Copy premium images into free plugin assets if available (runs on admin init)
add_action('admin_init', function() {
    $premium_base = WP_CONTENT_DIR . '/plugins/site-checker-premium-agency-yearly/includes/assets/';
    $free_images_dir = plugin_dir_path(__FILE__) . '../assets/images/';

    if ( ! is_dir( $free_images_dir ) ) {
        wp_mkdir_p( $free_images_dir );
    }

    $files_to_copy = [
        'visual-regression.png',
        'automation.png'
    ];

    foreach ( $files_to_copy as $f ) {
        $src = $premium_base . $f;
        $dest = $free_images_dir . $f;
        if ( file_exists( $src ) && ! file_exists( $dest ) ) {
            // Use @ to suppress warnings for permission issues
            @copy( $src, $dest );
        }
    }
});

// Add admin menu
function sitechal_add_admin_menu()
{
    add_menu_page(
        'Site Checker',
        'Site Checker',
        'manage_options',
        'site-checker-all-in-one-qa-testing',
        'sitechal_welcome',
        'dashicons-admin-tools',
        20
    );

    
    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'WP Related Checks',
        'WP Related Checks',
        'manage_options',
        'sitechal-wordpress-checks',
        'sitechal_wordpress_checks_page'
    );

    add_submenu_page(
        'site-checker-all-in-one-qa-testing', // Parent menu slug
        'WP Security Checks', // Page title
        'WP Security Checks', // Menu title
        'manage_options', // Capability
        'sitechal-wordpress-security-checks', // Menu slug
        'sitechal_wordpress_security_checks' // Callback function
    );

    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'General Checks',
        'General Checks',
        'manage_options',
        'sitechal-general-checks',
        'sitechal_general_checks_page'
    );
    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'Automation',
        'Automation',
        'manage_options',
        'sitechal-automation',
        'sitechal_automation_page'
    );

    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'Documentation',
        'Documentation',
        'manage_options',
        'sitechal-frequently-used-features',
        'sitechal_frequently_used_features'
    );

    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'About Us',
        'About Us',
        'manage_options',
        'sitechal-about-us',
        'sitechal_about_us'
    );

    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'Go Premium',
        'Go Premium',
        'manage_options',
        'sitechal-go-premium',
        'sitechal_go_premium_page'  // must exist
    );

    // UI Checks (mirror premium menu structure so sub-items appear on hover)
    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'UI Checks',
        'UI Checks',
        'manage_options',
        'wpsc_render_page',
        'wpsc_render_page'
    );

    // Sub-items (will appear under UI Checks on hover)
    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'Visual Regression Test',
        'Visual Regression Test',
        'manage_options',
        'wpsc-visual-regression',
        'sitechal_visual_regression_page'
    );

    add_submenu_page(
        'site-checker-all-in-one-qa-testing',
        'Responsive Test',
        'Responsive Test',
        'manage_options',
        'wpsc-responsive-test',
        'sitechal_responsive_test_page'
    );
}

function sitechal_go_premium_page() {
   if ( ! isset( $_GET['page'] ) ) {
        return;
    }

    if ( sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'sitechal-go-premium' ) {
        return;
    }

    wp_safe_redirect( 'https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=upgrade&utm_campaign=adminarea' );
    exit;
} // No callback, will redirect to pricing page

// Updated WordPress Related Checks Page
function sitechal_wordpress_checks_page()
{
    // Define allowed tabs
    $allowed_tabs = [
        'check-plugins',
        'site-health',
        'search-engine'
    ];

    // Default tab
    $tab = 'check-plugins';

    // Verify nonce and sanitize tab if valid
    if (
        isset($_GET['tab'], $_GET['_wpnonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'qa_wp_checks_tab_nonce')
    ) {
        $requested_tab = sanitize_key($_GET['tab']);
        if (in_array($requested_tab, $allowed_tabs, true)) {
            $tab = $requested_tab;
        }
    }

    // Base URL for building tab links
    $base_url = admin_url('admin.php?page=sitechal-wordpress-checks');

?>
    <div class="wrap">
        <h1 class="mainTitle"><?php esc_html_e('WP Related Checks', 'site-checker-all-in-one-qa-testing'); ?></h1>

        <div class="mainQaaContainer">
            <div class="sideBar">
                <?php
                $tab_labels = [
                    'check-plugins'   => __('WP Updates', 'site-checker-all-in-one-qa-testing'),
                    'site-health'     => __('Site Health', 'site-checker-all-in-one-qa-testing'),
                    'search-engine'   => __('WP Reading', 'site-checker-all-in-one-qa-testing'),
                ];
                $tab_classes = [
                    'check-plugins'   => 'checkPlugins',
                    'site-health'     => 'siteHealth',
                    'search-engine'   => 'searchEV',
                ];

                foreach ($allowed_tabs as $t) {
                    $nonce = wp_create_nonce('qa_wp_checks_tab_nonce');
                    $tab_url = add_query_arg([
                        'tab'      => $t,
                        '_wpnonce' => $nonce
                    ], $base_url);
                ?>
                    <a href="<?php echo esc_url($tab_url); ?>"
                        class="sideItems <?php echo esc_attr($tab_classes[$t]); ?> <?php echo ($tab === $t) ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_labels[$t]); ?>
                    </a>
                <?php } ?>
            </div>

            <div class="tableContentQaa">
                <?php
                $heavy_tasks = [
                    'check-plugins' => 'Checking Plugins ...',
                    'site-health' => 'Analyzing site health... Please wait',
                    'search-engine' => 'Search Engine Visibility ...'
                ];

                // Show loader for heavy tasks
                if (array_key_exists($tab, $heavy_tasks)) {
                    echo '<div class="qa-loader-overlay" id="qaLoaderOverlay">';
                    echo '<div class="qa-content-loader">';
                    echo '<div class="qa-loader-spinner"></div>';
                    echo '<div class="qa-loader-text">' . esc_html($heavy_tasks[$tab]) . '</div>';
                    echo '</div>';
                    echo '</div>';

                    if (ob_get_level()) {
                        ob_flush();
                        flush();
                    }
                }

                ob_start();

                switch ($tab) {
                    case 'check-plugins':
                        sitechal_check_wp_core_updates();
                        break;
                    case 'site-health':
                        sitechal_check_site_health_functionality();
                        break;
                    case 'search-engine':
                        sitechal_check_search_engine_visibility();
                        break;
                    default:
                        echo '<p>' . esc_html__('Invalid tab.', 'site-checker-all-in-one-qa-testing') . '</p>';
                        break;
                }

                $content = ob_get_clean();
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered output is generated by trusted internal plugin callbacks.
                echo '<div id="actualContent" style="display:none;">' . $content . '</div>';
                ?>
            </div>
        </div>
    </div>
<?php
}

// UI Checks Page (two tabs: Visual Regression, Responsive Test)
// Redirect callbacks for visual/regressive submenu links
function sitechal_visual_regression_page() {
    $_GET['tab'] = 'home';
    wpsc_render_page();
}

function sitechal_responsive_test_page() {
    $_GET['tab'] = 'responsive-test';
    wpsc_render_page();
}

// UI Checks renderer (mirrors premium layout: no sidebar, two tabs, premium overlay)
function wpsc_render_page() {
    // For free version assume not premium
    $is_premium = false;

    // Allowed tabs
    $allowed_tabs = ['home', 'responsive-test'];
    $tab = 'home';

    if (isset($_GET['tab']) && in_array(sanitize_key($_GET['tab']), $allowed_tabs, true)) {
        $tab = sanitize_key($_GET['tab']);
    } else {
        // Support direct submenu slugs
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if ($page === 'wpsc-visual-regression') {
            $tab = 'home';
        } elseif ($page === 'wpsc-responsive-test') {
            $tab = 'responsive-test';
        }
    }

    // Prefer local copy (copied from premium) if available, otherwise fall back to premium plugin path
    $local_bg = plugin_dir_path(__FILE__) . '../assets/images/visual-regression.png';
    if ( file_exists( $local_bg ) ) {
        $bg_img = esc_url( plugin_dir_url( __FILE__ ) . '../assets/images/visual-regression.png' );
    } else {
        $bg_img = esc_url( content_url('plugins/site-checker-premium-agency-yearly/includes/assets/visual-regression.png') );
    }

    ?>
    <div class="mainQaaContainer visualRegressionTestPage responsiveWebTest">

        <?php if ( ! $is_premium ) : ?>
            <div style="flex:1; padding: 20px; position:relative;">
                <div class="premiumDev">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sitechal-wordpress-license')); ?>" class="premiumIconContain">
                        <div class="Icon"></div>
                        <p><?php esc_html_e('Activate License', 'site-checker-all-in-one-qa-testing'); ?></p>
                    </a>
                </div>
                <img src="<?php echo $bg_img; ?>" alt="Visual Regression" style="max-width:100%;width:100%; height:auto; filter: blur(2px);" />
            </div>
        <?php else: ?>
            <div class="wrap visualDiffPage pt-40 pl-50 shadow" id="wpsc-wrap">
                <h1 class="text-40">Visual Regression Test</h1>
                <p class="fade-text">Upload your design and compare it with your live pages.</p>
            </div>
        <?php endif; ?>

    </div>
    <?php
}

// Create a dedicated flyout for the UI Checks submenu items and keep the rest of the Site Checker menu intact
add_action('admin_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            var adminMenu = document.getElementById('adminmenu');
            if (!adminMenu) return;

            var uiChecksLink = adminMenu.querySelector('a[href*="page=wpsc_render_page"]');
            var visLink = adminMenu.querySelector('a[href*="page=wpsc-visual-regression"]');
            var respLink = adminMenu.querySelector('a[href*="page=wpsc-responsive-test"]');
            if (!uiChecksLink || !visLink || !respLink) return;

            var uiChecksLi = uiChecksLink.closest('li');
            var visLi = visLink.closest('li');
            var respLi = respLink.closest('li');
            if (!uiChecksLi || !visLi || !respLi) return;

            // Hide the two submenu items from the default Site Checker list
            visLi.style.display = 'none';
            respLi.style.display = 'none';

            // Create flyout container for UI Checks
            var flyout = document.createElement('div');
            flyout.className = 'wpsc-ui-checks-flyout';
            flyout.style.display = 'none';
            flyout.style.position = 'absolute';
            flyout.style.top = '0';
            flyout.style.left = '100%';
            flyout.style.background = '#23282d';
            flyout.style.padding = '8px 0';
            flyout.style.border = '1px solid rgba(0,0,0,0.15)';
            flyout.style.boxShadow = '0 6px 12px rgba(0,0,0,0.2)';
            flyout.style.zIndex = 9999;
            flyout.style.minWidth = '180px';
            flyout.style.whiteSpace = 'nowrap';

            // Clone links for the flyout, preserving href and text
            function addFlyoutItem(link) {
                var clone = link.cloneNode(true);
                clone.removeAttribute('href');
                clone.style.display = 'block';
                clone.style.padding = '8px 16px';
                clone.style.color = '#fff';
                clone.style.width = '100%';
                clone.style.boxSizing = 'border-box';
                clone.style.textDecoration = 'none';
                clone.style.cursor = 'pointer';
                clone.addEventListener('click', function() {
                    window.location.href = link.href;
                });

                var item = document.createElement('div');
                item.className = 'wpsc-ui-checks-flyout-item';
                item.style.padding = '0';
                item.appendChild(clone);
                flyout.appendChild(item);
            }

            addFlyoutItem(visLink);
            addFlyoutItem(respLink);

            uiChecksLi.style.position = 'relative';
            uiChecksLi.appendChild(flyout);

            uiChecksLi.addEventListener('mouseenter', function() {
                flyout.style.display = 'block';
            });
            uiChecksLi.addEventListener('mouseleave', function() {
                flyout.style.display = 'none';
            });

            // Keep flyout visible while hovering it directly
            flyout.addEventListener('mouseenter', function() {
                flyout.style.display = 'block';
            });
            flyout.addEventListener('mouseleave', function() {
                flyout.style.display = 'none';
            });

        } catch (e) {
            console && console.log && console.log(e);
        }
    });
    </script>
    <?php
}, 100);

// General Checks Page
function sitechal_general_checks_page()
{
    // Define allowed tabs
    $allowed_tabs = [
        'http-to-https',
        'page-speed-insights',
        'ssl-rating',
        'youtube-suggested',
        'broken-links',
        'word-search',
        'srdb',
        'external-links',
        'accessibility-report',
        'responsive-test',
        'headings',
        'spell-check-grammar-check'
    ];

    // Set default tab
    $tab = 'http-to-https';

    // Verify nonce and sanitize tab if valid
    if (
        isset($_GET['tab'], $_GET['_wpnonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'qa_tab_switch_nonce')
    ) {
        $requested_tab = sanitize_key($_GET['tab']);
        if (in_array($requested_tab, $allowed_tabs, true)) {
            $tab = $requested_tab;
        }
    }

    // Generate base URL
    $base_url = admin_url('admin.php?page=sitechal-general-checks');

?>
    <div class="wrap">
        <h1 class="mainTitle"><?php esc_html_e('General Checks', 'site-checker-all-in-one-qa-testing'); ?></h1>

        <div class="mainQaaContainer">
            <div class="sideBar">
                <?php foreach ($allowed_tabs as $single_tab) :
                    $tab_labels = [
                        'http-to-https'       => __('HTTP to HTTPS Redirection', 'site-checker-all-in-one-qa-testing'),
                        'page-speed-insights' => __('Page Speed', 'site-checker-all-in-one-qa-testing'),
                        'ssl-rating'          => __('SSL Rating', 'site-checker-all-in-one-qa-testing'),
                        'youtube-suggested'   => __('Youtube Suggested', 'site-checker-all-in-one-qa-testing'),
                        'broken-links'        => __('Broken Links', 'site-checker-all-in-one-qa-testing'),
                        'word-search'          => __('Word Search', 'site-checker-all-in-one-qa-testing'),
                        'srdb'                => __('404 Page', 'site-checker-all-in-one-qa-testing'),
                        'external-links'      => __('External & Internal Links', 'site-checker-all-in-one-qa-testing'),
                        'accessibility-report' => __('Accessibility Report', 'site-checker-all-in-one-qa-testing'),
                        'responsive-test'     => __('Responsive Test', 'site-checker-all-in-one-qa-testing'),
                        'headings'            => __('Headings', 'site-checker-all-in-one-qa-testing'),
                        'spell-check-grammar-check' => __('Spell & Grammar Check', 'site-checker-all-in-one-qa-testing')
                    ];
                    $tab_classes = [
                        'http-to-https'       => 'redirect301',
                        'page-speed-insights' => 'pageSpeed',
                        'ssl-rating'          => 'sslRating',
                        'youtube-suggested'   => 'youTube',
                        'broken-links'        => 'brokenLink preMain',
                        'word-search'          => 'dummyText preMain',
                        'srdb'                => 'page404 preMain',
                        'external-links'      => 'externalLinks preMain',
                        'accessibility-report' => 'accessibilityReport',
                        'responsive-test'     => 'responsiveTestChecklist preMain',
                        'headings'            => 'headingIconSidebar preMain',
                        'spell-check-grammar-check' => 'spellGrammarCheck preMain'
                    ];

                    $nonce = wp_create_nonce('qa_tab_switch_nonce');
                    $tab_url = add_query_arg([
                        'tab' => $single_tab,
                        '_wpnonce' => $nonce
                    ], $base_url);

                ?>
                    <a href="<?php echo esc_url($tab_url); ?>"
                        class="sideItems <?php echo esc_attr($tab_classes[$single_tab]); ?> <?php echo ($tab === $single_tab) ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_labels[$single_tab]); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="tableContentQaa">
                <?php
                // Define heavy tasks with custom messages (based on allowed tabs)
                $heavy_tasks = [
                    'http-to-https' => 'Checking HTTP to HTTPS redirections...',
                    'page-speed-insights' => 'Running page speed analysis... This may take a moment',
                    'ssl-rating' => 'Checking SSL certificate... This may take 30-60 seconds',
                    'youtube-suggested' => 'Checking YouTube suggested videos...',
                    'broken-links' => 'Scanning for broken links... Please wait',
                    'word-search' => 'Searching for given words inside website content...',
                    'srdb' => 'Checking for 404 page issues...',
                    'external-links' => 'Searching through external links on the site...',
                    'accessibility-report' => 'Generating accessibility report...',
                    'responsive-test' => 'Running responsive test on multiple screen sizes...',
                    'headings' => 'Analyzing page headings...',
                    'spell-check-grammar-check' => 'Checking spelling and grammar...'
                ];

                $active_tab = isset($_GET['tab']) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'http-to-https';

                // Show loader for heavy tasks BEFORE processing
                if (array_key_exists($active_tab, $heavy_tasks)) {
                    echo '<div class="qa-loader-overlay" id="qaLoaderOverlay">';
                    echo '<div class="qa-content-loader">';
                    echo '<div class="qa-loader-spinner"></div>';
                    echo '<div class="qa-loader-text">' . esc_html($heavy_tasks[$active_tab]) . '</div>';
                    echo '</div>';
                    echo '</div>';

                    // Output buffer and flush to browser
                    if (ob_get_level()) {
                        ob_flush();
                        flush();
                    }
                }

                // Start output buffering for content
                ob_start();

                // Load content based on active tab
                if ($active_tab === 'http-to-https') {
                    sitechal_check_http_to_https();
                } elseif ($active_tab === 'page-speed-insights') {
                    sitechal_check_page_speed();
                } elseif ($active_tab === 'broken-links') {
                    sitechal_broken_links();
                } elseif ($active_tab === 'word-search') {
                    sitechal_custom_site_search_results();
                } elseif ($active_tab === 'ssl-rating') {
                    sitechal_check_ssllabs();
                } elseif ($active_tab === 'youtube-suggested') {
                    sitechal_youtube_suggested();
                } elseif ($active_tab === 'srdb') {
                    $srdb_content = sitechal_checkSrdbAndTakeScreenshot();
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Function returns trusted internal plugin markup.
                    echo $srdb_content;
                } elseif ($active_tab === 'external-links') {
                    sitechal_checkLinksOpenInNewTab();
                } elseif ($active_tab === 'accessibility-report') {
                    sitechal_check_accessibility_report();
                } elseif ($active_tab === 'responsive-test') {
                    sitechal_responsive_tester_page();
                } elseif ($active_tab === 'headings') {
                    sitechal_create_dropdown_for_headings();
                } elseif ($active_tab === 'spell-check-grammar-check') {
                    sitechal_create_dropdown_for_spell_and_grammar_check();
                } else {
                    echo '<p>' . esc_html__('Invalid tab specified.', 'site-checker-all-in-one-qa-testing') . '</p>';
                }

                $content = ob_get_clean();

                // Output the content wrapped in a div that will hide the loader.
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered output is generated by trusted internal plugin callbacks.
                echo '<div id="actualContent" style="display:none;">' . $content . '</div>';
                ?>
            </div>
        </div>
    </div>
<?php
}

// WordPress Security Checks Menu
function sitechal_wordpress_security_checks()
{
    // Define allowed tabs
    $allowed_tabs = [
        'check-admin-id',
        'check-aiowps',
        'check-password-strength'
    ];

    // Default tab
    $current_tab = 'check-admin-id';

    // Verify nonce and sanitize tab if present and valid
    if (
        isset($_GET['tab'], $_GET['_wpnonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'qa_wp_sec_tab_nonce')
    ) {
        $requested_tab = sanitize_key($_GET['tab']);
        if (in_array($requested_tab, $allowed_tabs, true)) {
            $current_tab = $requested_tab;
        }
    }

    // Base URL
    $base_url = admin_url('admin.php?page=sitechal-wordpress-security-checks');

?>
    <div class="wrap">
        <h1 class="mainTitle"><?php esc_html_e('WP Security Checks', 'site-checker-all-in-one-qa-testing'); ?></h1>

        <div class="mainQaaContainer">
            <!-- Tabs Navigation -->
            <div class="sideBar">
                <?php
                $tab_labels = [
                    'check-admin-id'         => __('Check User\'s ID & Admin URL', 'site-checker-all-in-one-qa-testing'),
                    'check-aiowps'           => __('Security Plugins Check', 'site-checker-all-in-one-qa-testing'),
                    'check-password-strength' => __('Check Password Strength', 'site-checker-all-in-one-qa-testing'),
                ];
                $tab_classes = [
                    'check-admin-id'          => 'checkAdminUser',
                    'check-aiowps'            => 'pluginNeed',
                    'check-password-strength' => 'passStrenght',
                ];

                foreach ($allowed_tabs as $tab) {
                    $nonce = wp_create_nonce('qa_wp_sec_tab_nonce');
                    $tab_url = add_query_arg([
                        'tab' => $tab,
                        '_wpnonce' => $nonce
                    ], $base_url);
                ?>
                    <a href="<?php echo esc_url($tab_url); ?>"
                        class="sideItems <?php echo esc_attr($tab_classes[$tab]); ?> <?php echo ($current_tab === $tab) ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_labels[$tab]); ?>
                    </a>
                <?php } ?>
            </div>
            <!-- Tabs Content -->
            <div class="tableContentQaa">
                <?php
                $heavy_tasks = [
                    'check-admin-id' => 'Checking Admin URL & User ID ...',
                    'check-aiowps' => 'Security Plugins Check ...',
                    'check-password-strength' => 'Checking Password Strength ...'
                ];
                // Show loader for heavy tasks
                if (array_key_exists($tab, $heavy_tasks)) {
                    echo '<div class="qa-loader-overlay" id="qaLoaderOverlay">';
                    echo '<div class="qa-content-loader">';
                    echo '<div class="qa-loader-spinner"></div>';
                    echo '<div class="qa-loader-text">' . esc_html($heavy_tasks[$tab]) . '</div>';
                    echo '</div>';
                    echo '</div>';

                    if (ob_get_level()) {
                        ob_flush();
                        flush();
                    }
                }

                ob_start();


                $current_tab = isset($_GET['tab']) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'check-admin-id';

                if ($current_tab === 'check-admin-id') {
                    sitechal_check_admin_user_id();
                } elseif ($current_tab === 'check-aiowps') {
                    sitechal_check_aiowps_plugin();
                } elseif ($current_tab === 'check-password-strength') {
                    sitechal_check_password_strength();
                } else {
                    echo '<p>' . esc_html__('Invalid tab specified.', 'site-checker-all-in-one-qa-testing') . '</p>';
                }

                $content = ob_get_clean();
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered output is generated by trusted internal plugin callbacks.
                echo '<div id="actualContent" style="display:none;">' . $content . '</div>';
                ?>
            </div>
        </div>
    </div>
    </div>
<?php
}

// Automation Page
function sitechal_automation_page()
{
    // Handle form submission
    if (
        isset($_POST['sitechal_save_automation_settings'], $_POST['_wpnonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'sitechal_automation_nonce')
    ) {
        $enabled_checks = isset($_POST['enabled_checks']) && is_array($_POST['enabled_checks'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['enabled_checks']))
            : [];
        $frequency = isset($_POST['automation_frequency']) ? sanitize_text_field(wp_unslash($_POST['automation_frequency'])) : 'daily';
        $email_recipients = isset($_POST['email_recipients']) ? sanitize_text_field(wp_unslash($_POST['email_recipients'])) : '';
        $send_on_issues_only = isset($_POST['send_on_issues_only']) ? 1 : 0;

        // Check license status
        $is_premium = false;
        $free_checks = ['core_updates', 'security_plugins', 'ssl_rating'];

        // Filter out premium checks if not licensed
        if (!$is_premium) {
            $enabled_checks = array_intersect($enabled_checks, $free_checks);
        }

        update_option('sitechal_enabled_checks', $enabled_checks);
        update_option('sitechal_automation_frequency', $frequency);
        update_option('sitechal_email_recipients', $email_recipients);
        update_option('sitechal_send_on_issues_only', $send_on_issues_only);

        // Schedule or unschedule the cron job
        if (!empty($enabled_checks)) {
            sitechal_schedule_automation_cron($frequency);
        } else {
            sitechal_unschedule_automation_cron();
        }

        echo '<div class="notice notice-success"><p>Automation settings saved successfully!</p></div>';
    }

    // Get current settings
    $enabled_checks = get_option('sitechal_enabled_checks', []);
    $frequency = get_option('sitechal_automation_frequency', 'daily');
    $email_recipients = get_option('sitechal_email_recipients', get_option('admin_email'));
    $send_on_issues_only = get_option('sitechal_send_on_issues_only', 0);

    // Check license status
    $is_premium = false;

    // Define free checks (available without premium license)
    $free_checks = [
        'core_updates',
        'security_plugins',
        'ssl_rating'
    ];

    // Define available checks with categories
    $available_checks = [
        // WP Related Checks
        'plugin_updates' => 'Plugin Updates Check',
        'theme_updates' => 'Theme Updates Check',
        'core_updates' => 'WordPress Core Updates Check',
        'site_health' => 'Site Health Check',
        'search_engine_visibility' => 'Search Engine Visibility Check',

        // WP Security Checks
        'admin_security' => 'Check User\'s ID & Admin URL',
        'security_plugins' => 'Security Plugins Check',

        // General Checks
        'http_to_https' => 'HTTP to HTTPS Redirection Check',
        'page_speed' => 'Page Speed Check',
        'ssl_rating' => 'SSL Rating Check',
        'youtube_settings' => 'Youtube Suggested Settings',
        'broken_links' => 'Broken Links Check',
        'broken_images' => 'Broken Images Check',
        'accessibility_report' => 'Accessibility Report',
    ];

    $free_items = [];
    $premium_items = [];

    foreach ($available_checks as $check_key => $check_label) {
        if (in_array($check_key, $free_checks, true)) {
            $free_items[$check_key] = $check_label;
        } else {
            $premium_items[$check_key] = $check_label;
        }
    }

?>
    <div class="wrap  automationPage">
        <div class="box-one">
        <h1 class="mainTitle"><?php esc_html_e('Automation Settings', 'site-checker-all-in-one-qa-testing'); ?></h1>

        <div class="textBlurb">
            <p><?php esc_html_e('Configure automated checks to run periodically and receive email reports. Select which checks to enable, set the frequency, and specify email recipients.', 'site-checker-all-in-one-qa-testing'); ?></p>

        </div>
 </div>
        <!-- Premium Feature Popup Modal -->
        <div id="premium-popup-overlay" class="premium-popup-overlay">
            <div class="premium-popup-container">
                <button class="premium-popup-close" onclick="closePremiumPopup()" aria-label="Close">&times;</button>

                <div class="premium-popup-content">
                    <div class="premium-icon">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'premium-icon.png'); ?>" alt="Site Checker" />
                    </div>

                    <a href="<?php echo esc_url('https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=automation&utm_campaign=adminarea'); ?>"
                        class="premium-upgrade-button"
                        target="_blank"
                        rel="noopener noreferrer">
                        <?php esc_html_e('Upgrade to Premium', 'site-checker-all-in-one-qa-testing'); ?>
                    </a>
                </div>
            </div>
        </div>

        <form method="post" action="" id="automation-settings-form" >
            <?php wp_nonce_field('sitechal_automation_nonce'); ?>

            <div class="automation-settings">
                <div class="sitechal-settings-section">
                     <div class="light-bg">

                       <div class="content">
                   
                             <h3><?php esc_html_e('Enable Automation', 'site-checker-all-in-one-qa-testing'); ?></h3>
                            <p class="description"><?php esc_html_e('Select which checks you want to automate (at least one required):', 'site-checker-all-in-one-qa-testing'); ?></p>
                              
                       </div>
                            <div class="label" >
                            <label>
                                <input type="checkbox" id="select-all-checks" style="margin: 0;">
                                <?php esc_html_e('Select All Checks', 'site-checker-all-in-one-qa-testing'); ?>
                            </label>
                        </div>
                       
                        </div>
                        <!-- Select All Checkbox -->
                     
                    <div class="setting-group form-wrapper">
                        

                        <!-- Validation Error Message (hidden by default) -->
                        <div id="validation-error" style="display: none; margin: 10px 0; padding: 10px; background: #fef0f0; border-left: 4px solid #dc3232; color: #dc3232;">
                            <strong><?php esc_html_e('Error:', 'site-checker-all-in-one-qa-testing'); ?></strong> <?php esc_html_e('Please select at least one check to enable automation.', 'site-checker-all-in-one-qa-testing'); ?>
                        </div>

                        <div class="checks-grid">
                            <!-- FREE FEATURES -->
                            <?php foreach ($free_items as $check_key => $check_label) :
                                $is_checked = in_array($check_key, $enabled_checks, true);
                            ?>
                                <label class="check-item">
                                    <input type="checkbox"
                                        name="enabled_checks[]"
                                        value="<?php echo esc_attr($check_key); ?>"
                                        class="automation-check-item automation-check"
                                        <?php checked($is_checked); ?> />
                                    <?php echo esc_html($check_label); ?>
                                </label>
                            <?php endforeach; ?>

                            <!-- PREMIUM FEATURES -->
                            <?php foreach ($premium_items as $check_key => $check_label) : ?>
                                <label class="check-item premium-check" data-premium="true">
                                    <input type="checkbox"
                                        class="automation-check"
                                        data-premium-required="true"
                                        disabled
                                        value="<?php echo esc_attr($check_key); ?>" />
                                    <?php echo esc_html($check_label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="setting-two">
                    <div class="setting-group check-frequency">
                        <h3 class='form-subtitle'><?php esc_html_e('Check Frequency', 'site-checker-all-in-one-qa-testing'); ?></h3>
                        <select name="automation_frequency">
                            <option value="weekly" <?php selected($frequency, 'weekly'); ?>><?php esc_html_e('Weekly', 'site-checker-all-in-one-qa-testing'); ?></option>
                            <option value="biweekly" <?php selected($frequency, 'biweekly'); ?>><?php esc_html_e('Bi-Weekly (Every 2 Weeks)', 'site-checker-all-in-one-qa-testing'); ?></option>
                            <option value="monthly" <?php selected($frequency, 'monthly'); ?>><?php esc_html_e('Monthly', 'site-checker-all-in-one-qa-testing'); ?></option>
                            <option value="quarterly" <?php selected($frequency, 'quarterly'); ?>><?php esc_html_e('Quarterly (Every 3 Months)', 'site-checker-all-in-one-qa-testing'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Select frequency for automated checks.', 'site-checker-all-in-one-qa-testing'); ?></p>
                    </div>

                    <div class="setting-group email-recipient">
                        <div class="r1">

                       
                        <h3 class='form-subtitle'><?php esc_html_e('Email Recipients', 'site-checker-all-in-one-qa-testing'); ?></h3>
                        <input type="email" name="email_recipients" value="<?php echo esc_attr($email_recipients); ?>" class="regular-text" multiple />
                        <p class="description"><?php esc_html_e('Enter email addresses separated by commas', 'site-checker-all-in-one-qa-testing'); ?></p>
                      </div>
                        <p class="submit">
                        <input type="submit" name="sitechal_save_automation_settings" class="button button-primary" value="<?php echo esc_attr__('Save Changes', 'site-checker-all-in-one-qa-testing'); ?>" />
                </p>
                    </div>
                 
                    </div>
                    
                       
                </div>

             
            </div>
           
        </form>

 
        <div class="automation-info">
             <div class="seperator"></div>
             <div class="content">
            <div class="box">
            <h3><?php esc_html_e('Next Scheduled Check', 'site-checker-all-in-one-qa-testing'); ?></h3>
            <?php
            $next_scheduled = wp_next_scheduled('sitechal_automation_cron');
            if ($next_scheduled) {
                $timezone_string = wp_timezone_string();
                $local_time = wp_date('l, F j, Y \a\t g:i A', $next_scheduled);
                $local_time_short = wp_date('Y-m-d H:i:s', $next_scheduled);

                $current_time = time();
                $time_diff = $next_scheduled - $current_time;

                if ($time_diff > 0) {
                    $hours = floor($time_diff / 3600);
                    $minutes = floor(($time_diff % 3600) / 60);

                    if ($hours > 24) {
                        $days = floor($hours / 24);
                        $remaining_hours = $hours % 24;
                        $time_until = sprintf('%d days, %d hours', $days, $remaining_hours);
                    } elseif ($hours > 0) {
                        $time_until = sprintf('%d hours, %d minutes', $hours, $minutes);
                    } else {
                        $time_until = sprintf('%d minutes', $minutes);
                    }

                    echo '<p><strong>' . esc_html__('Next check scheduled for:', 'site-checker-all-in-one-qa-testing') . '</strong><br>';
                    echo esc_html($local_time) . '<br>';
                    echo '(' . esc_html($local_time_short) . ' ' . esc_html($timezone_string) . ')<br>';
                    echo '<em>' . esc_html__('In ', 'site-checker-all-in-one-qa-testing') . esc_html($time_until) . '</em></p>';
                } else {
                    echo '<p><strong style="color:#d63638;">' . esc_html__('Scheduled time has passed! The cron should run soon.', 'site-checker-all-in-one-qa-testing') . '</strong></p>';
                }
            } else {
                echo '<p>' . esc_html__('No automated checks scheduled.', 'site-checker-all-in-one-qa-testing') . '</p>';
            }
            ?>
            </div>
            </div>
        </div>
    </div>

<?php
}

// About Us Page
function sitechal_about_us()
{ ?>

    <div class="wrap sitechal-about-us-page">
        <div class="about-us-container">
            <div class="about-us-content">
                <h2 class="about-title"><?php esc_html_e('About Us', 'site-checker-all-in-one-qa-testing'); ?></h2>

                <p><?php esc_html_e('Welcome to WP Site Checker, a WordPress Site Auditing plugin which allows your QA Teams to streamline and automate multiple processes and make sure your site is at the top of its game in terms of functionality, SEO-Compatibility, and Loading Speeds.', 'site-checker-all-in-one-qa-testing'); ?></p>

                <p><?php esc_html_e('With WP Site Checker your QA-Teams have, in their hands, a toolbox that allows them to go through site, security, and general WordPress Checks. Providing you updates about your site’s WordPress Core Status, checks for required security plugins and theme plugins, and scanning sites for page speed, SEO issues, grammar and spelling checks, and much, much more.', 'site-checker-all-in-one-qa-testing'); ?></p>

                <h3 class="resource-title"><?php esc_html_e('Resourceful Links:', 'site-checker-all-in-one-qa-testing'); ?></h3>

                <div class="resource-links">
                    <a href="https://wpsitechecker.com/documentation/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('Documentation', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/wp-related-checks/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('WP Related Checks', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/wp-security-checks/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('WP Security Checks', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/wp-general-checks/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('WP General Checks', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('Pricing', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/contact-us/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('Support', 'site-checker-all-in-one-qa-testing'); ?></a>
                </div>

                <p class="genetech-solutions">
                    <?php
                    $genetech_link = sprintf(
                        '<strong><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></strong>',
                        esc_url('https://www.genetechsolutions.com/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea'),
                        esc_html__('Genetech Solutions.', 'site-checker-all-in-one-qa-testing')
                    );
                    /* translators: %s: linked company name (Genetech Solutions). */
                    $product_text = esc_html__('WP Site Checker is a product of %s', 'site-checker-all-in-one-qa-testing');
                    printf(
                        wp_kses_post($product_text),
                        wp_kses_post($genetech_link)
                    );
                    ?>
                </p>

                <p><?php esc_html_e('Other products by the Team include:', 'site-checker-all-in-one-qa-testing'); ?></p>

                <ul class="product-list">
                    <li><a href="https://pieforms.com/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('Pie Forms', 'site-checker-all-in-one-qa-testing'); ?></a>, <?php esc_html_e('the Easiest Drag-and-Drop WordPress Form Builder Plugin,', 'site-checker-all-in-one-qa-testing'); ?></li>
                    <li><a href="https://pieregister.com/?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('Pie Register', 'site-checker-all-in-one-qa-testing'); ?></a>, <?php esc_html_e('create a simple registration form on your WP Site with its Drag-and-Drop interface,', 'site-checker-all-in-one-qa-testing'); ?></li>
                    <li><a href="https://www.genetechsolutions.com/products/pb-addons?utm_source=freeplugin&utm_medium=aboutus&utm_campaign=adminarea" target="_blank"><?php esc_html_e('PB Add-ons', 'site-checker-all-in-one-qa-testing'); ?></a> <?php esc_html_e('for WP Bakery, a collection of free and premium add-ons to build you website’s page up to its true potential,', 'site-checker-all-in-one-qa-testing'); ?></li>
                </ul>
            </div>

            <div class="about-us-videos">
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/MGxLvpipmKg?si=qW_3tA1B2aiwrMG6&rel=0&enablejsapi=1" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/KUlA42HWnbs?si=fnT1LycN6xfxfadV&rel=0&enablejsapi=1" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
        </div>

    </div>
<?php }

// Frequently used features
function sitechal_frequently_used_features()
{ 
    $base_url = admin_url('admin.php?page=sitechal-wordpress-checks');
    ?>

    <div class="wrap sitechal-about-us-page sitechal-frequently-used-features">
        <div class="about-us-container">
            <div class="about-us-content">
                <h2 class="about-title"><?php esc_html_e('Ready To Audit your Website? Well then, where would you like to start?', 'site-checker-all-in-one-qa-testing'); ?></h2>
                <div class="btn-box">
                    <a href="<?php echo esc_url($base_url); ?>" class="site-chal-button  site-chal-button-primary"><?php esc_html_e('I’m Ready, Let’s Get Started!', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/documentation/?utm_source=freeplugin&utm_medium=documentation&utm_campaign=adminarea" target="_blank" class="site-chal-button site-chal-button-secondary"><?php esc_html_e('Read the Detailed User Guide', 'site-checker-all-in-one-qa-testing'); ?></a>
                </div>
                <div class="seperator"></div>

                <div class="useful-features">
                    <h3 class="resource-title"><?php esc_html_e('Documentation', 'site-checker-all-in-one-qa-testing'); ?></h3>
                    <div class="icons-wrapper">
                        <div class="icon-box">
                            <div class="icon">
                                <figure>
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/wp-core-updates.png'); ?>" alt="WordPress Core Update Status Checker" />
                                </figure>
                            </div>
                            <div class="content">
                                <h4 class="sub-title"><?php esc_html_e('WordPress Core Update Status Checker', 'site-checker-all-in-one-qa-testing'); ?></h4>
                            </div>

                        </div>
                        <div class="icon-box">
                            <div class="icon">
                                <figure>
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/wp-site-health.png'); ?>" alt="WP Site Health Reporter" />
                                </figure>
                            </div>
                            <div class="content">
                                <h4 class="sub-title"><?php esc_html_e('WP Site Health Reporter', 'site-checker-all-in-one-qa-testing'); ?></h4>
                            </div>

                        </div>

                        <div class="icon-box">
                            <div class="icon">
                                <figure>
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/page-speed.png'); ?>" alt="Page Speed Scanner" />
                                </figure>
                            </div>
                            <div class="content">
                                <h4 class="sub-title"><?php esc_html_e('Page Speed Scanner', 'site-checker-all-in-one-qa-testing'); ?></h4>
                            </div>

                        </div>
                        <div class="icon-box">
                            <div class="icon">
                                <figure>
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/seo.png'); ?>" alt="Search Engine Visibility Toggle" />
                                </figure>
                            </div>
                            <div class="content">
                                <h4 class="sub-title"><?php esc_html_e('Search Engine Visibility Toggle', 'site-checker-all-in-one-qa-testing'); ?></h4>
                            </div>

                        </div>

                        <div class="icon-box">
                            <div class="icon">
                                <figure>
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/website-security-checker.png'); ?>" alt="Website Security Plugin Checker" />
                                </figure>
                            </div>
                            <div class="content">
                                <h4 class="sub-title"><?php esc_html_e('Website Security Plugin Checker', 'site-checker-all-in-one-qa-testing'); ?></h4>
                            </div>

                        </div>

                        <div class="icon-box">
                            <div class="icon">
                                <figure>
                                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/ssl-rating-checks.png'); ?>" alt="SSL Rating Reporter" />
                                </figure>
                            </div>
                            <div class="content">
                                <h4 class="sub-title"><?php esc_html_e('SSL Rating Reporter', 'site-checker-all-in-one-qa-testing'); ?></h4>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="seperator"></div>
                <div class="useful-features">
                    <h3 class="resource-title"><?php esc_html_e('Helpful Links', 'site-checker-all-in-one-qa-testing'); ?></h3>

                </div>
                <div class="resource-links">
                    <a href="https://wpsitechecker.com/documentation/?utm_source=freeplugin&utm_medium=documentation&utm_campaign=adminarea"><?php esc_html_e('Documentation', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/wp-related-checks/?utm_source=freeplugin&utm_medium=documentation&utm_campaign=adminarea"><?php esc_html_e('WP Related Checks', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/wp-security-checks/?utm_source=freeplugin&utm_medium=documentation&utm_campaign=adminarea"><?php esc_html_e('WP Security Checks', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/wp-general-checks/?utm_source=freeplugin&utm_medium=documentation&utm_campaign=adminarea"><?php esc_html_e('WP General Checks', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=documentation&utm_campaign=adminarea"><?php esc_html_e('Pricing', 'site-checker-all-in-one-qa-testing'); ?></a>
                    <a href="https://wpsitechecker.com/frequently-asked-questions/?utm_source=freeplugin&utm_medium=documentation&utm_campaign=adminarea"><?php esc_html_e('Support', 'site-checker-all-in-one-qa-testing'); ?></a>
                </div>


            </div>

            <div class="about-us-videos">
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/KUlA42HWnbs?si=h3sotOOzjmnGwtTE&rel=0&enablejsapi=1" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>

            </div>
        </div>

    </div>

<?php }

// Frequently used features
function sitechal_welcome()
{ ?>

    <div class="wrap sitechal-wrapper sitechal-welcome">
        <div class="container sec-one">
            <div class="logo">

                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'wp-site-checker.svg'); ?>" alt="WP Site Checker" />

            </div>

            <div class="sec-one">
                <h2 class="main-heading text-center"><?php esc_html_e('Welcome to WP Site Checker', 'site-checker-all-in-one-qa-testing'); ?></h2>
                <p class="text-center"><?php esc_html_e('Hello! And Welcome, to WP Site Checker. The ultimate comprehensive web QA toolbox in the palm of your hands.', 'site-checker-all-in-one-qa-testing'); ?></p>

            </div>
        </div>

        <div class="container sec-two ">
            <iframe width="100%" height="500" src="https://www.youtube.com/embed/MGxLvpipmKg?si=mazTn7RD_TgNt3N1&rel=0&enablejsapi=1" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>

            <h2 class="main-heading heading-light"><?php echo wp_kses_post(__('How do I use WP Site Checker to audit my <strong>website performance</strong> and improve it?', 'site-checker-all-in-one-qa-testing')); ?></h2>
            <!-- <a href="https://www.youtube.com/watch?v=MGxLvpipmKg" target="_blank" class="site-chal-button  site-chal-button-primary"><?php esc_html_e('Click here to Watch Video', 'site-checker-all-in-one-qa-testing'); ?></a> -->


        </div>


        <div class="container sec-three ">

            <p class="text-center"><?php esc_html_e('To learn how to use this toolbox to its full potential you can take a look at the guide video above, or flip through our written guides and documentation!', 'site-checker-all-in-one-qa-testing'); ?></p>

        </div>

        <div class="container sec-four">
            <h2 class="main-heading"><?php esc_html_e('WP Site Checker Features and Addons', 'site-checker-all-in-one-qa-testing'); ?></h2>
            <p class="text-center"><?php esc_html_e('WP Site Checker comes with a menagerie of features, each sorted into one of three major feature collections;', 'site-checker-all-in-one-qa-testing'); ?></p>

            <div class="features-wrapper">
                <div class="single-feature">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/wordpress-related-checks.png'); ?>" alt="WordPress Related Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('WordPress Related Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="single-feature">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/wordpress-security-checks.png'); ?>" alt="WordPress Security Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('WordPress Security Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="single-feature">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/wordpress-general-checks.png'); ?>" alt="WordPress General Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('WordPress General Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>



            </div>
        </div>
        <hr>

        <div class="container sec-five">
            <h2 class="main-heading"><?php esc_html_e('Some of the more popular features include;', 'site-checker-all-in-one-qa-testing'); ?></h2>
            <div class="icons-wrapper">
                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/user-id-checks.png'); ?>" alt="User ID Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('User ID Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/website-security-checker.png'); ?>" alt="Security Plugin Checks and Installation" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Security Plugin Checks and Installation', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/admin-url-checks.png'); ?>" alt="Admin URL Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Admin URL Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/page-spped.png'); ?>" alt="Page Speed Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Page Speed Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/redirection-checks.png'); ?>" alt="301 Redirection Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('301 Redirection Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/broken-link-search.png'); ?>" alt="Broken Link Search" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Broken Link Search', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/toggling-upload-directory.png'); ?>" alt="Toggling Upload Directory Privacy" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Toggling Upload Directory Privacy', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>


                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/password-strength-checks.png'); ?>" alt="Password Strength Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Password Strength Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/ssl-rating-checks.png'); ?>" alt="SSL Rating Checks" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('SSL Rating Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>
            </div>

        </div>

        <div class="container sec-six">
            <h2 class="main-heading"><?php esc_html_e('Upgrade Today', 'site-checker-all-in-one-qa-testing'); ?></h2>
            <p class="text-center"><?php esc_html_e('WP Site Checker is completely free for you to use, but through the paid version you get access to a banquet of extra functionality and some new features to work with! What kinds of features? Well;', 'site-checker-all-in-one-qa-testing'); ?></p>
            <div class="icons-wrapper">
                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/multiple-license.png'); ?>" alt="Multiple Licenses" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Multiple Licenses', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/quick-update.png'); ?>" alt="Quick Updates" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Quick Updates', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/multiple-security-plugins-checks.png'); ?>" alt="Multiple Security Plugins Check" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Multiple Security Plugins Check', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/multiple-page-speed-scans.png'); ?>" alt="Multiple Page - Page Speed Scans" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Multiple Page - Page Speed Scans', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/ssl-rating-checks.png'); ?>" alt="Detailed SSL Ratings" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Detailed SSL Ratings', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/broken-link-search.png'); ?>" alt="Broken Link Checker" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Broken Link Checker', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/word-search.png'); ?>" alt="Specific Word Search" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Specific Word Search', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>


                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/page-404.png'); ?>" alt="404 Page Check" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('404 Page Check', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/link-scanner.png'); ?>" alt="External & Internal Link Scan" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('External & Internal Link Scan', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/accessbility-reporter.png'); ?>" alt="Accessibility Reporter" />
                    </figure>
                    <h3 class="subtitle"><?php esc_html_e('Accessibility Reporter', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>

                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/responsive-tester.png'); ?>" alt="Website Responsiveness Tester" />
                    </figure>
                    <h3 class="subtitle mw-35"><?php esc_html_e('Website Responsiveness Tester', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>


                <div class="icon rounded-bg-100">
                    <figure>
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '/inner-pages/headings-spelling-checks.png'); ?>" alt="Headings, Spellings, and Grammar Checks" />
                    </figure>
                    <h3 class="subtitle mw-35"><?php esc_html_e('Headings, Spellings, and Grammar Checks', 'site-checker-all-in-one-qa-testing'); ?></h3>
                </div>
            </div>

        </div>


        <!-- Our Satisfied Customers Section -->
        <div class="container sec-seven">
            <h2 class="main-heading"><?php esc_html_e('Our Satisfied Customers', 'site-checker-all-in-one-qa-testing'); ?></h2>

            <div class="testimonial-list">
                <?php
                $testimonials = [
                    [
                        'name' => 'ayeshakhan12',
                        'avatar' => 'https://secure.gravatar.com/avatar/ca95fe60a48dd1be5fc1e16cfb6f09abbbdbb4d9925cf29ae3e9936d39e18a79?s=100&d=retro&r=g',
                        'text' => 'Site Checker: All-in-One QA Testing has been a fantastic addition to my WordPress toolkit. It quickly detects broken links, analyzes performance, and performs essential security checks. everything I need in one place. It would be even better if it also included accessibility testing and checks for both internal and external links. Plus, a scheduling feature for automatic weekly scans and fixes would make it absolutely perfect!',
                        'rating' => 5,
                        'date' => 'October 13, 2025'
                    ],
                    [
                        'name' => 'akhtersahab',
                        'avatar' => 'https://secure.gravatar.com/avatar/de91fbe6013d97163becb9cb6d32964a07210f41f4518aedc352b889a88a5063?s=100&d=retro&r=g',
                        'text' => 'I really like how this plugin handles all the essential QA tests in one place from performance to basic security. It saves me a lot of manual work. If they add a one-click fix option for common issues, it’ll be even more powerful.',
                        'rating' => 5,
                        'date' => 'October 13, 2025'
                    ],
                    [
                        'name' => 'saad404',
                        'avatar' => 'https://secure.gravatar.com/avatar/0905b933b92a4bb58d4241e2f5d24411b05efa4cb3aae0dbbc1229ada6a757ef?s=100&d=retro&r=g',
                        'text' => 'I’ve been using the Site Checker: All-in-One QA Testing plugin for a while now, and honestly, it’s been super handy for keeping my WordPress sites in check. It does a great job combining all the essential QA stuff — from speed and performance tests to broken link detection and basic security checks — all in one clean dashboard. The best part is that it actually saves me from switching between multiple tools, which used to be such a pain. The reports are clear, easy to understand, and give enough detail to act on quickly. It’s not overloaded with features, just practical and to the point. Perfect for anyone who wants a reliable, time-saving way to monitor their site’s overall health.',
                        'rating' => 5,
                        'date' => 'October 8, 2025'
                    ]
                ];

                foreach ($testimonials as $testimonial) : ?>
                    <div class="testimonial-item ">
                        <div class="testimonial-left">
                            <img src="<?php echo esc_url($testimonial['avatar']); ?>" alt="<?php echo esc_attr($testimonial['name']); ?>" class="customer-avatar">
                        </div>
                        <div class="testimonial-right">
                            <p class="testimonial-text" data-fulltext="<?php echo esc_attr($testimonial['text']); ?>">
                                <?php echo esc_html($testimonial['text']); ?>
                            </p>
                            <div class="rating-stars">
                                <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                    <svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.03503 0L11.8103 5.68023L18.0701 6.56434L13.5254 10.959L14.619 17.1857L9.03503 14.2215L3.45107 17.1857L4.54462 10.959L-2.86102e-06 6.56434L6.25981 5.68023L9.03503 0Z" fill="#E84814" />
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <div class="testimonial-footer">
                                <span class="customer-name"><?php echo esc_html($testimonial['name']); ?></span>
                                <span class="testimonial-date"><?php echo esc_html($testimonial['date']); ?></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                <?php endforeach; ?>

            </div>

            <div class="testimonial-buttons">
                <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=upgrade&utm_campaign=adminarea" target="_blank" class="site-chal-button  site-chal-button-secondary"><?php esc_html_e('Upgrade Your QA Tester Toolkit', 'site-checker-all-in-one-qa-testing'); ?></a>
            </div>
        </div>
    </div>
    </div>
<?php }
