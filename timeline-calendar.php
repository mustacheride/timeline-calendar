<?php
/*
Plugin Name: Timeline Calendar
Plugin URI: https://github.com/mustacheride/timeline-calendar
Description: A comprehensive WordPress plugin for creating and managing timeline-based content with interactive calendar views, sparkline visualizations, and hierarchical navigation. Perfect for historical blogs, project timelines, or any content that needs chronological organization.
Version: 1.0.0
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Author: mustacheride
Author URI: https://github.com/mustacheride
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: timeline-calendar
Domain Path: /languages
Network: false

Timeline Calendar is a powerful WordPress plugin that transforms your site into a timeline management system. 
Create historical content organized by years, months, and days with interactive calendar views, 
sparkline visualizations, and smart navigation.
*/

if (!defined('ABSPATH')) exit;

// Flush rewrite rules on plugin activation
register_activation_hook(__FILE__, function() {
    // Flush rewrite rules to ensure our rules are registered
    flush_rewrite_rules();
});

// Flush rewrite rules on plugin deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Register custom post type
add_action('init', function() {
    register_post_type('timeline_article', [
        'public' => true,
        'label' => 'Timeline Articles',
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'has_archive' => true,
        'menu_icon' => 'dashicons-calendar-alt'
    ]);
});

// Add meta box for timeline year/month/day
add_action('add_meta_boxes', function() {
    add_meta_box(
        'timeline_date',
        'Timeline Date Settings',
        function($post) {
            $year = get_post_meta($post->ID, 'timeline_year', true);
            $month = get_post_meta($post->ID, 'timeline_month', true);
            $day = get_post_meta($post->ID, 'timeline_day', true);
            echo '<label>Year: <input type="number" name="timeline_year" id="timeline_year" value="' . esc_attr($year) . '" step="1" /></label> ';
            echo '<label>Month: <select name="timeline_month" id="timeline_month">';
            $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
            foreach ($months as $num=>$name) {
                echo '<option value="' . $num . '"' . selected($month, $num, false) . '>' . $name . '</option>';
            }
            echo '</select></label> ';
            echo '<label>Day: <select name="timeline_day" id="timeline_day">';
            for ($d=1; $d<=31; $d++) {
                echo '<option value="' . $d . '"' . selected($day, $d, false) . '>' . $d . '</option>';
            }
            echo '</select></label>';
            echo '<p><em>Changing these values will update the permalink below.</em></p>';
            ?>
            <script>
            jQuery(document).ready(function($) {
                function updatePermalink() {
                    var year = $('#timeline_year').val();
                    var month = $('#timeline_month').val();
                    var day = $('#timeline_day').val();
                    var postName = $('#post_name').val() || $('#title').val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                    
                    if (year && month && day && postName) {
                        var newPermalink = '<?php echo home_url('/'); ?>timeline/' + year + '/' + month + '/' + day + '/' + postName + '/';
                        
                        // Update the permalink display
                        var permalinkLink = $('.permalink a');
                        if (permalinkLink.length) {
                            permalinkLink.attr('href', newPermalink).text(newPermalink.replace('<?php echo home_url('/'); ?>', ''));
                        }
                        
                        // Update the sample permalink input
                        var samplePermalink = $('#sample-permalink');
                        if (samplePermalink.length) {
                            samplePermalink.val('timeline/' + year + '/' + month + '/' + day + '/' + postName + '/');
                        }
                    }
                }
                
                $('#timeline_year, #timeline_month, #timeline_day, #post_name, #title').on('change keyup', updatePermalink);
                
                // Initial update
                updatePermalink();
            });
            </script>
            <?php
        },
        'timeline_article'
    );
});

// Save meta box data for year/month/day
add_action('save_post', function($post_id) {
    if (isset($_POST['timeline_year'])) {
        update_post_meta($post_id, 'timeline_year', intval($_POST['timeline_year']));
    }
    if (isset($_POST['timeline_month'])) {
        update_post_meta($post_id, 'timeline_month', intval($_POST['timeline_month']));
    }
    if (isset($_POST['timeline_day'])) {
        update_post_meta($post_id, 'timeline_day', intval($_POST['timeline_day']));
    }
});

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', function() {
            wp_enqueue_style('timeline-calendar-style', plugins_url('assets/style.css', __FILE__), [], '1.0.2');
            wp_enqueue_script('timeline-calendar-js', plugins_url('assets/calendar.js', __FILE__), ['jquery'], '1.0.3', true);
    wp_enqueue_script('timeline-header-js', plugins_url('assets/timeline-header.js', __FILE__), ['jquery'], '1.0.1', true);
    wp_enqueue_script('timeline-year-view-js', plugins_url('assets/year-view.js', __FILE__), ['jquery'], '1.0.1', true);
            wp_enqueue_script(
            'timeline-sparkline-calendar',
            plugin_dir_url(__FILE__) . 'assets/sparkline-calendar.js',
            array('jquery'),
            '1.0.12',
            true
        );
});

// Calendar shortcode
add_shortcode('timeline_calendar', function() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/calendar.php';
    return ob_get_clean();
});



// Shortcode for archive.org-style sparkline calendar
add_shortcode('timeline_sparkline_calendar', function() {
    ob_start();
    echo "<div class='timeline-sparkline-calendar' id='timeline-sparkline-calendar'></div>";
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof TimelineSparklineCalendar !== 'undefined') {
            new TimelineSparklineCalendar('#timeline-sparkline-calendar', {
                startYear: -2,
                endYear: 4,
                yearsPerView: 7
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
});

// New improved sparkline shortcode
add_shortcode('timeline_sparkline', function($atts) {
    $atts = shortcode_atts([
        'start_year' => null,
        'end_year' => null,
        'years_per_view' => 7
    ], $atts);
    
    // Generate unique ID for this instance
    $unique_id = 'timeline-sparkline-' . uniqid();
    
    ob_start();
    ?>
    <div class="timeline-sparkline-calendar" id="<?php echo esc_attr($unique_id); ?>"></div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof TimelineSparklineCalendar !== 'undefined') {
            const config = {
                yearsPerView: <?php echo intval($atts['years_per_view']); ?>,
                showNavigation: true
            };
            
            <?php if ($atts['start_year'] !== null): ?>
            config.startYear = <?php echo intval($atts['start_year']); ?>;
            <?php endif; ?>
            
            <?php if ($atts['end_year'] !== null): ?>
            config.endYear = <?php echo intval($atts['end_year']); ?>;
            <?php endif; ?>
            
            new TimelineSparklineCalendar('#<?php echo esc_js($unique_id); ?>', config);
        } else {
            console.log('TimelineSparklineCalendar not available');
        }
    });
    </script>
    <?php
    return ob_get_clean();
});

// Shortcode to test timeline permalinks
add_shortcode('timeline_permalink_test', function() {
    if (!current_user_can('manage_options')) {
        return '<p>You need administrator privileges to view this debug information.</p>';
    }
    
    ob_start();
    debug_timeline_permalinks();
    return ob_get_clean();
});

// Shortcode for full year calendar
add_shortcode('timeline_year_calendar', function($atts) {
    $atts = shortcode_atts(['year' => 0], $atts);
    $year = $atts['year'] ?: (isset($_GET['timeline_year']) ? intval($_GET['timeline_year']) : 0);
    
    ob_start();
    echo "<div class='timeline-year-view'>";
    echo "<div class='timeline-year-navigation'>";
    echo "<div class='timeline-year-bar'>";
    echo "<button class='timeline-year-nav-btn' id='timeline-year-prev'>&lt;</button>";
    echo "<div class='timeline-year-scroll'>";
    echo "<div class='timeline-year-list' id='timeline-year-list'>";
    // Years will be populated by JavaScript
    echo "</div>";
    echo "</div>";
    echo "<button class='timeline-year-nav-btn' id='timeline-year-next'>&gt;</button>";
    echo "</div>";
    echo "</div>";
    echo "<div class='timeline-year-calendar' data-current-year='$year'>";
    $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    $i = 0;
    foreach ($months as $num => $name) {
        if ($i % 3 === 0) echo "<div class='timeline-year-row'>";
        echo "<div class='timeline-month-block'>";
        echo "<h4>$name</h4>";
        echo "<div class='timeline-calendar-root' data-year='$year' data-month='$num'></div>";
        echo "</div>";
        $i++;
        if ($i % 3 === 0) echo "</div>";
    }
    if ($i % 3 !== 0) echo "</div>";
    echo "</div>";
    echo "</div>";
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize year navigation
        const yearView = new TimelineYearView();
        
        // Initialize individual month calendars
        document.querySelectorAll('.timeline-calendar-root').forEach(function(root) {
            const year = parseInt(root.getAttribute('data-year'));
            const month = parseInt(root.getAttribute('data-month'));
            new TimelineCalendar(root, year, month);
        });
    });
    </script>
    <?php
    return ob_get_clean();
});

// AJAX: Fetch available years
add_action('wp_ajax_timeline_calendar_years', 'timeline_calendar_years_ajax');
add_action('wp_ajax_nopriv_timeline_calendar_years', 'timeline_calendar_years_ajax');
function timeline_calendar_years_ajax() {
    global $wpdb;
    $years = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = 'timeline_year' 
        ORDER BY CAST(meta_value AS SIGNED)
    ");
    wp_send_json($years);
}

// This Day in History shortcode
add_shortcode('timeline_this_day_in_history', function() {
    $current_month = date('n'); // Current month (1-12)
    $current_day = date('j');   // Current day (1-31)
    
    $args = [
        'post_type' => 'timeline_article',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => 'timeline_month',
                'value' => $current_month,
                'compare' => '='
            ],
            [
                'key' => 'timeline_day',
                'value' => $current_day,
                'compare' => '='
            ]
        ]
    ];
    
    $query = new WP_Query($args);
    $articles = [];
    
    foreach ($query->posts as $post) {
        $year = get_post_meta($post->ID, 'timeline_year', true);
        $articles[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'timeline_year' => $year,
            'permalink' => get_timeline_permalink($post->ID),
            'excerpt' => get_the_excerpt($post)
        ];
    }
    
    ob_start();
    ?>
    <div class="timeline-this-day-in-history">
        <h2 class="timeline-this-day-title">This Day in History</h2>
        <p class="timeline-this-day-subtitle"><?php echo date('F j'); ?> throughout the timeline</p>
        
        <?php if (empty($articles)): ?>
            <div class="timeline-this-day-empty">
                <p>No timeline articles found for <?php echo date('F j'); ?>.</p>
            </div>
        <?php else: ?>
            <div class="timeline-this-day-list">
                <?php foreach ($articles as $article): ?>
                    <div class="timeline-this-day-item">
                        <div class="timeline-this-day-year">Year <?php echo esc_html($article['timeline_year']); ?></div>
                        <div class="timeline-this-day-content">
                            <h3 class="timeline-this-day-article-title">
                                <a href="<?php echo esc_url($article['permalink']); ?>">
                                    <?php echo esc_html($article['title']); ?>
                                </a>
                            </h3>
                            <?php if (!empty($article['excerpt'])): ?>
                                <div class="timeline-this-day-excerpt">
                                    <?php echo wp_kses_post($article['excerpt']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

// AJAX: Fetch articles for a given year/month
add_action('wp_ajax_timeline_calendar_articles', 'timeline_calendar_articles_ajax');
add_action('wp_ajax_nopriv_timeline_calendar_articles', 'timeline_calendar_articles_ajax');
function timeline_calendar_articles_ajax() {
    $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
    $month = isset($_GET['month']) ? intval($_GET['month']) : 1;
    $args = [
        'post_type' => 'timeline_article',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'timeline_year',
                'value' => $year,
                'compare' => '='
            ],
            [
                'key' => 'timeline_month',
                'value' => $month,
                'compare' => '='
            ]
        ]
    ];
    $query = new WP_Query($args);
    $articles = [];
    foreach ($query->posts as $post) {
        $articles[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'timeline_year' => get_post_meta($post->ID, 'timeline_year', true),
            'timeline_month' => get_post_meta($post->ID, 'timeline_month', true),
            'timeline_day' => get_post_meta($post->ID, 'timeline_day', true),
            'permalink' => get_timeline_permalink($post->ID)
        ];
    }
    wp_send_json($articles);
} 

// Add rewrite rules for timeline URLs with higher priority
add_action('init', function() {
    // Special-case: /timeline/1/ (to avoid WP pagination redirect)
    add_rewrite_rule(
        '^timeline/1/?$',
        'index.php?timeline_year=1',
        'top'
    );
    
    // Article view: /timeline/year/month/day/article/
    add_rewrite_rule(
        '^timeline/(-?[0-9]+)/([0-9]+)/([0-9]+)/([^/]+)/?$',
        'index.php?timeline_year=$matches[1]&timeline_month=$matches[2]&timeline_day=$matches[3]&timeline_article=$matches[4]',
        'top'
    );
    
    // Day view: /timeline/year/month/day/
    add_rewrite_rule(
        '^timeline/(-?[0-9]+)/([0-9]+)/([0-9]+)/?$',
        'index.php?timeline_year=$matches[1]&timeline_month=$matches[2]&timeline_day=$matches[3]',
        'top'
    );
    
    // Month view: /timeline/year/month/
    add_rewrite_rule(
        '^timeline/(-?[0-9]+)/([0-9]+)/?$',
        'index.php?timeline_year=$matches[1]&timeline_month=$matches[2]',
        'top'
    );
    
    // Year view: /timeline/year/
    add_rewrite_rule(
        '^timeline/(-?[0-9]+)/?$',
        'index.php?timeline_year=$matches[1]',
        'top'
    );
    
    // Overview: /timeline/
    add_rewrite_rule(
        '^timeline/?$',
        'index.php?timeline_overview=1',
        'top'
    );
}, 1, 0);

// Add admin action to flush rewrite rules manually
add_action('admin_post_flush_timeline_rewrite_rules', function() {
    if (current_user_can('manage_options')) {
        flush_rewrite_rules();
        wp_redirect(admin_url('plugins.php?timeline_rewrite_flushed=1'));
        exit;
    }
});

// Add admin notice for rewrite rule flush
add_action('admin_notices', function() {
    if (isset($_GET['timeline_rewrite_flushed'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Timeline rewrite rules have been flushed successfully!</p></div>';
    }
});

// Add admin menu for timeline settings
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=timeline_article',
        'Timeline Settings',
        'Settings',
        'manage_options',
        'timeline-settings',
        function() {
            ?>
            <div class="wrap">
                <h1>Timeline Calendar Settings</h1>
                
                <div class="card">
                    <h2>Rewrite Rules</h2>
                    <p>If you're having issues with timeline URLs, try flushing the rewrite rules:</p>
                    <a href="<?php echo admin_url('admin-post.php?action=flush_timeline_rewrite_rules'); ?>" class="button button-primary">
                        Flush Rewrite Rules
                    </a>
                </div>
                
                <div class="card">
                    <h2>URL Structure</h2>
                    <p>The timeline plugin now supports the following URL structure:</p>
                    <ul>
                        <li><strong>Overview:</strong> <code>/timeline/</code></li>
                        <li><strong>Year:</strong> <code>/timeline/{year}/</code></li>
                        <li><strong>Month:</strong> <code>/timeline/{year}/{month}/</code></li>
                        <li><strong>Day:</strong> <code>/timeline/{year}/{month}/{day}/</code></li>
                        <li><strong>Article:</strong> <code>/timeline/{year}/{month}/{day}/{article-name}/</code></li>
                    </ul>
                </div>
                
                <div class="card">
                    <h2>Test URLs</h2>
                    <p>Here are some example URLs you can test:</p>
                    <ul>
                        <li><a href="<?php echo home_url('/timeline/'); ?>" target="_blank">Timeline Overview</a></li>
                        <li><a href="<?php echo home_url('/timeline/3/'); ?>" target="_blank">Year 3</a></li>
                        <li><a href="<?php echo home_url('/timeline/3/8/'); ?>" target="_blank">August, Year 3</a></li>
                        <li><a href="<?php echo home_url('/timeline/3/8/15/'); ?>" target="_blank">August 15, Year 3</a></li>
                    </ul>
                </div>
                
                <div class="card">
                    <h2>Debug Information</h2>
                    <p>Debug information for timeline permalinks:</p>
                    <?php debug_timeline_permalinks(); ?>
                </div>
            </div>
            <?php
        }
    );
});

// Add query vars
add_filter('query_vars', function($vars) {
    $vars[] = 'timeline_year';
    $vars[] = 'timeline_month';
    $vars[] = 'timeline_day';
    $vars[] = 'timeline_article';
    $vars[] = 'timeline_overview';
    return $vars;
});

// Handle timeline requests early in the process
add_action('parse_request', function($wp) {
    // Only process if this is actually a timeline URL
    if (strpos($_SERVER['REQUEST_URI'], '/timeline/') !== 0) {
        return;
    }
    
    $timeline_overview = $wp->query_vars['timeline_overview'] ?? null;
    $timeline_year = array_key_exists('timeline_year', $wp->query_vars) ? $wp->query_vars['timeline_year'] : null;
    $timeline_month = array_key_exists('timeline_month', $wp->query_vars) ? $wp->query_vars['timeline_month'] : null;
    $timeline_day = array_key_exists('timeline_day', $wp->query_vars) ? $wp->query_vars['timeline_day'] : null;
    $timeline_article = array_key_exists('timeline_article', $wp->query_vars) ? $wp->query_vars['timeline_article'] : null;
    
    // Check if this is a timeline request
    if ($timeline_overview !== null || $timeline_year !== null || $timeline_month !== null || $timeline_day !== null || $timeline_article !== null) {
        // Set a flag to indicate this is a timeline request
        $wp->query_vars['is_timeline_request'] = true;
    }
});

// Handle timeline requests
add_action('template_redirect', function() {
    global $wp_query;
    
    // Only process if this is actually a timeline URL
    if (strpos($_SERVER['REQUEST_URI'], '/timeline/') !== 0) {
        return;
    }
    
    // Check if this is a timeline request using the flag set in parse_request
    $is_timeline_request = $wp_query->get('is_timeline_request');
    
    // Get query vars directly from the global query
    $timeline_overview = $wp_query->get('timeline_overview');
    $timeline_year = $wp_query->get('timeline_year');
    $timeline_month = $wp_query->get('timeline_month');
    $timeline_day = $wp_query->get('timeline_day');
    $timeline_article = $wp_query->get('timeline_article');
    
    // Only proceed if we have actual timeline query vars
    if (!$is_timeline_request && $timeline_overview === null && $timeline_year === null && $timeline_month === null && $timeline_day === null && $timeline_article === null) {
        return;
    }
    
    // Set up WordPress properly
    $wp_query->is_page = true;
    $wp_query->is_single = false;
    $wp_query->is_home = false;
    $wp_query->is_archive = false;
    $wp_query->is_search = false;
    $wp_query->is_404 = false;
    
    // Set the page title
    if ($timeline_overview !== null) {
        $wp_query->post_title = 'Timeline Overview';
    } elseif ($timeline_article !== null) {
        $wp_query->post_title = $timeline_article;
    } elseif ($timeline_day !== null) {
        $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
        $wp_query->post_title = $month_names[$timeline_month] . ' ' . $timeline_day . ', Year ' . $timeline_year;
    } elseif ($timeline_month !== null) {
        $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
        $wp_query->post_title = $month_names[$timeline_month] . ', Year ' . $timeline_year;
    } elseif ($timeline_year !== null) {
        $wp_query->post_title = 'Year ' . $timeline_year;
    }
    
    // Load our timeline template
    include plugin_dir_path(__FILE__) . 'templates/timeline-template.php';
    exit;
});

// Alternative approach: Handle timeline URLs directly
// This approach is disabled to avoid conflicts with WordPress rewrite rules
/*
add_action('init', function() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $path = parse_url($request_uri, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // Check if this is a timeline URL
    if (strpos($path, 'timeline') === 0) {
        $path_parts = explode('/', $path);
        
        if (count($path_parts) >= 1 && $path_parts[0] === 'timeline') {
            $year = isset($path_parts[1]) ? $path_parts[1] : null;
            $month = isset($path_parts[2]) ? $path_parts[2] : null;
            $day = isset($path_parts[3]) ? $path_parts[3] : null;
            $article = isset($path_parts[4]) ? $path_parts[4] : null;
            
            // Set query vars
            if ($year) set_query_var('timeline_year', $year);
            if ($month) set_query_var('timeline_month', $month);
            if ($day) set_query_var('timeline_day', $day);
            if ($article) set_query_var('timeline_article', $article);
            if (!$year && !$month && !$day && !$article) set_query_var('timeline_overview', 1);
            
            // Set up WordPress query
            global $wp_query;
            $wp_query->is_page = true;
            $wp_query->is_single = false;
            $wp_query->is_home = false;
            $wp_query->is_archive = false;
            $wp_query->is_search = false;
            
            // Set the page title
            if ($article) {
                $wp_query->post_title = $article;
            } elseif ($day) {
                $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                $wp_query->post_title = $month_names[$month] . ' ' . $day . ', Year ' . $year;
            } elseif ($month) {
                $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                $wp_query->post_title = $month_names[$month] . ', Year ' . $year;
            } elseif ($year) {
                $wp_query->post_title = 'Year ' . $year;
            } else {
                $wp_query->post_title = 'Timeline Overview';
            }
            
            // Load our timeline template
            include plugin_dir_path(__FILE__) . 'templates/timeline-template.php';
            exit;
        }
    }
});
*/

// AJAX: Get sparkline data for horizontal calendar
add_action('wp_ajax_timeline_sparkline_data', 'timeline_sparkline_data');
add_action('wp_ajax_nopriv_timeline_sparkline_data', 'timeline_sparkline_data');

function timeline_sparkline_data() {
    $start_year = isset($_GET['start_year']) ? intval($_GET['start_year']) : 1;
    $end_year = isset($_GET['end_year']) ? intval($_GET['end_year']) : 8;
    
    global $wpdb;
    
    // Get article counts per month for each year
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            pm_year.meta_value as year,
            pm_month.meta_value as month,
            COUNT(*) as count
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm_year ON p.ID = pm_year.post_id AND pm_year.meta_key = 'timeline_year'
        JOIN {$wpdb->postmeta} pm_month ON p.ID = pm_month.post_id AND pm_month.meta_key = 'timeline_month'
        WHERE p.post_type = 'timeline_article' 
        AND p.post_status = 'publish'
        AND CAST(pm_year.meta_value AS SIGNED) BETWEEN %d AND %d
        GROUP BY pm_year.meta_value, pm_month.meta_value
        ORDER BY pm_year.meta_value ASC, pm_month.meta_value ASC
    ", $start_year, $end_year));
    
    $sparkline_data = [];
    
    // Initialize all years with 12 months of zeros
    for ($year = $start_year; $year <= $end_year; $year++) {
        $sparkline_data[$year] = array_fill(1, 12, 0);
    }
    
    // Fill in actual counts
    foreach ($results as $row) {
        $year = intval($row->year);
        $month = intval($row->month);
        if (isset($sparkline_data[$year])) {
            $sparkline_data[$year][$month] = intval($row->count);
        }
    }
    
    wp_send_json_success($sparkline_data);
} 

// Custom function to generate timeline permalinks
function get_timeline_permalink($post_id) {
    $year = get_post_meta($post_id, 'timeline_year', true);
    $month = get_post_meta($post_id, 'timeline_month', true);
    $day = get_post_meta($post_id, 'timeline_day', true);
    $post = get_post($post_id);
    
    if ($year && $month && $day && $post) {
        return home_url("/timeline/{$year}/{$month}/{$day}/{$post->post_name}/");
    }
    
    // Fallback to default permalink if timeline data is missing
    return get_permalink($post_id);
}

// Filter to show correct timeline permalinks in admin
add_filter('post_link', function($permalink, $post) {
    if ($post->post_type === 'timeline_article') {
        return get_timeline_permalink($post->ID);
    }
    return $permalink;
}, 10, 2);

// Filter to show correct timeline permalinks in admin for get_permalink()
add_filter('get_permalink', function($permalink, $post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'timeline_article') {
        return get_timeline_permalink($post_id);
    }
    return $permalink;
}, 10, 2);

// Filter to update the permalink display in the admin edit screen
add_filter('admin_post_link', function($permalink, $post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'timeline_article') {
        return get_timeline_permalink($post_id);
    }
    return $permalink;
}, 10, 2);

// Filter to update the permalink preview in the admin edit screen
add_filter('sample_permalink_html', function($html, $post_id, $title, $name, $post) {
    if ($post && $post->post_type === 'timeline_article') {
        $timeline_permalink = get_timeline_permalink($post_id);
        $home_url = home_url('/');
        $relative_permalink = str_replace($home_url, '', $timeline_permalink);
        
        // Replace the permalink in the HTML
        $html = preg_replace(
            '/<a[^>]*href="[^"]*"[^>]*>([^<]*)<\/a>/',
            '<a href="' . esc_url($timeline_permalink) . '">' . esc_html($relative_permalink) . '</a>',
            $html
        );
        
        // Also update the input field if it exists
        $html = preg_replace(
            '/<input[^>]*id="sample-permalink"[^>]*value="[^"]*"/',
            '<input id="sample-permalink" value="' . esc_attr($relative_permalink) . '"',
            $html
        );
    }
    return $html;
}, 10, 5);

// Debug function to test permalink generation
function debug_timeline_permalinks() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $args = [
        'post_type' => 'timeline_article',
        'posts_per_page' => 5,
        'post_status' => 'publish'
    ];
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        echo '<div style="background: #f0f0f0; padding: 1em; margin: 1em 0; border: 1px solid #ccc;">';
        echo '<h3>Timeline Permalink Debug Info</h3>';
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $year = get_post_meta($post_id, 'timeline_year', true);
            $month = get_post_meta($post_id, 'timeline_month', true);
            $day = get_post_meta($post_id, 'timeline_day', true);
            $old_permalink = get_permalink($post_id);
            $new_permalink = get_timeline_permalink($post_id);
            
            echo '<div style="margin-bottom: 1em; padding: 0.5em; background: white; border: 1px solid #ddd;">';
            echo '<strong>' . get_the_title() . '</strong><br>';
            echo 'Timeline Date: Year ' . $year . ', Month ' . $month . ', Day ' . $day . '<br>';
            echo 'Old Permalink: <a href="' . $old_permalink . '" target="_blank">' . $old_permalink . '</a><br>';
            echo 'New Permalink: <a href="' . $new_permalink . '" target="_blank">' . $new_permalink . '</a><br>';
            echo '</div>';
        }
        
        echo '</div>';
        wp_reset_postdata();
    }
} 

// Disable canonical redirects for /timeline/ URLs to prevent unwanted redirects (e.g., /timeline/1/ to /timeline/)
add_filter('redirect_canonical', function($redirect_url, $requested_url) {
    // Only disable canonical redirects for actual timeline URLs
    if (strpos($_SERVER['REQUEST_URI'], '/timeline/') === 0) {
        return false;
    }
    return $redirect_url;
}, 10, 2); 