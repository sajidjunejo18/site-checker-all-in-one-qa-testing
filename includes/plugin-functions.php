<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'libraries/zxcvbn-php/src/Zxcvbn.php';

use ZxcvbnPhp\Zxcvbn;

function sitechal_youtube_suggested() {
    $args = array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    $pages_query = new WP_Query($args);
    $unique_pages = [];
    $video_count = 0;
    $limit = 5;

   if ($pages_query->have_posts()) {
        echo '<div class="textBlurb">';
        echo '<h3 class="panelHeading">' . esc_html__('YouTube Suggested', 'site-checker-all-in-one-qa-testing') . '</h3>';
        echo '<div class="blurbIcon">?</div>';
        echo '<div class="blurbContent">';
        echo '<p>' . esc_html__('Enable the user to toggle the YouTube content accessible on or by the site. Whether the YouTube content must be channel-specific, or simply be relevant to the topic and themes of your site.', 'site-checker-all-in-one-qa-testing') . '</p>';
        echo '</div></div>';

        while ($pages_query->have_posts() && $video_count < $limit) {
            $pages_query->the_post();
            $page_url = get_permalink(get_the_ID());
            $page_title = get_the_title();

            if (!in_array($page_url, $unique_pages)) {
                $videos = sitechal_youtube_videos($page_url);
                if (!empty($videos)) {
                    echo "<div class='yTMain'>";
                    echo "<h3 class='title'>" . esc_html__('Page Title:', 'site-checker-all-in-one-qa-testing') . " <span>" . esc_html($page_title) . "</span></h3>";
                    echo "<h3 class='titleUrl'>" . esc_html__('Page URL:', 'site-checker-all-in-one-qa-testing') . " <a href='" . esc_url($page_url) . "' target='_blank'>" . esc_html($page_url) . "</a></h3><ul>";

                    foreach ($videos as $video) {
                        $rel = $video['has_rel_0']
                            ? '<span class="ytTick" style="color: green !important;">✔</span> ' . __('suggests channel-specific videos...', 'site-checker-all-in-one-qa-testing')
                            : '<span style="color: red !important;">❌</span> ' . __('does not suggest channel-specific videos...', 'site-checker-all-in-one-qa-testing');

                        echo '<li>' . esc_html($video['src']) . ' — ' . wp_kses_post($rel) . '</li>';
                    }

                    echo "</ul>";
                    $video_count++;
                    echo "</div>";
                }
                $unique_pages[] = $page_url;
            }
        }

        wp_reset_postdata();
    }

    if ($video_count === 0) {
        echo "<p class='qa-success'><strong>" . esc_html__('No YouTube videos found on the entire website.', 'site-checker-all-in-one-qa-testing') . "</strong></p>";
    }

    return $unique_pages;
}

function sitechal_youtube_videos($url) {
    // Fetch the HTML content using WordPress HTTP API
    $response = wp_remote_get($url, [
        'timeout' => 20,
        'sslverify' => true,
        'headers'  => [
            'User-Agent' => 'Mozilla/5.0'
        ]
    ]);

    // Handle request errors
    if (is_wp_error($response)) {
        return null;
    }

    $html = wp_remote_retrieve_body($response);

    if (empty($html)) {
        return null;
    }

    // Parse the HTML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    libxml_clear_errors();

    $iframes = $dom->getElementsByTagName('iframe');
    $youtube_videos = [];

    foreach ($iframes as $iframe) {
        $src = $iframe->getAttribute('src');
        if (strpos($src, 'youtube.com/embed/') !== false || strpos($src, 'youtube-nocookie.com/embed/') !== false) {
            $has_rel_0 = (strpos($src, 'rel=0') !== false);
            $youtube_videos[] = [
                'src'       => esc_url_raw($src),
                'has_rel_0' => $has_rel_0
            ];
        }
    }

    return $youtube_videos;
}

// Check if WP Activity Log plugin is installed
function sitechal_check_wp_activity_log_functionality() {
    $all_plugins = get_plugins();
    $plugin_slug = 'wp-security-audit-log/wp-security-audit-log.php';

    if (isset($all_plugins[$plugin_slug])) {
        $plugin = $all_plugins[$plugin_slug];
        echo '<p>The WP Activity Log plugin is installed.</p>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Detail</th><th>Value</th></tr></thead><tbody>';
        echo '<tr><td>Name</td><td>' . esc_html($plugin['Name']) . '</td></tr>';
        echo '<tr><td>Version</td><td>' . esc_html($plugin['Version']) . '</td></tr>';
        echo '<tr><td>Author</td><td>' . esc_html($plugin['Author']) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Author URI', 'site-checker-all-in-one-qa-testing') . '</td><td><a href="' . esc_url($plugin['AuthorURI']) . '" target="_blank">' . esc_html($plugin['AuthorURI']) . '</a></td></tr>';
        echo '</tbody></table>';
    } else {
        echo '<p>The WP Activity Log plugin is not installed. <a href="https://wordpress.org/plugins/wp-security-audit-log/" target="_blank">Download it here</a>.</p>';
    }
}

// Function to display the site health report page
function sitechal_check_site_health_functionality() {
    ?>
        <div class="textBlurb">
            <h3 class="panelHeading"><?php esc_html_e('Site Health Report', 'site-checker-all-in-one-qa-testing'); ?></h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The <strong>Site Health</strong> function reports on the current <strong>health</strong> of your website. This includes accessibility to the site, the recorded ping rate, and more. Allowing you to identify whether your site is visible to audiences online, or whether users were able to easily connect to it. </p>
            </div>
        </div>
    <div class="siteHealth">
        <?php
        // Fetch and display site health
        sitechal_report_site_health_issues();
        ?>
    </div>
    <?php
}

// Function to fetch and display site health data from a REST endpoint
function sitechal_execute_site_health_async_test($url) {
    // Generate a nonce for authentication
    $nonce = wp_create_nonce('wp_rest');

    // Ensure we have a full URL
    if (strpos($url, 'http') !== 0) {
        $url = get_site_url() . $url;
    }

    // If cookies are required for the request, pick only the relevant one
    $cookies = [];
    $auth_cookie_name = 'wordpress_logged_in_' . COOKIEHASH;

    if ( isset( $_COOKIE[ $auth_cookie_name ] ) ) {
        $cookies[ $auth_cookie_name ] = sanitize_text_field(
            wp_unslash( $_COOKIE[ $auth_cookie_name ] )
        );
    }

    $response = wp_remote_get($url, [
        'headers' => [
            'X-WP-Nonce'    => $nonce, // Use nonce for authentication
            'Content-Type'  => 'application/json',
        ],
        // Only include the necessary cookie (or comment this line out if not needed)
        'cookies' => $cookies,
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    // Retrieve and decode the response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decoding error for ' . $endpoint . ': ' . json_last_error_msg());
        return null;
    }

    return $data;
}

// Strip Function to remove wrapping <p> tags from HTML
function sitechal_strip_wrapping_p($html){
    return preg_replace('/<p>(.*?)<\/p>/', '$1', $html);
}

// Function to display site health results categorized by status
function sitechal_print_issues($title, $items) {
    $class_name = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '-', $title));

    echo '<div class="accTabs ' . esc_attr($class_name) . '">';

    $count = count($items);
    $item_label = ($count === 1) ? 'Item' : 'Items';

    $title_text = esc_html($count . ' ' . $item_label . ' with status ' . strtoupper($title));
    $button_html = '';

    if ($class_name === 'critical') {
        $button_html = '<button type="button" class="site-health-fix-btn">' . esc_html__('How to Fix?', 'site-checker-all-in-one-qa-testing') . '</button>';
    }

    echo '<h4 class="general-result accTitle"><strong>' . $title_text . '</strong>' . $button_html . '</h4>';
    echo '<div class="siteHAcc">';
    if (!empty($items)) {
        foreach ($items as $issue) {
            echo '<div class="accItems">';
            echo "<h4 class='itemTitle'>" . esc_html($issue['label']) . "</h4>";
            echo "<p class='itemDesc'>" . esc_html($issue['description']) . "</p>";
            echo "<p class='itemLink'><strong>" . esc_html__('Actions:', 'site-checker-all-in-one-qa-testing') . "</strong> " . wp_kses_post(sitechal_strip_wrapping_p($issue['actions'])) . "</p>";
            echo '</div>';
        }
    } else {
        echo "<p>No items found.</p><br>";
    }
    echo '</div>';
    echo '</div>';
}

// Function to extract text from HTML
function sitechal_extract_text_from_html($html_content) {
    if (empty($html_content)) {
        return 'No content available';
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress warnings for malformed HTML
    $doc->loadHTML($html_content);
    libxml_clear_errors();
    
    return wp_strip_all_tags($doc->textContent);
}

// Main function to check and report site health issues
function sitechal_report_site_health_issues() {
    if ( ! class_exists( 'WP_Site_Health' ) ) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
    }

    $sh = WP_Site_Health::get_instance();
    $tests = $sh->get_tests();

    $good_items = [];
    $recommended_items = [];
    $critical_items = [];
    $seen_ids = [];

    // Process Direct Tests
    if (isset($tests['direct'])) {
        foreach ($tests['direct'] as $id => $test_data) {
            $callback = $test_data['test'];
            $data = false;

            if ( is_array( $callback ) && is_callable( $callback ) ) {
                $data = call_user_func( $callback );
            } elseif ( is_string( $callback ) && method_exists( $sh, 'get_test_' . $callback ) ) {
                $data = call_user_func( array( $sh, 'get_test_' . $callback ) );
            }

            if ($data && is_array($data) && isset($data['status'])) {
                $seen_ids[] = $id;
                $status = $data['status'];
                $issue = [
                    'label'       => $data['label'] ?? 'No label',
                    'description' => sitechal_extract_text_from_html($data['description'] ?? 'No description available'),
                    'actions'     => isset($data['actions']) && !empty($data['actions'])
                                    ? wp_kses_post($data['actions'])
                                    : 'No actions available'        
                ];

                if ($status == 'good') {
                    $good_items[] = $issue;
                } elseif ($status == 'recommended') {
                    $recommended_items[] = $issue;
                } elseif ($status == 'critical') {
                    $critical_items[] = $issue;
                }
            }
        }
    }

    // Process Async Tests
    if (isset($tests['async'])) {
        foreach ($tests['async'] as $id => $test_data) {
            // Skip if already processed in direct tests
            if (in_array($id, $seen_ids)) {
                continue;
            }

            // Some plugins provide a string for the test, which might be a method name or a relative URL
            // Core async tests provide a full URL
            $test_url = $test_data['test'];
            
            // If it's not a URL, we can't easily run it via REST here
            if (empty($test_url) || !is_string($test_url) || (strpos($test_url, 'http') !== 0 && strpos($test_url, '/') !== 0)) {
                continue;
            }

            $data = sitechal_execute_site_health_async_test($test_url);
            
            if ($data && is_array($data) && isset($data['status'])) {
                $status = $data['status'];
                $issue = [
                    'label' => $data['label'],
                    'description' => sitechal_extract_text_from_html($data['description'] ?? 'No description available'),
                    'actions'     => isset($data['actions']) && !empty($data['actions'])
                                    ? wp_kses_post($data['actions'])
                                    : 'No actions available'        
                ];
            
                if ($status == 'good') {
                    $good_items[] = $issue;
                } elseif ($status == 'recommended') {
                    $recommended_items[] = $issue;
                } elseif ($status == 'critical') {
                    $critical_items[] = $issue;
                }
            }
        }
    }
    // die;

    // Determine the general result based on critical items
    $general_result = 'Site is in good health with no critical or recommended issues.';
    if (!empty($critical_items)) {
        $general_result = 'Site needs to be improved as it has critical errors.';
    } elseif (!empty($recommended_items)) {
        $general_result = 'Site should be improved as it has recommended items.';
    }

    // Print the general result
    echo '<h4 class="general-result"><strong>General Site Health Result:</strong></h4>';
    echo '<p>' . esc_html($general_result) . '</p>';

    // Print categorized issues
    sitechal_print_issues('CRITICAL', $critical_items);
    sitechal_print_issues('RECOMMENDED', $recommended_items);
    sitechal_print_issues('GOOD', $good_items);
}

// Function to check if WP Mail SMTP plugin is installed and activated
function sitechal_check_wp_mail_smtp_installed_and_activated() {
    // WP Mail SMTP plugin slug
    $wp_mail_smtp_slug = 'wp-mail-smtp/wp_mail_smtp.php';
    // Get all installed plugins
    $all_plugins = get_plugins();

    // Check if the WP Mail SMTP plugin is installed and activated
    if (isset($all_plugins[$wp_mail_smtp_slug])) {
        if (is_plugin_active($wp_mail_smtp_slug)) {
            return true; // WP Mail SMTP plugin is installed and activated
        } else {
            return false; // WP Mail SMTP is installed but not activated
        }
    }

    return false; // WP Mail SMTP plugin is not installed
}

// Function to get WP Mail SMTP settings
function sitechal_get_wp_mail_smtp_settings() {
    $options = get_option('wp_mail_smtp');
    if (!$options) {
        return false; // WP Mail SMTP settings not found
    }
    return $options;
}

// Function to display WP Mail SMTP settings, including from email
function sitechal_display_wp_mail_smtp_settings() {
    // Check if the WP Mail SMTP plugin is installed and activated
    if (sitechal_check_wp_mail_smtp_installed_and_activated()) {
        // Get the WP Mail SMTP settings
        $smtp_settings = sitechal_get_wp_mail_smtp_settings();
        if ($smtp_settings) {
            // Get the "from email" from the settings
            $from_email = isset($smtp_settings['mail']['from_email']) ? $smtp_settings['mail']['from_email'] : '';

            echo '<h3>WP Mail SMTP Settings</h3>';
            echo '<p>The WP Mail SMTP plugin is installed and activated.</p>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>Setting</th><th>Value</th></tr></thead><tbody>';

            // Display from email if it exists
            if ($from_email) {
                echo '<tr><td>From Email Address</td><td>' . esc_html($from_email) . '</td></tr>';
                if (strpos($from_email, 'genetech') === false) {
                    echo '<tr><td>Note</td><td>The from email address does not contain "genetech".</td></tr>';
                } else {
                    echo '<tr><td>Note</td><td>The from email address contains "genetech".</td></tr>';
                }
            } else {
                echo '<tr><td>From Email Address</td><td>Not found in WP Mail SMTP settings.</td></tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>WP Mail SMTP settings are not configured properly.</p>';
        }
    } else {
        echo '<p>The WP Mail SMTP plugin is either not installed or not activated.</p>';
    }
}

// Check 'Discourage Search Engines from Indexing Site'
function sitechal_check_search_engine_visibility() {
     echo '<h3 class="panelHeading">WP Reading</h3>';
            
    echo '<div class="searchEngineMain" style="margin-top: 30px;">';
    echo '<div class="textBlurb" style="z-index:9999;">
            <h3 class="panelHeading subheading">Search Engine Visibility</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The <strong>Search Engine Visibility</strong> function allows the user/developer to edit how Search Engine crawlers interact with the web page. Specifically whether or not they are allowed to index information off of the website. This can be useful when making updates to the site and you don’t want potential visitors to see things like placeholder text or half-finished edits.</p>
            </div>
            </div>';
    // echo '<h4>Search Engine Visibility</h4>';
    
    // $discourage_search_engines = get_option('blog_public') == '0' ? 'Yes' : 'No';
    // echo '<p>Discourage search engines from indexing the site: <strong>' . $discourage_search_engines . '</strong>.</p>';
    
    // $discourage_search_engines = get_option('blog_public') == '0' ? 'Yes' : 'No';
    // $class = $discourage_search_engines === 'Yes' ? 'yesActive' : 'noActive';
    // echo '<p class="disSearchText ' . $class . '">Discourage search engines from indexing the site: <strong>' . $discourage_search_engines . '</strong>.</p>';
    // echo '<p class="suggestionText">Lorem ipsum dolor sit amet, consectetur adipiscing elit....</p>';


    $discourage_search_engines = get_option('blog_public') == '0' ? 'Yes' : 'No';
    $class = $discourage_search_engines === 'No' ? 'yesActive' : 'noActive';
    $strong_class = $discourage_search_engines === 'No' ? 'yes' : 'no';
    echo '<p class="disSearchText ' . esc_attr($class) . '">';
    echo esc_html__('Discourage search engines from indexing the site:', 'site-checker-all-in-one-qa-testing');
    echo ' <strong class="' . esc_attr($strong_class) . '">' . esc_html($discourage_search_engines) . '</strong>.</p>';

    if ($discourage_search_engines === 'Yes') {
        echo '<p class="redirectLink">' . esc_html__('You can toggle the Search Engine Indexing behaviour on your site', 'site-checker-all-in-one-qa-testing') . ' <a href="' . esc_url(admin_url('options-reading.php')) . '">here</a></p>';
    }

    echo '</div>';
    
    sitechal_check_robots_txt();
    sitechal_check_ai_txt();
    sitechal_check_llm_txt();
    
}

function sitechal_check_wp_core_updates() {
    // Load the necessary update functions
    if (!function_exists('get_core_updates')) {
        require_once ABSPATH . 'wp-admin/includes/update.php'; // Load update functions
    }

    // Get current WordPress version and check if an update is needed
    $current_wp_version = get_bloginfo('version');
    $core_update = get_core_updates(array('dismissed' => false));

    echo '<div class="textBlurb" style="z-index:9999;">
            <h3 class="panelHeading">WordPress Core Update Status</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The Core plugins required for WordPress to function properly on your site, making sure you don’t lose important functionality.</p>
            </div>
            </div>';
    if (is_wp_error($core_update)) {
        echo '<p class="qa-error">Failed to retrieve core updates. Please try again later.</p>';
        return;
    }

    $wp_update_needed = !empty($core_update) && version_compare($current_wp_version, $core_update[0]->current, '<');

    if ($wp_update_needed) {
        // echo '<p style="color: orange;" class="qa-not-success">An update is available for WordPress. Current version: <strong>' . esc_html($current_wp_version) . '</strong>, New version: <strong>' . esc_html($core_update[0]->current) . '</strong>.</p>';
        echo '<p style="color: orange;" class="qa-success">Your WordPress Version is <strong>' . esc_html($current_wp_version) . '</strong>, Update your WordPress now to avoid causing operational issues!.</p>';
    } else {
        echo '<p class="qa-success padZero">Your WordPress Version is (<strong>' . esc_html($current_wp_version) . '</strong>). You are up to date!</p>';
    }

    // Get list of themes that need to be updated
     echo '<div class="textBlurb">
            <h3 class="panelHeading">Theme Updates</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The WordPress themes your site may be utilising, and what their current statuses and versions are,</p>
            </div>
            </div>';
    $themes_to_update = array();
    $themes = wp_get_themes();
    foreach ($themes as $theme_slug => $theme) {
        $theme_update_data = sitechal_get_theme_update($theme_slug);
        if ($theme_update_data && isset($theme_update_data['new_version'])) {
            $themes_to_update[] = array(
                'slug' => $theme_slug,
                'name' => $theme->get('Name'),
                'version' => $theme->get('Version'),
                'new_version' => $theme_update_data['new_version']
            );
        }
    }

    if ( empty( $themes_to_update ) ) {
        echo '<p class="qa-success">' .
            esc_html__( 'All themes are up to date.', 'site-checker-all-in-one-qa-testing' ) .
            '</p>';
    } else {
        echo '<ul class="versionPlugin">';

        foreach ( $themes_to_update as $theme ) {
            ?>
           <li>
                <strong><?php echo esc_html( $theme['name'] ); ?></strong> -
                <?php
                echo esc_html__('Current version:', 'site-checker-all-in-one-qa-testing') . ' ' . esc_html( $theme['version']) . ', ';
                echo esc_html__('New version:', 'site-checker-all-in-one-qa-testing') . ' ' . esc_html( $theme['new_version']);
                ?>
                <span class="update-button-inline" style="margin-left: 10px;">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="qa-inline-form">
                        <input type="hidden" name="action" value="update_single_theme">
                        <input type="hidden" name="theme_slug" value="<?php echo esc_attr( $theme['slug'] ); ?>">
                        <?php wp_nonce_field( 'update_theme_nonce', '_wpnonce' ); ?>
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Update Now', 'site-checker-all-in-one-qa-testing' ); ?>">
                    </form>
                </span>
            </li>
            <?php
        }
        echo '</ul>';
    }
    sitechal_check_plugins_functionality();
}

// Check for outdated plugins
function sitechal_check_plugins_functionality() {
    $all_plugins = get_plugins();
    $updates = get_site_transient('update_plugins');

    if (empty($all_plugins)) {
        echo '<p>No plugins found on this site.</p>';
        return;
    }
    echo '<table class="wp-list-table widefat fixed striped">
        <div class="textBlurb" style="top: 25px;">
            <div class="blurbIcon">?</div>
            <div class="blurbContent" style="top: 20px;">
                <p>This function displays all installed plugins with their current versions, and allows quick updates in one place.</p>
            </div>
        </div>
        <thead>
            <tr>
                <th>Plugin Name</th>
                <th>Current Version</th>
                <th>Latest Version</th>
                <th class="actionTh">Actions</th>
            </tr>
        </thead>
        </table>';
    echo '<div class="wpUpdateTable" style="position: relative;">';
    echo '<div class="premiumDev">
        <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
            <div class="Icon"></div>
            <p>Upgrade to Premium Features</p>
        </a>
    </div>';
    echo '<div class="wp-list-table widefat fixed striped">';

    //     echo '<div class="premiumDev">
    //     <a href="https://wpsitechecker.com/pricing/" target="_blank" class="premiumIconContain">
    //         <div class="Icon"></div>
    //         <p>Upgrade to Premium Features</p>
    //     </a>
    // </div>';
    echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/plugins.PNG') . '" 
    alt="Responsive" 
    style="max-width:99%; height:auto; border:1px solid #ddd; border-radius:6px;width:100%;" />';
    echo '</div>';
    echo '</div>';

    echo '</div>';
    echo '</div>';

}

// Helper function to check theme updates
function sitechal_get_theme_update($theme_slug) {
    $theme_updates = get_site_transient('update_themes');
    if (isset($theme_updates->response[$theme_slug])) {
        return $theme_updates->response[$theme_slug];
    }
    return false;
}

// Robots.txt Functionality
function sitechal_check_robots_txt() {
    ?>
    <div class="checkRobotsMain" style="margin-top: 30px;">
        <!-- <h3 class="checkHeading">Check Robots.txt</h3> -->
        <div class="textBlurb">
            <h3 class="panelHeading subheading">Check Robots.txt</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>Simply check whether your version of WordPress has the necessary Robots.Txt File to facilitate customizing your site’s interaction with crawlers, and thus it’s Search Engine Visibility.</p>
            </div>
        </div>
        
        <?php
        $site_url = get_site_url();
        $robots_url = $site_url . '/robots.txt';
        
        $response = wp_remote_get($robots_url);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            echo '<p class="qa-success">Successfully retrieved robots.txt content:</p>';
            echo '<pre class="qa-pre">' . esc_html(wp_remote_retrieve_body($response)) . '</pre>';
        } else {
            echo '<p class="qa-error">Failed to retrieve robots.txt file.</p>';
        }
        ?>
    </div>
    <?php
}

// AI.txt Functionality
function sitechal_check_ai_txt() {
    ?>
    <div class="checkRobotsMain" style="margin-top: 30px;">
        <!-- <h3 class="checkHeading">Check Robots.txt</h3> -->
        <div class="textBlurb">
            <h3 class="panelHeading subheading">Check AI.txt</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>Simply check whether your version of WordPress has the necessary AI.Txt File to facilitate customizing your site’s interaction with crawlers, and thus it’s Search Engine Visibility.</p>
            </div>
        </div>
        
        <?php
        $site_url = get_site_url();
        $robots_url = $site_url . '/ai.txt';
    
        $response = wp_remote_get($robots_url);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            echo '<p class="qa-success">Successfully retrieved AI.txt content:</p>';
            echo '<pre class="qa-pre">' . esc_html(wp_remote_retrieve_body($response)) . '</pre>';
        } else {
            echo '<p class="qa-error">Failed to retrieve AI.txt file.</p>';
        }
        ?>
    </div>
    <?php
}

// Robots.txt Functionality
function sitechal_check_llm_txt() {
    ?>
    <div class="checkRobotsMain" style="margin-top: 30px;">
        <!-- <h3 class="checkHeading">Check Robots.txt</h3> -->
        <div class="textBlurb">
            <h3 class="panelHeading subheading">Check LLM.txt</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>Simply check whether your version of WordPress has the necessary LLM.Txt File to facilitate customizing your site’s interaction with crawlers, and thus it’s Search Engine Visibility.</p>
            </div>
        </div>
        
        <?php
        $site_url = get_site_url();
        $robots_url = $site_url . '/llm.txt';

        $response = wp_remote_get($robots_url);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            echo '<p class="qa-success">Successfully retrieved LLM.txt content:</p>';
            echo '<pre class="qa-pre">' . esc_html(wp_remote_retrieve_body($response)) . '</pre>';
        } else {
            echo '<p class="qa-error">Failed to retrieve LLM.txt file.</p>';
        }
        ?>
    </div>
    <?php
}

// 301 Redirection Functionality
function sitechal_check_http_to_https() {
    ?>
    <div class="redirectionPanelMain">
        <div class="textBlurb">
            <h3 class="panelHeading">Check HTTP to HTTPS Redirection</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>This function simply checks to see whether HTTP is being transferred to HTTPS, confirming whether your website is secure in this area.</p>
            </div>
        </div>
        
        <?php
        $site_url = get_site_url();
        $http_url = str_replace('https://', 'http://', $site_url);

        $response = wp_remote_get($http_url, ['redirection' => 0]);

        if (!is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            $location = wp_remote_retrieve_header($response, 'location');

            if ($response_code === 301 && strpos($location, 'https://') === 0) {
                echo '<p class="qa-success"><span style="color:#39465F">Success:</span> The website redirects from HTTP to HTTPS.</p>';
            } else {
                echo '<p class="qa-error"><span style="color:#39465F">Failure:</span> The website does not properly redirect to HTTPS.</p>';
            }
        } else {
            echo '<p class="qa-error"><span style="color:#39465F">Error:</span> Unable to perform the HTTP to HTTPS check.</p>';
        }
        ?>
        <div class="heroBannerImg">
            <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/redirection-Banner.png' ); ?>" alt="<?php echo esc_attr__('hello', 'site-checker-all-in-one-qa-testing'); ?>">
        </div>
    </div>
    <?php
}

function sitechal_check_admin_user_id() {
    ?>
    <h3 class="panelHeading" style="margin-bottom:30px;">Check User's ID & Admin URL</h3>
    <div class="adminCheck">
    <div class="textBlurb" style="z-index:9999;">
        <h3 class="panelHeading subheading">Check User's ID</h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p>Queries the User ID of the admin account that is observing this check. Retrieves the User ID in turn to check if it is User ID 1, and in doing so deciding whether the user having User ID 1 is or is not a security risk.</p>
        </div>
    </div>

    <?php
    $users = get_users();
    $admin_has_id_one = false;
    foreach ($users as $user) {
        if ($user->ID == 1 && user_can($user, 'administrator')) {
            $admin_has_id_one = true;
            break;
        }
    }

    if ($admin_has_id_one) {
        echo '<p class="qa-error">' . esc_html($user->display_name) . ' has user ID 1. This is a security risk!</p>';
    } else {
        echo '<p class="qa-success">User does not have user ID 1. The website is secure.</p>';
    }
    ?></div><?php

    sitechal_check_admin_url();
}

function sitechal_check_aiowps_plugin() {
    echo '<div class="pluginNeedInstall">';
     echo '<div class="textBlurb">
            <h3 class="panelHeading">Security Plugin Check</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>This function checks whether the general security plugins that are recommended have been installed. If it finds that the plugin(s) have not been installed, or that they not working properly, it allows the user/developer to install and enable them.</p>
            </div>
            </div>';
    echo '<h3 class="panelSubHeading">Check All in One Firewall Plugin</h3>';

    $plugin_slug = 'all-in-one-wp-security-and-firewall';
    $plugin_path = $plugin_slug . '/wp-security.php'; // Correct main file

    echo '<div class="pluginInstall">';
    if (is_plugin_active($plugin_path)) {
        echo '<p class="qa-success">The All In One WP Security & Firewall plugin is installed and active.</p>';
    } else {
        echo '<p class="qa-error">The All In One WP Security & Firewall plugin is not installed or activated.</p>';

        // Plugin installed but not active
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
            $activate_url = admin_url('plugins.php?action=activate&plugin=' . urlencode($plugin_path) . '&_wpnonce=' . wp_create_nonce('activate-plugin_' . $plugin_path));
            echo '<p><a href="' . esc_url($activate_url) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">Activate Plugin</a></p>';
        } else {
            $install_url = admin_url('update.php?action=install-plugin&plugin=' . $plugin_slug . '&_wpnonce=' . wp_create_nonce('install-plugin_' . $plugin_slug));
            echo '<p><a href="' . esc_url($install_url) . '" class="button button-primary" target="_blank" rel="noopener noreferrer">Install Plugin</a></p>';
        }
    }

    

    echo '</div>';
    ?>
    <!-- <h3>WP Mail SMTP</h3> -->
    <!-- <?php
    sitechal_display_wp_mail_smtp_settings();

    ?> -->
    <!-- <h3>Check WP Activity Log</h3> -->
    <!-- <?php
    sitechal_check_wp_activity_log_functionality(); ?>-->
    <?php

    
     //premium Div
        ?>
            <div style="position:relative;">
         <div class="premiumDev">
            <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                <div class="Icon"></div>
                <p>Upgrade to Premium Features</p>
            </a>
        </div>';
        <?php
            echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/WPSecurity_CHECKS.jpg') . '" 
            alt="Responsive" 
            style="max-width:99%;width:99%; height:auto; border:1px solid #ddd; border-radius:6px;" />';
            echo '</div>';
        ?>
        <?php
    echo '</div>';
}

//Check Admin URL
function sitechal_check_admin_url() {
    ?>
    <div class="textBlurb">
        <h3 class="panelHeading subheading">Check Admin URL</h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p>Queries the Admin URL, and checks to see whether it is secure and accessible.</p>
        </div>
    </div>
    <?php
    $admin_url = site_url('/wp-admin/');
    $response = wp_remote_get($admin_url);

    if (is_wp_error($response)) {
        echo '<p class="qa-error">' . esc_html__('Could not access the admin URL. Error:', 'site-checker-all-in-one-qa-testing') . ' ' . esc_html($response->get_error_message()) . '</p>';
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            echo '<p class="qa-error">' . esc_html__('The default admin URL (', 'site-checker-all-in-one-qa-testing') . esc_url($admin_url) . esc_html__(') is openly accessible. This could pose a security issue.', 'site-checker-all-in-one-qa-testing') . '</p>';
        } else {
            echo '<p class="qa-success">The default admin URL (' . esc_html($admin_url) . ') is not accessible. You are currently secure!</p>';
        }
    }
}

//Page Speed Check
function sitechal_check_page_speed() {
    // Increase PHP execution time
    ini_set('max_execution_time', 500); // 300 seconds = 5 minutes
    $pages = get_pages();
    $pageOptionsHTML = '';

    if ($pages) {
        foreach ($pages as $page) {
            $page_title = esc_html($page->post_title);
            $page_permalink = esc_url(get_permalink($page->ID));
            $pageOptionsHTML .= '<option value="' . $page_permalink . '">' . $page_title . '</option>';
        }
    } else {
        $pageOptionsHTML = '<option value="">' . esc_html__('No pages found', 'site-checker-all-in-one-qa-testing') . '</option>';
    }

    // Define allowed HTML for select options
    $allowed_html = array(
        'option' => array(
            'value' => true,
            'selected' => true,
        ),
    );
    ?>
    <div class="textBlurb">
        <h3 class="panelHeading"><?php echo esc_html__('Page Speed', 'site-checker-all-in-one-qa-testing'); ?></h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p><?php echo wp_kses_post(
        __('The <strong>Page Speed</strong> function checks, and provides the user/developer with a concise report of the speed at which your site loads on average internet connections. It checks for both the desktop and mobile versions of your website and provides statistics for both.', 'site-checker-all-in-one-qa-testing')
    );?>
        </div>
    </div>
   <div class="pageSpeedSelectSection">
        <h3><?php echo esc_html__('Select up to 3 URL', 'site-checker-all-in-one-qa-testing'); ?></h3>
        <div class="pageSpeedContent">
            <form id="urlForm" method="post" action="" class="pageSpeedData">
                <div class="pageSpeedSelect">
                    <div id="dropdownsContainer">
                        <div class="dropdown-container">
                            <select name="pageURL[]" class="dynamic-dropdown">
                                <option value=""><?php echo esc_html__('Select a page', 'site-checker-all-in-one-qa-testing'); ?></option>
                                <?php echo wp_kses($pageOptionsHTML, $allowed_html); ?>
                            </select>
                        </div>
                    </div>
                    <template id="dropdownTemplate">
                        <div class="dropdown-container">
                            <select name="pageURL[]" class="dynamic-dropdown">
                                <option value=""><?php echo esc_html__('Select a page', 'site-checker-all-in-one-qa-testing'); ?></option>
                                <?php echo wp_kses($pageOptionsHTML, $allowed_html); ?>
                            </select>
                        </div>
                    </template>
                    <button type="button" id="addMoreBtn"><?php echo esc_html__('Add more link', 'site-checker-all-in-one-qa-testing'); ?></button>
                </div>
                <div class="pageSpeedAnalyisBtnContainer">
                    <p id="urlCount" style="display: none;">1</p>
                    <button type="button" id="analyzeBtn" style="cursor: pointer;"><?php echo esc_html__('Analyze', 'site-checker-all-in-one-qa-testing'); ?></button>
                </div>
        
                <div id="analysisButtonsContainer" class="pageSpeedAnalysisBtn" style="display:none;">
                    <div id="pageButtons"></div>
                </div>
        
                <div id="deviceButtons" style="display: none;">
                    <button type="button" id="mobileBtn" class="mobileBtn"><?php echo esc_html__('Mobile', 'site-checker-all-in-one-qa-testing'); ?></button>
                    <button type="button" id="desktopBtn" class="desktopBtn"><?php echo esc_html__('Desktop', 'site-checker-all-in-one-qa-testing'); ?></button>
                </div>
            </form>
        </div>
   </div>
    
    <div id="resultsContainer"></div>

    <div id="coreVitalsButtons" style="margin-top: 20px; display: none; text-align: center;">
        <button type="button" style="cursor:pointer" class="vital-btn" data-vital="all"><?php echo esc_html__('All', 'site-checker-all-in-one-qa-testing'); ?></button>
        <button type="button" style="cursor:pointer" class="vital-btn" data-vital="FCP"><?php echo esc_html__('FCP', 'site-checker-all-in-one-qa-testing'); ?></button>
        <button type="button" style="cursor:pointer" class="vital-btn" data-vital="LCP"><?php echo esc_html__('LCP', 'site-checker-all-in-one-qa-testing'); ?></button>
        <button type="button" style="cursor:pointer" class="vital-btn" data-vital="TBT"><?php echo esc_html__('TBT', 'site-checker-all-in-one-qa-testing'); ?></button>
        <button type="button" style="cursor:pointer" class="vital-btn" data-vital="CLS"><?php echo esc_html__('CLS', 'site-checker-all-in-one-qa-testing'); ?></button>
    </div>
    
    <div id="coreVitalsData" style="margin-top: 20px; text-align: center;"></div>

    <button id="downloadReportBtn" style="margin-top: 20px; padding: 10px 20px; display: none; font-size: 16px; cursor:pointer">
        <?php echo esc_html__('Download Report', 'site-checker-all-in-one-qa-testing'); ?>
    </button>

    <?php
}

function sitechal_check_password_strength() {
    ?>
    <div class="textBlurb">
        <h3 class="panelHeading">Check Password Strength</h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p>This function is enabled when your website requires a password and provides users with a “Password Strength” rating as they create their credentials for access to the site. This is a must-have functionality for any site utilizing user-generated credentials.</p>
        </div>
    </div>
    <div class="passwordCheckForm">
        <form method="post" action="">
            <?php wp_nonce_field('qa_check_admin_password_action', 'qa_admin_nonce'); ?>
            <input type="password" name="admin-password" id="admin-password" placeholder="Enter Admin Password" required>
            <button type="submit" class="button button-primary">Check Strength</button>
        </form>
    </div>
    <?php

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin-password'], $_POST['qa_admin_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['qa_admin_nonce'])), 'qa_check_admin_password_action')){
        $password = sanitize_text_field(wp_unslash($_POST['admin-password']));
        
        $zxcvbn = new Zxcvbn();
        $result = $zxcvbn->passwordStrength($password);
        
        // Display results
        $score = $result['score'];
        echo '<div class="passwordScore">';
        echo '<h4 class="scoreHeading">Strength Analysis:</h4>';

        $class = '';
            if ($score == 0) {
                $class = 'lower0';
                $scoreText = 'very weak';
            } elseif ($score == 1) {
                $class = 'lower1';
                $scoreText = 'Weak';
            }
            elseif ($score == 2) {
                $class = 'lower2';
                $scoreText = 'Fair';
            }
            elseif ($score == 3) {
                $class = 'mid3';
                $scoreText = 'Good';
            }
            elseif ($score == 4) {
                $class = 'strong4';
                $scoreText = 'Strong';
            }

            echo '<div class="circleScoreMain">';
            echo '<div class="circleScore ' . esc_attr($class) . '"><h4>' . esc_html($score) . '</h4></div>';
            echo '<p class="contentScore"><strong>' . esc_html($scoreText) . '</strong></p>';
            echo '</div>';
        // echo '<div class="ratingNote">
        //         <h4>Rating Scale Note:</h4>
        //         <p>This scale is used to evaluate performance, quality, or another relevant factor. The ratings range from <strong>0 to 4</strong>, where:</p>
        //         <ul>
        //             <li>0 = Weak (No capability or very poor performance)</li>
        //             <li>1 = Fair (Below average or limited performance)</li>
        //             <li>2 = Average (Acceptable or meets basic expectations)</li>
        //             <li>3 = Good (Above average performance)</li>
        //             <li>4 = Strong (Excellent or outstanding performance)</li>
        //         </ul>
        //     </div>';

        if ($score >= 3) {
            echo '<p class="qa-success bold">Password is secure.</p>';
        } else {
            echo '<p class="qa-error bold">Password is not secure. Consider using a stronger password.</p>';
        }

        // if (!empty($result['feedback']['suggestions'])) {
        //     echo '<p>Suggestions:</p>';
        //     echo '<ul>';
        //     foreach ($result['feedback']['suggestions'] as $suggestion) {
        //         echo '<li>' . esc_html($suggestion) . '</li>';
        //     }
        //     echo '</ul>';
        // }

         // Hardcoded score meanings (always visible)
         echo '<div class="ratingNote">';
         echo '<h4>Rating Scale Meaning:</h4>';
         echo '<ul>';
         echo '<li><strong>0</strong> - Very Weak: Easily guessable password, no protection.</li>';
         echo '<li><strong>1</strong> - Weak: Low strength, may resist only trivial attacks.</li>';
         echo '<li><strong>2</strong> - Fair: Acceptable, but improvements recommended.</li>';
         echo '<li><strong>3</strong> - Good: Strong enough for general usage.</li>';
         echo '<li><strong>4</strong> - Excellent: Very strong, recommended for high-security areas.</li>';
         echo '</ul>';
         echo '</div>';

        echo '</div>';
    }
}

//Broken Links
function sitechal_broken_links() {
    ?>
     <div class="borkenLinkMain">
        <div class="textBlurb">
            <h3 class="panelHeading">Broken Links</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>This function checks for broken links and images, it also reports where these broken URLs may be, allowing for quick and efficient bug fixing and site updates.</p>
            </div>
        </div>

            <div style="position:relative;">
                <div class="premiumDev">
                    <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                        <div class="Icon"></div>
                        <p>Upgrade to Premium Features</p>
                    </a>
                </div>
                <?php
             echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/Broken_links.jpg') . '" 
                alt="Responsive" 
                style="max-width:100%; height:auto; border:1px solid #ddd; border-radius:6px;" />';
                ?>
            </div>
    </div>
    <?php
}

//Word Search
function sitechal_custom_site_search_results() {
    ?>
    <div class="textBlurb">
        <h3 class="panelHeading">Word Search</h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p>This function allows you to search up specific keywords or phrases throughout the website, telling you how many times and where they have been used. Useful for applying variation within the Key Words and Phrases used throughout the site content.</p>
        </div>
    </div>
    <div style="position:relative;">
        <div class="premiumDev">
            <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                <div class="Icon"></div>
                <p>Upgrade to Premium Features</p>
            </a>
        </div>
        <?php
             echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/word_search.jpg') . '" 
         alt="Responsive" 
         style="max-width:100%;width:100%; height:auto; " />';
        ?>
    </div>
    <?php
}

//SSL Rating
function sitechal_check_ssllabs() {
    // Get current domain
    $parsed_url = wp_parse_url(get_site_url());
    $websiteUrl = $parsed_url['host'];
	// $websiteUrl = "pegavault.com";
    $headers = [
        "User-Agent: Mozilla/5.0",
        "email: info@genetech.co"
    ];

    $context = stream_context_create([
        'http' => [
            'method' => "GET",
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
            'timeout' => 10
        ]
    ]);

    // Build API endpoint
    $apiUrl = "https://api.ssllabs.com/api/v4/analyze?host=" . urlencode($websiteUrl);

    // Wait for analysis to complete
    $startTime = time();
    $timeout = 300; // 5 minutes max

    do {
        $response = @file_get_contents($apiUrl, false, $context);
	
        if ($response === false) {
           echo '<div class="textBlurb">
            <h3 class="panelHeading">SSL Rating</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The <strong>SSL Rating</strong> function provides the user/developer with the site’s SSL rating. This includes factors such as how valid or secure your site’s SSL Certificate, Protocol Support, and Key Exchange(s) are.</p>
            </div>
            </div>'; 
           die('<p class="sslErrorHeading">' . esc_html__('Unable to retrieve data for', 'site-checker-all-in-one-qa-testing') . ' <strong>' . esc_html($websiteUrl) . '</strong>. ' . esc_html__('The website may be down or unreachable at the moment. Please check the URL or refresh the page and try again.', 'site-checker-all-in-one-qa-testing') . '</p>');

            return;
        }

        $result = json_decode($response, true);

        if (isset($result['status']) && $result['status'] === 'READY') {
            break;
        }

        sleep(10); // Wait before retrying
    } while (time() - $startTime < $timeout);
	
   // Extract grade if available
	if (isset($result['endpoints'][0]['grade'])) {
		$grade = $result['endpoints'][0]['grade'];
        echo '<div class="textBlurb">
            <h3 class="panelHeading">SSL Rating</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The <strong>SSL Rating</strong> function provides the user/developer with the site’s SSL rating. This includes factors such as how valid or secure your site’s SSL Certificate, Protocol Support, and Key Exchange(s) are.</p>
            </div>
        </div>';
        // echo "<div class='sslResult'><div class='row'><p>Analyzed URL:</p><a href='{$websiteUrl}'>{$websiteUrl}</a></div></div>";
        if ($grade === 'A+'){
            echo '<div class="gradeSum greenG">
            <div class="gradeRank"><h3>A+</h3><p>Grade</p></div>
            <div class="gradeMean long"><h3>Excellent</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p><span>Strong encryption</span>, modern protocols, no known vulnerabilities, and extra security features (like HSTS).</p></div>
            </div>';
        }
        if ($grade === 'A-'){
            echo '<div class="gradeSum greenG">
            <div class="gradeRank"><h3>A-</h3><p>Grade</p></div>
            <div class="gradeMean long"><h3>Excellent</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p><span>Strong encryption</span>, modern protocols, no known vulnerabilities, and extra security features (like HSTS).</p></div>
            </div>';
        }
        if ($grade === 'A'){
            echo '<div class="gradeSum greenG">
            <div class="gradeRank"><h3>A</h3><p>Grade</p></div>
            <div class="gradeMean long"><h3>Excellent</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p><span>Strong encryption</span>, modern protocols, no known vulnerabilities, and extra security features (like HSTS).</p></div>
            </div>';
        }
        if ($grade === 'B'){
            echo '<div class="gradeSum yellowG">
            <div class="gradeRank"><h3>B</h3><p>Grade</p></div>
            <div class="gradeMean"><h3>Good</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p><span>Generally secure</span>, but may use some older protocols or have minor misconfigurations.</p></div>
            </div>';
        }
        if ($grade === 'C'){
            echo '<div class="gradeSum orangeG">
            <div class="gradeRank"><h3>C</h3><p>Grade</p></div>
            <div class="gradeMean"><h3>Fair</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p><span>Basic security</span>, but lacks best practices — could have weak ciphers or support outdated protocols.</p></div>
            </div>';
        }
        if ($grade === 'D'){
            echo '<div class="gradeSum orangeG">
            <div class="gradeRank"><h3>D</h3><p>Grade</p></div>
            <div class="gradeMean"><h3>Poor</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p><span>Insecure setup</span>, outdated protocols (like TLS 1.0), or weak encryption — needs urgent improvement.</p></div>
            </div>';
        }
        if ($grade === 'F'){
            echo '<div class="gradeSum redG">
            <div class="gradeRank"><h3>F</h3><p>Grade</p></div>
            <div class="gradeMean long"><h3>Failing</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p>Severely insecure or broken — vulnerable to attacks, e.g., Heartbleed, POODLE, etc.</p></div>
            </div>';
        }
        if ($grade === 'T'){
            echo '<div class="gradeSum redG">
            <div class="gradeRank"><h3>T</h3><p>Grade</p></div>
            <div class="gradeMean long"><h3>Timeout/Error</h3><p>Status</p></div>
            <div class="gradeSumary"><h3>Summary</h3><p>The server didn’t respond properly or the certificate is invalid/missing.</p></div>
            </div>';
        }
       
        } else {
		// Optionally, you might want to log the error or perform other actions
        echo '
        <div class="textBlurb">
            <h3 class="panelHeading">SSL Rating</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The <strong>SSL Rating</strong> function provides the user/developer with the site’s SSL rating. This includes factors such as how valid or secure your site’s SSL Certificate, Protocol Support, and Key Exchange(s) are.</p>
            </div>
        </div>
        ';
    
        // This one needs double quotes to parse the variable inside the string
        echo "<p class='sslErrorHeading'>" . esc_html__('Unable to retrieve SSL Labs rating for', 'site-checker-all-in-one-qa-testing') . ' <strong>' . esc_html($websiteUrl) . '</strong></p>';
    }
    ?>
    <div class='srdbMain' style="position:relative;">
     <div class="premiumDev">
        <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
            <div class="Icon"></div>
                <p>Upgrade to Premium Features</p>
        </a>
    </div>';
    <?php
    echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/ssl.PNG') . '" 
    alt="Responsive" 
    style="max-width:100%;width:100%; height:auto;" />';
    echo '</div>';
    ?>
</div>
<?php
}

//SRDB
function sitechal_checkSrdbAndTakeScreenshot() {
    ?>
    <div class="textBlurb">
        <h3 class="panelHeading"><?php esc_html_e('404 Page', 'site-checker-all-in-one-qa-testing'); ?></h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p>The “Page 404” function lets you check whether your website’s 404 page works correctly and displays a custom message, ensuring visitors know the site is active even if a page doesn’t exist.</p>
        </div>
    </div>
    <div class='srdbMain' style="position:relative;">
        <div class="premiumDev">
            <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                <div class="Icon"></div>
                <p>Upgrade to Premium Features</p>
            </a>
        </div>    
        <?php
        echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/404_page.jpg') . '" 
         alt="Responsive" 
         style="max-width:100%;width:100%; height:auto;" />';
        ?>
    </div>
    <?php
}

//Check if External links opens in a new tab
function sitechal_checkLinksOpenInNewTab() {
    ?>
    <div class="borkenLinkMain">
        <div class="textBlurb">
            <h3 class="panelHeading">External & Internal Links
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>This tool analyzes all links and separates them into external links (that open in new tabs) and internal links.</p>
            </div>
        </div>
        <div class="externalContainer">
            <div class="LinksContainer">
                <!-- <h1>Responsive Website Tester</h1> -->
            <div style="position:relative;">
                <div class="premiumDev">
                    <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                        <div class="Icon"></div>
                        <p>Upgrade to Premium Features</p>
                    </a>
                </div>';
                <?php
                echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/Internal_Externalinks.jpg') . '" 
                alt="Responsive" 
                style="max-width:100%;width:100% height:auto; " />';
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
    <?php
}

//Accessibility Report
function sitechal_check_accessibility_report() {
    // Generate dropdown options
    $pages = get_pages();
    $pageOptionsHTML = '';

    if ($pages) {
        foreach ($pages as $page) {
            $page_title = esc_html($page->post_title);
            $page_permalink = esc_url(get_permalink($page->ID));
            $pageOptionsHTML .= '<option value="' . $page_permalink . '">' . $page_title . '</option>';
        }
    } else {
        $pageOptionsHTML = '<option value="">No pages found</option>';
    }

    // Handle form submission
    $resultsHtml = '';
    $showPremiumButton = false; // flag for "Free Trial Ended" button
    $disableAddButton = false; // NEW FLAG
    if (
        isset($_SERVER['REQUEST_METHOD']) &&
        'POST' === $_SERVER['REQUEST_METHOD'] &&
        isset($_POST['username'], $_POST['email'], $_POST['pageURL'], $_POST['_wpnonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'sitechal_accessibility_form')
    ) {
        $username = sanitize_text_field(wp_unslash($_POST['username']));
        $email = sanitize_email(wp_unslash($_POST['email']));
        $page_urls = wp_unslash($_POST['pageURL']);
        $urls = is_array($page_urls) ? array_map('esc_url_raw', $page_urls) : [];

        $resultsHtml .= '<div class="accessibility-results">';

        foreach ($urls as $url) {
            if (empty($url)) continue;

            // Normalize the URL (scheme + host + path, remove trailing slash)
            $parsed_url = wp_parse_url($url);
            $normalized_url = isset($parsed_url['scheme'], $parsed_url['host'])
                ? $parsed_url['scheme'] . '://' . $parsed_url['host'] . rtrim($parsed_url['path'] ?? '', '/')
                : $url;

            // Check the last checked URL (if any)
            $last_checked_url = get_transient('sitechal_last_checked_url');

            // If user has checked another URL before — block new checks
            if ($last_checked_url && $last_checked_url !== $normalized_url) {
                $showPremiumButton = true; // trigger button display
                $disableAddButton = true;
                $resultsHtml .= '<p style="color:red;font-size:16px;">You’ve reached your free link limit! Upgrade to Premium to continue scanning and get in-depth accessibility insights.</p>';
                continue;
            }

            // Save this as the allowed URL for rechecks
            set_transient('sitechal_last_checked_url', $normalized_url, 0);

            // Proceed to API call
            $api_called = true;
            $payload = json_encode([
                'url'   => $url,
                'name'  => $username,
                'email' => $email,
            ]);

            $http_response = wp_remote_post(
                'https://api.wpsitechecker.com/test-accessibility',
                [
                    'timeout' => 30,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $payload,
                ]
            );

            $response_body = is_wp_error($http_response) ? '' : wp_remote_retrieve_body($http_response);
            $data          = json_decode($response_body, true);

            $resultsHtml .= '<div class="gradeSumary">';
            if (isset($data['message'])) {
                $resultsHtml .= '<p>Your request for an accessibility report has been submitted. Keep an eye on your email ' . esc_html($email) . ' shortly. </p>';
            } else {
                $resultsHtml .= '<p style="color:red;font-size:16px;">⚠️ The server is temporarily unable to process your request.<br>
                Please try again later.</p>';
            }
            $resultsHtml .= '</div>';
        }

        $resultsHtml .= '</div>';
    }


    // HTML Output
    ?>
    <div class="textBlurb">
        <h3 class="panelHeading">Accessibility Report</h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p>The Accessibility Report function provides you with a comprehensive report on accessibility and flags issues based on Web Accessibility Standards.</p>
        </div>
    </div>

    <div class="pageSpeedSelectSection accessSection">
        <h3 class="accessTotalHeading">Select only 1 unique URL</h3>
        <div class="pageSpeedContent">
            <form id="urlForm" method="post" action="" class="pageSpeedData">
                <?php wp_nonce_field('sitechal_accessibility_form'); ?>
                <div class="accessibilityCheckForm fieldSection">
                    <div class="form-group">
                        <label for="username" class="userIcon"></label>
                        <input type="text" name="username" id="username" required placeholder="Name" value="<?php echo esc_attr(isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email" class="emailIcon"></label>
                        <input type="email" name="email" id="email" required placeholder="Email" value="<?php echo esc_attr(isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : ''); ?>">
                    </div>
                </div>

                <div class="pageSpeedSelect accessSectionSelect">
                    <div id="dropdownsContainer">
                        <?php
                        $selectedURLs = isset($_POST['pageURL']) ? (array) wp_unslash($_POST['pageURL']) : [''];
                        foreach ($selectedURLs as $selectedURL) : ?>
                            <div class="dropdown-container">
                                <select name="pageURL[]" class="dynamic-dropdown">
                                    <option value="">Select a Page</option>
                                    <?php
                                    $selected_options_html = str_replace('value="' . esc_attr($selectedURL) . '"', 'value="' . esc_attr($selectedURL) . '" selected', $pageOptionsHTML);
                                    echo wp_kses(
                                        $selected_options_html,
                                        [
                                            'option' => [
                                                'value'    => true,
                                                'selected' => true,
                                            ],
                                        ]
                                    );
                                    ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <template id="dropdownTemplate">
                        <div class="dropdown-container">
                            <select name="pageURL[]" class="dynamic-dropdown">
                                <option value="">Select a page</option>
                                <?php
                                echo wp_kses(
                                    $pageOptionsHTML,
                                    [
                                        'option' => [
                                            'value'    => true,
                                            'selected' => true,
                                        ],
                                    ]
                                );
                                ?>
                            </select>
                             <!-- <button type="button" class="removeBtn" style="margin-left: 10px;">Remove</button> -->
                        </div>
                    </template>

                    <?php if (!$disableAddButton): ?>
                        <button type="button" id="addBtn">Add other link</button>
                    <?php endif; ?>

                   <?php if ($showPremiumButton): ?>
                    <div class="premiumNotice">
                        <button type="button" id="premiumButton" style="background:#f44336;color:#fff;border:none;padding:10px 16px;border-radius:5px;cursor:pointer;">
                        <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank">Unlock More with Premium</a>
                        </button>
                    </div>
                <?php endif; ?>
                    
                </div>

                <div class="pageSpeedAnalyisBtnContainer">
                    <p id="urlCount" style="display: none;"></p>
                    <button type="submit" id="analyzedBtn" style="cursor: pointer;">Analyze</button>
                </div>
            </form>
        </div>

        <div id="resultsContainer" class="accessibility accessibilityResults">
            <?php
            echo wp_kses(
                $resultsHtml,
                [
                    'div' => [
                        'class' => true,
                        'style' => true,
                    ],
                    'p'  => [
                        'style' => true,
                    ],
                    'br' => [],
                ]
            );
            ?>
        </div>
    </div>
    
    <?php
}

//Main Headings Checker Function
function sitechal_create_dropdown_for_headings(){
    echo '<div class="textBlurb">
        <h3 class="panelHeading">Headings Checker</h3>
        <div class="blurbIcon">?</div>
        <div class="blurbContent">
            <p>The Headings Checker function lets you review all the headings on a single page or across your entire site, helps identify placement, and ensures proper formatting structure.</p>
        </div>
    </div>';

    echo '<div class="wrap headingMainContent" style="position:relative;">';
        echo '<div class="premiumDev">
                <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                    <div class="Icon"></div>
                    <p>Upgrade to Premium Features</p>
                </a>
            </div>';

    // Form with dropdown

    // echo    '<div class="headSelectDownBtn">';

    echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/headings.jpg') . '" 
             alt="Headings Checker Banner" 
             style="max-width:100%;width:100%; height:auto;" />';
    // echo '</div>';
    echo '</div>'; // .wrap
}
 
//Responsive
function sitechal_responsive_tester_page() {
    ?>
        <div class="textBlurb">
            <h3 class="panelHeading">Responsive Website Tester</h3>
            <div class="blurbIcon">?</div>
            <div class="blurbContent">
                <p>The Responsive Test function allows developers and QA Engineers to access and test site responsiveness and design across different screen sizes and device orientations.</p>
            </div>
        </div>
        <div class="wrap">
            <!-- <h1>Responsive Website Tester</h1> -->
            <div style="position:relative;">
                <div class="premiumDev">
                    <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                        <div class="Icon"></div>
                        <p>Upgrade to Premium Features</p>
                    </a>
                </div>';
                <?php
        echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/responsive.jpg') . '" 
        alt="Responsive" 
        style="max-width:100%;width:100%; height:auto; " />';
        echo '</div>';
        echo '</div>';
    }

/**
 * Dropdown + iframe loader for Spell & Grammar
 */
function sitechal_create_dropdown_for_spell_and_grammar_check() {
    if ( ! current_user_can('manage_options') ) {
        echo '<div class="notice notice-error"><p>Insufficient permissions.</p></div>';
        return;
    }

    $pages = get_pages([
        'sort_order'  => 'ASC',
        'sort_column' => 'post_title',
        'post_status' => 'publish',
        'number'      => 0,
    ]);

    $selected_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;
    $language    = isset($_GET['language']) ? sanitize_text_field(wp_unslash($_GET['language'])) : 'en-US';

    echo    '<div class="textBlurb">
                <h3 class="panelHeading">Spell & Grammar Checker</h3>
                <div class="blurbIcon">?</div>
                <div class="blurbContent">
                    <p>The Spell and Grammar Check” function lets you analyse the spelling and grammar of any page on your site, with options for American or British English.</p>
                </div>
            </div>';
    
    echo '<div class="wrap spell-grammar-checker-container" style="position:relative;">';
    echo '<div class="premiumDev">
                    <a href="https://wpsitechecker.com/pricing/?utm_source=freeplugin&utm_medium=dashboard&utm_campaign=upgradetopremium" target="_blank" class="premiumIconContain">
                        <div class="Icon"></div>
                        <p>Upgrade to Premium Features</p>
                    </a>
                </div>';

    // Form
    echo '<div class="spell-grammar-form">';
    echo '<form method="get" action="' . esc_url(admin_url('admin.php')) . '">';
    echo '<input type="hidden" name="page" value="sitechal-general-checks">';
    echo '<input type="hidden" name="tab" value="spell-check-grammar-check">';

    echo '<select name="page_id" class="pageNameSelect">';
    echo '<option value="">— Choose a page —</option>';
    foreach ($pages as $p) {
        $title = $p->post_title ? $p->post_title : '(no title)';
        echo '<option value="' . esc_attr($p->ID) . '"' . selected($selected_id, $p->ID, false) . '>' . esc_html($title) . '</option>';
    }
    echo '</select> ';

    // Language dropdown
    $languages = [
        'en-US' => 'American English',
        'en-GB' => 'British English'
    ];
    // echo '&nbsp;&nbsp;<label for="language">Accent: </label>';
    echo '<select name="language" id="language" class="languageSelect">';
    foreach ($languages as $code => $label) {
        echo '<option value="' . esc_attr($code) . '"' . selected($language, $code, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';

    echo ' <button type="submit" class="button button-primary">Run Check</button>';
    echo '</form>';
    echo '<img src="' . esc_url(plugin_dir_url(__FILE__) . 'assets/grammar.jpg') . '" 
             alt="Headings Checker Banner" 
             style="max-width:100%;width:100%; height:auto; " />';
    echo '</div>'; 
}

// Schedule the automation cron job
function sitechal_schedule_automation_cron($frequency) {
    // Clear any existing schedule
    sitechal_unschedule_automation_cron();
    
    // Schedule the first run based on frequency
    // For immediate scheduling, use current time + interval
    $intervals = [
        'weekly' => WEEK_IN_SECONDS,
        'biweekly' => WEEK_IN_SECONDS * 2,      // 2 weeks
        'monthly' => DAY_IN_SECONDS * 30,        // 30 days
        'quarterly' => DAY_IN_SECONDS * 90       // 90 days (3 months)
    ];

    $interval_seconds = isset($intervals[$frequency]) ? $intervals[$frequency] : DAY_IN_SECONDS;

    // WordPress cron uses UTC timestamps internally
    // time() returns current UTC timestamp, which is what we need
    // Schedule the first run based on the selected frequency interval
    $next_run = time() + $interval_seconds;

    // Debug logging to help troubleshoot timezone issues
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Site Checker Automation: Scheduling cron');
        error_log('Site Checker Automation: Current UTC time = ' . time() . ' (' . gmdate('Y-m-d H:i:s', time()) . ' UTC)');
        error_log('Site Checker Automation: Next run UTC = ' . $next_run . ' (' . gmdate('Y-m-d H:i:s', $next_run) . ' UTC)');
        error_log('Site Checker Automation: Next run Local = ' . wp_date('Y-m-d H:i:s', $next_run) . ' (' . wp_timezone_string() . ')');
        error_log('Site Checker Automation: Next run Local = ' . wp_date('Y-m-d H:i:s', $next_run) . ' (' . wp_timezone_string() . ')');
        error_log('Site Checker Automation: Next run Local = ' . wp_date('Y-m-d H:i:s', $next_run) . ' (' . wp_timezone_string() . ')');
        error_log('Site Checker Automation: Frequency = ' . $frequency);
    }

    // Schedule new cron job
    if (!wp_next_scheduled('sitechal_automation_cron')) {
        wp_schedule_event($next_run, $frequency, 'sitechal_automation_cron');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Site Checker Automation: Cron scheduled successfully');
        }
    }
}

// Unschedule the automation cron job
function sitechal_unschedule_automation_cron() {
    $timestamp = wp_next_scheduled('sitechal_automation_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'sitechal_automation_cron');
    }
}

// AJAX handler for auto-updating cron schedule when checkboxes change
add_action('wp_ajax_sitechal_update_cron_schedule', 'sitechal_ajax_update_cron_schedule');
function sitechal_ajax_update_cron_schedule() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sitechal_auto_update_cron')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    // Get and sanitize data
    $enabled_checks = isset($_POST['enabled_checks']) && is_array($_POST['enabled_checks'])
        ? array_map('sanitize_text_field', wp_unslash($_POST['enabled_checks']))
        : [];
    $frequency = isset($_POST['frequency']) ? sanitize_text_field(wp_unslash($_POST['frequency'])) : 'daily';

    // Validate frequency
    $valid_frequencies = ['hourly', 'daily', 'weekly', 'biweekly', 'monthly', 'quarterly'];
    if (!in_array($frequency, $valid_frequencies, true)) {
        $frequency = 'daily';
    }

    // Check if license is valid for premium checks
    $is_premium = false;
    $free_checks = ['core_updates', 'security_plugins', 'ssl_rating'];

    // Filter out premium checks if not licensed
    if (!$is_premium) {
        $enabled_checks = array_intersect($enabled_checks, $free_checks);
    }

    // Update the enabled checks option (don't update other settings)
    update_option('sitechal_enabled_checks', $enabled_checks);

    // Update cron schedule
    if (!empty($enabled_checks)) {
        sitechal_schedule_automation_cron($frequency);
        wp_send_json_success(['message' => 'Automation schedule updated', 'checks' => count($enabled_checks)]);
    } else {
        sitechal_unschedule_automation_cron();
        wp_send_json_success(['message' => 'Automation disabled (no checks enabled)', 'checks' => 0]);
    }
}

// Hook the automation cron
add_action('sitechal_automation_cron', 'sitechal_run_automated_checks');

// Main automation runner function
function sitechal_run_automated_checks() {
    $enabled_checks = get_option('sitechal_enabled_checks', []);
    $send_on_issues_only = get_option('sitechal_send_on_issues_only', 0);

    if (empty($enabled_checks)) {
        return;
    }

    $results = [];
    $has_issues = false;

    // Run enabled checks
    foreach ($enabled_checks as $check) {
        $check_result = sitechal_run_single_check($check);
        $results[$check] = $check_result;

        if (!empty($check_result['issues'])) {
            $has_issues = true;
        }
    }

    // Send email if not set to issues-only or if there are issues
    if (!$send_on_issues_only || $has_issues) {
        sitechal_send_automation_report($results);
    }
}

// Run a single automated check
function sitechal_run_single_check($check_type) {
    $result = [
        'status' => 'success',
        'issues' => [],
        'data' => []
    ];

    try {
        switch ($check_type) {
            case 'core_updates':
                $result = sitechal_automated_core_updates_check();
                break;
            case 'ssl_rating':
                $result = sitechal_automated_ssl_rating_check();
                break;
            case 'security_plugins':
                $result = sitechal_automated_security_plugins_check();
                break;
        }
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['issues'][] = 'Check failed with error: ' . $e->getMessage();
    }

    return $result;
}


function sitechal_automated_core_updates_check() {
    $result = ['status' => 'success', 'issues' => [], 'data' => []];
    // Load the necessary update functions
    if (!function_exists('get_core_updates')) {
        require_once ABSPATH . 'wp-admin/includes/update.php'; // Load update functions
    }
    $current_wp_version = get_bloginfo('version');
    $core_update = get_core_updates(array('dismissed' => false));

    if (!empty($core_update) && version_compare($current_wp_version, $core_update[0]->current, '<')) {
        $result['issues'][] = 'WordPress core update available: ' . $core_update[0]->current . ' (current: ' . $current_wp_version . ')';
        $result['status'] = 'warning';
        $result['data']['current_version'] = $current_wp_version;
        $result['data']['available_version'] = $core_update[0]->current;
    } else {
        $result['data']['message'] = 'WordPress core is up to date (version: ' . $current_wp_version . ')';
    }

    return $result;
}

function sitechal_automated_ssl_rating_check() {
    $result = ['status' => 'success', 'issues' => [], 'data' => []];

    // Get current domain
    $parsed_url = wp_parse_url(get_site_url());
    $websiteUrl = $parsed_url['host'] ?? '';

    $headers = ["User-Agent: Mozilla/5.0", "email: info@genetech.co"];
    $context = stream_context_create([
        'http' => [
            'method' => "GET",
            'header' => implode("\r\n", $headers),
            'ignore_errors' => true,
            'timeout' => 10
        ]
    ]);

    $apiUrl = "https://api.ssllabs.com/api/v4/analyze?host=" . urlencode($websiteUrl);
    $response = @file_get_contents($apiUrl, false, $context);

    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['endpoints'][0]['grade'])) {
            $grade = $data['endpoints'][0]['grade'];
            $result['data']['grade'] = $grade;

            // Consider grades below A as issues
            if (!in_array($grade, ['A+', 'A', 'A-'])) {
                $result['issues'][] = 'SSL rating is ' . $grade . ' - consider improving SSL configuration';
                $result['status'] = 'warning';
            } else {
                $result['data']['message'] = 'SSL rating is good: ' . $grade;
            }
        }
    } else {
        $result['issues'][] = 'Could not retrieve SSL rating';
        $result['status'] = 'error';
    }

    return $result;
}

function sitechal_automated_security_plugins_check() {
    $result = ['status' => 'success', 'issues' => [], 'data' => []];

    $security_plugins = [
        'all-in-one-wp-security-and-firewall/wp-security.php',
        'wordfence/wordfence.php',
        'better-wp-security/backup.php',
        'sucuri-scanner/sucuri.php',
        'wp-security-audit-log/wp-security-audit-log.php'
    ];

    $installed_count = 0;
    $active_count = 0;

    foreach ($security_plugins as $plugin) {
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
            $installed_count++;
            if (is_plugin_active($plugin)) {
                $active_count++;
            }
        }
    }

    if ($active_count === 0) {
        $result['issues'][] = 'No security plugins are active - consider installing and activating security plugins';
        $result['status'] = 'warning';
    } elseif ($active_count < 2) {
        $result['issues'][] = 'Only ' . $active_count . ' security plugin(s) active - consider adding more security layers';
        $result['status'] = 'warning';
    } else {
        $result['data']['message'] = $active_count . ' security plugins active';
    }

    $result['data']['installed'] = $installed_count;
    $result['data']['active'] = $active_count;

    return $result;
}

// Send automation report via email
function sitechal_send_automation_report($results) {
    $email_recipients = get_option('sitechal_email_recipients', get_option('admin_email'));
    $recipients = array_map('trim', explode(',', $email_recipients));

    $subject = 'WP SiteChecker - Automated QA Report';

    $message = sitechal_generate_automation_report_html($results);

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    ];

    foreach ($recipients as $recipient) {
        if (is_email($recipient)) {
            wp_mail($recipient, $subject, $message, $headers);
        }
    }
}

// Generate HTML report for email
function sitechal_generate_automation_report_html($results) {
    $site_name = get_bloginfo('name');
    $site_url = get_site_url();
    $report_time = current_time('F j, Y \a\t g:i A');

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>WP SiteChecker - Automated QA Report</title>
        <style>
            /* Reset and base styles */
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 0; 
                background: #f5f5f5;
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            
            /* Container */
            .container { 
                max-width: 800px; 
                margin: 0 auto; 
                background: white; 
                padding: 30px; 
                border-radius: 10px;
            }
            
            /* Header */
            .header { 
                text-align: center; 
                border-bottom: 2px solid #060868; 
                padding-bottom: 20px; 
                margin-bottom: 30px; 
            }
            
            .header h1 { 
                color: #060868; 
                margin: 0; 
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                font-size: 24px;
                line-height: 1.3;
            }
            
            .header h1 img {
                height: 40px; 
                width: auto; 
                margin-top: -18px;
            }
            
            .header p {
                margin: 10px 0;
                font-size: 14px;
                line-height: 1.5;
            }
            
            /* Summary */
            .summary { 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 5px; 
                margin-bottom: 30px; 
            }
            
            .summary h2 {
                margin-top: 0;
                font-size: 20px;
            }
            
            /* Check Results */
            .check-result { 
                margin-bottom: 20px; 
                padding: 15px; 
                border-left: 4px solid #060868; 
                background: #f8f9fa;
                word-wrap: break-word;
            }
            
            .check-result.success { border-left-color: #28a745; }
            .check-result.warning { border-left-color: #dc3545; }
            .check-result.error { border-left-color: #dc3545; }
            
            .check-title { 
                font-weight: bold; 
                margin-bottom: 10px;
                font-size: 16px;
            }
            
            .issues { 
                color: #dc3545; 
                margin-top: 10px; 
            }
            
            .issues ul, .data ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            
            .issues li, .data li {
                margin-bottom: 5px;
                line-height: 1.5;
            }
            
            .data { 
                color: #666; 
                margin-top: 10px; 
            }
            
            /* Score circles container */
            .scores-container {
                text-align: center;
                margin: 20px 0;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            /* Footer */
            .footer { 
                text-align: center; 
                margin-top: 30px; 
                padding-top: 20px; 
                border-top: 1px solid #ddd; 
                color: #666;
                font-size: 14px;
            }
            
            .footer p {
                margin: 5px 0;
            }
            
            /* Links */
            a {
                color: #060868;
                text-decoration: none;
            }
            
            a:hover {
                text-decoration: underline;
            }
            
            /* Mobile Responsive Styles */
            @media only screen and (max-width: 600px) {
                body {
                    padding: 10px !important;
                }
                
                .container {
                    padding: 20px !important;
                    border-radius: 5px !important;
                }
                
                .header h1 {
                    font-size: 18px !important;
                    flex-direction: column !important;
                    gap: 10px !important;
                }
                
                .header h1 img {
                    height: 30px !important;
                    margin-top: 0 !important;
                }
                
                .header p {
                    font-size: 13px !important;
                }
                
                .summary {
                    padding: 15px !important;
                }
                
                .summary h2 {
                    font-size: 18px !important;
                }
                
                .check-result {
                    padding: 12px !important;
                    margin-bottom: 15px !important;
                }
                
                .check-title {
                    font-size: 14px !important;
                }
                
                .issues ul, .data ul {
                    padding-left: 15px !important;
                }
                
                .scores-container {
                    gap: 10px !important;
                }
                
                .footer {
                    font-size: 12px !important;
                }
            }
            
            /* Extra small devices */
            @media only screen and (max-width: 480px) {
                .container {
                    padding: 15px !important;
                }
                
                .header h1 {
                    font-size: 16px !important;
                }
                
                .check-title {
                    font-size: 13px !important;
                }
            }
            
            /* Email client specific fixes */
            @media screen and (max-width: 600px) {
                table[class="body"] {
                    width: 100% !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>
                    <img 
                        src="' . esc_url( plugin_dir_url( __FILE__ ) . 'wp-site-checker.svg' ) . '" 
                        alt="Site Checker"
                    />
                    <span>SiteChecker - Automated QA Report</span>
                </h1>
                <p><strong>Site:</strong> ' . esc_html( $site_name ) . ' (<a href="' . esc_url( $site_url ) . '" target="_blank">' . esc_html( $site_url ) . '</a>)</p>
                <p><strong>Report Generated:</strong> ' . esc_html( $report_time ) . '</p>
            </div>

            <div class="summary">
                <h2>Summary</h2>
                <p>This automated report contains results from ' . count( $results ) . ' QA checks performed on your website.</p>
            </div>
    ';

    $total_checks = count($results);
    $success_count = 0;
    $warning_count = 0;
    $error_count = 0;

    foreach ($results as $check_type => $result) {
            $status_class = $result['status'];
            if ($result['status'] === 'success') $success_count++;
            elseif ($result['status'] === 'warning') $warning_count++;
            elseif ($result['status'] === 'error') $error_count++;

            $check_labels = [
                'site_health' => 'Site Health Check',
                'plugin_updates' => 'Plugin Updates Check',
                'theme_updates' => 'Theme Updates Check',
                'core_updates' => 'WordPress Core Updates Check',
                'broken_links' => 'Broken Links Check',
                'broken_images' => 'Broken Images Check',
                'ssl_rating' => 'SSL Rating Check',
                'http_to_https' => 'HTTP to HTTPS Redirection Check',
                'admin_security' => 'Admin Security Check',
                'security_plugins' => 'Security Plugins Check',
                'search_engine_visibility' => 'Search Engine Visibility Check',
                'uploads_access' => 'Uploads Directory Access Check',
                'youtube_settings' => 'YouTube Settings Check',
                'robots_txt' => 'Robots.txt Check',
                'page_speed' => 'Page Speed Check',
                'accessibility_report' => 'Accessibility Report',
                'srdb' => '404 Page Check',
                'external_links' => 'External & Internal Links Check'
            ];

            $check_title = isset($check_labels[$check_type]) ? $check_labels[$check_type] : ucfirst(str_replace('_', ' ', $check_type));

            $html .= '
                <div class="check-result ' . esc_attr($status_class) . '">
                    <div class="check-title">' . esc_html($check_title) . '</div>
            ';

            // Special handling for page speed with full PageSpeed Insights data
            if ($check_type === 'page_speed' && !empty($result['data']['has_full_data']) && !empty($result['data']['all_scores'])) {
                $html .= '<div class="scores-container">';
                
                // Display all scores with circular indicators
                foreach ($result['data']['all_scores'] as $category => $score) {
                    $label = ucfirst(str_replace('-', ' ', $category));
                    $html .= sitechal_generate_score_circle_html($score, $label);
                }
                
                $html .= '</div>';  
                
                // Display URL tested
                if (isset($result['data']['url'])) {
                    $html .= '<div style="margin-top: 10px; color: #666; word-break: break-all;"><small><strong>URL Tested:</strong> ' . esc_html($result['data']['url']) . '</small></div>';
                }
                
                // Show any issues
                if (!empty($result['issues'])) {
                    $html .= '<div class="issues" style="margin-top: 15px;"><strong>Issues Found:</strong><ul>';
                    foreach ($result['issues'] as $issue) {
                        $html .= '<li>' . esc_html($issue) . '</li>';
                    }
                    $html .= '</ul></div>';
                }
                
                // Show message if available
                if (isset($result['data']['message'])) {
                    $html .= '<div style="margin-top: 10px; color: #28a745;"><strong>' . esc_html($result['data']['message']) . '</strong></div>';
                }
            } else {
                // Standard display for other checks OR page speed fallback
                if (!empty($result['issues'])) {
                    $html .= '<div class="issues"><strong>Issues Found:</strong><ul>';
                    foreach ($result['issues'] as $issue) {
                        $html .= '<li>' . esc_html($issue) . '</li>';
                    }
                    $html .= '</ul></div>';
                }

                if (!empty($result['data'])) {
                    // For page speed fallback, skip certain fields
                    $data_to_display = $result['data'];
                    if ($check_type === 'page_speed') {
                        unset($data_to_display['has_full_data'], $data_to_display['all_scores'], 
                            $data_to_display['performance_score'], $data_to_display['accessibility_score'], 
                            $data_to_display['best_practices_score'], $data_to_display['seo_score'],
                            $data_to_display['fcp'], $data_to_display['lcp'], $data_to_display['tbt'], 
                            $data_to_display['cls']);
                    }
                    
                    if (!empty($data_to_display)) {
                        $html .= '<div class="data"><strong>Details:</strong><ul>';
                        foreach ($data_to_display as $key => $value) {
                            if (is_array($value)) {
                                $value = implode(', ', $value);
                            }
                            $html .= '<li><strong>' . esc_html(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' . esc_html($value) . '</li>';
                        }
                        $html .= '</ul></div>';
                    }
                }
            }

            $html .= '</div>';
        }

        $html .= '
                <div class="footer">
                    <p>This report was generated automatically by <a href="' . esc_url('https://www.wpsitechecker.com') . '">WP SiteChecker</a>.</p>
                    <p>For more detailed information, please visit your <a href="' . esc_url( admin_url() ) . '">WordPress admin panel</a>.</p>
                </div>
            </div>
        </body>
        </html>
        ';

        return $html;
    }
?>
