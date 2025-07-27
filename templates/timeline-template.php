<?php
/**
 * Timeline Template
 * Handles all timeline URL structures:
 * /timeline/ - Overview with sparkline calendar
 * /timeline/{year}/ - Year overview (12 months)
 * /timeline/{year}/{month}/ - Month overview (all days in that month)
 * /timeline/{year}/{month}/{day}/ - Day overview (all articles for that day)
 * /timeline/{year}/{month}/{day}/{article}/ - Article view
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// DEBUG: Output timeline context at the top of the template
$debug_vars = [
    'timeline_year' => isset($timeline_year) ? $timeline_year : 'unset',
    'timeline_month' => isset($timeline_month) ? $timeline_month : 'unset',
    'timeline_day' => isset($timeline_day) ? $timeline_day : 'unset',
    'timeline_article' => isset($timeline_article) ? $timeline_article : 'unset',
    'timeline_overview' => isset($timeline_overview) ? $timeline_overview : 'unset',
];
echo '<!-- Timeline template loaded: ' . htmlspecialchars(json_encode($debug_vars)) . ' -->';

// Function to generate breadcrumbs
function get_timeline_breadcrumbs($year = null, $month = null, $day = null, $article = null) {
    $breadcrumbs = [];
    $breadcrumbs[] = '<a href="' . home_url('/timeline/') . '">Timeline</a>';
    
    if ($year !== null) {
        $breadcrumbs[] = '<a href="' . home_url('/timeline/' . $year . '/') . '">Year ' . $year . '</a>';
        
        if ($month !== null) {
            $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
            $breadcrumbs[] = '<a href="' . home_url('/timeline/' . $year . '/' . $month . '/') . '">' . $month_names[$month] . '</a>';
            
            if ($day !== null) {
                $breadcrumbs[] = '<a href="' . home_url('/timeline/' . $year . '/' . $month . '/' . $day . '/') . '">' . $day . '</a>';
                
                if ($article !== null) {
                    $breadcrumbs[] = '<span>' . $article . '</span>';
                }
            }
        }
    }
    
    return '<div class="timeline-breadcrumbs">' . implode(' &raquo; ', $breadcrumbs) . '</div>';
}

// Get URL parameters
$timeline_year = get_query_var('timeline_year', null);
$timeline_month = get_query_var('timeline_month', null);
$timeline_day = get_query_var('timeline_day', null);
$timeline_article = get_query_var('timeline_article', null);
$timeline_overview = get_query_var('timeline_overview', null);

// Debug output
if (isset($_GET['debug'])) {
    echo "<!-- Debug Info:\n";
    echo "timeline_year: " . ($timeline_year !== null ? $timeline_year : 'null') . "\n";
    echo "timeline_month: " . ($timeline_month !== null ? $timeline_month : 'null') . "\n";
    echo "timeline_day: " . ($timeline_day !== null ? $timeline_day : 'null') . "\n";
    echo "timeline_article: " . ($timeline_article !== null ? $timeline_article : 'null') . "\n";
    echo "timeline_overview: " . ($timeline_overview !== null ? $timeline_overview : 'null') . "\n";
    echo "-->\n";
}

// Always show debug info for now
echo "<!-- Debug Info:\n";
echo "timeline_year: " . ($timeline_year !== null ? $timeline_year : 'null') . "\n";
echo "timeline_month: " . ($timeline_month !== null ? $timeline_month : 'null') . "\n";
echo "timeline_day: " . ($timeline_day !== null ? $timeline_day : 'null') . "\n";
echo "timeline_article: " . ($timeline_article !== null ? $timeline_article : 'null') . "\n";
echo "timeline_overview: " . ($timeline_overview !== null ? $timeline_overview : 'null') . "\n";
echo "-->";

// Assets are now enqueued conditionally in the main plugin file
// No need to call get_header() as this template is now integrated with the theme system
?>

<div class="timeline-container wp-block-group">
    <div class="timeline-content">
        <?php if ($timeline_overview !== null): ?>
            <!-- Timeline Overview -->
            <div class="timeline-overview">
                <div class="timeline-sparkline-calendar" id="timeline-sparkline-calendar"></div>
                <div class="timeline-overview-stats">
                    <h2>Timeline Statistics</h2>
                    <?php
                    global $wpdb;
                    $total_articles = $wpdb->get_var("
                        SELECT COUNT(*) FROM {$wpdb->posts} 
                        WHERE post_type = 'timeline_article' AND post_status = 'publish'
                    ");
                    $year_range = $wpdb->get_row("
                        SELECT 
                            MIN(CAST(pm.meta_value AS SIGNED)) as min_year,
                            MAX(CAST(pm.meta_value AS SIGNED)) as max_year
                        FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                        WHERE p.post_type = 'timeline_article' 
                        AND p.post_status = 'publish'
                        AND pm.meta_key = 'timeline_year'
                    ");
                    ?>
                    <p>Total Articles: <?php echo esc_html($total_articles); ?></p>
                    <?php if ($year_range): ?>
                        <p>Year Range: <?php echo esc_html($year_range->min_year); ?> - <?php echo esc_html($year_range->max_year); ?></p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($timeline_article !== null): ?>
            <!-- Individual Article View -->
            <?php
            $args = [
                'post_type' => 'timeline_article',
                'name' => $timeline_article,
                'meta_query' => [
                    [
                        'key' => 'timeline_year',
                        'value' => $timeline_year,
                        'compare' => '='
                    ],
                    [
                        'key' => 'timeline_month',
                        'value' => $timeline_month,
                        'compare' => '='
                    ],
                    [
                        'key' => 'timeline_day',
                        'value' => $timeline_day,
                        'compare' => '='
                    ]
                ]
            ];
            $query = new WP_Query($args);
            
            if ($query->have_posts()):
                while ($query->have_posts()): $query->the_post();
            ?>
                <div class="timeline-article-overview">
                    <!-- Sparkline Calendar at the top -->
                    <div class="timeline-sparkline-calendar" id="timeline-article-sparkline-<?php echo $timeline_year; ?>-<?php echo $timeline_month; ?>-<?php echo $timeline_day; ?>-<?php echo $timeline_article; ?>"></div>
                    
                    <h2><?php the_title(); ?></h2>
                                            <?php echo get_timeline_breadcrumbs($timeline_year !== null ? intval($timeline_year) : null, $timeline_month !== null ? intval($timeline_month) : null, $timeline_day !== null ? intval($timeline_day) : null, $timeline_article); ?>
                    
                    <article class="timeline-article">
                        <header class="timeline-article-header">
                            <div class="timeline-article-meta">
                                <span class="timeline-date">
                                    <?php 
                                    $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                                    echo esc_html($month_names[$timeline_month] . ' ' . $timeline_day . ', Year ' . $timeline_year); 
                                    ?>
                                </span>
                            </div>
                        </header>
                        <div class="timeline-article-content">
                            <?php the_content(); ?>
                        </div>
                    </article>
                    
                    <!-- Article Navigation -->
                    <div class="timeline-article-navigation">
                        <?php
                        // Get all articles sorted by year, month, day, and title
                        $all_articles_args = [
                            'post_type' => 'timeline_article',
                            'posts_per_page' => -1,
                            'orderby' => 'meta_value_num title',
                            'order' => 'ASC',
                            'meta_query' => [
                                'relation' => 'AND',
                                [
                                    'key' => 'timeline_year',
                                    'compare' => 'EXISTS'
                                ],
                                [
                                    'key' => 'timeline_month',
                                    'compare' => 'EXISTS'
                                ],
                                [
                                    'key' => 'timeline_day',
                                    'compare' => 'EXISTS'
                                ]
                            ],
                            'meta_key' => 'timeline_year'
                        ];
                        
                        $all_articles_query = new WP_Query($all_articles_args);
                        $articles = [];
                        
                        if ($all_articles_query->have_posts()) {
                            while ($all_articles_query->have_posts()) {
                                $all_articles_query->the_post();
                                $year = get_post_meta(get_the_ID(), 'timeline_year', true);
                                $month = get_post_meta(get_the_ID(), 'timeline_month', true);
                                $day = get_post_meta(get_the_ID(), 'timeline_day', true);
                                
                                $articles[] = [
                                    'id' => get_the_ID(),
                                    'title' => get_the_title(),
                                    'slug' => get_post_field('post_name'),
                                    'year' => intval($year),
                                    'month' => intval($month),
                                    'day' => intval($day)
                                ];
                            }
                        }
                        wp_reset_postdata();
                        
                        // Sort articles by year, month, day, then title
                        usort($articles, function($a, $b) {
                            if ($a['year'] !== $b['year']) {
                                return $a['year'] - $b['year'];
                            }
                            if ($a['month'] !== $b['month']) {
                                return $a['month'] - $b['month'];
                            }
                            if ($a['day'] !== $b['day']) {
                                return $a['day'] - $b['day'];
                            }
                            return strcasecmp($a['title'], $b['title']);
                        });
                        
                        // Find current article index
                        $current_index = -1;
                        foreach ($articles as $index => $article) {
                            if ($article['slug'] === $timeline_article) {
                                $current_index = $index;
                                break;
                            }
                        }
                        
                        // Get previous and next articles
                        $prev_article = ($current_index > 0) ? $articles[$current_index - 1] : null;
                        $next_article = ($current_index < count($articles) - 1) ? $articles[$current_index + 1] : null;
                        ?>
                        
                        <?php if ($prev_article): ?>
                            <a href="<?php echo get_timeline_permalink($prev_article['id']); ?>" class="timeline-nav-prev">
                                &larr; <?php echo esc_html($prev_article['title']); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($next_article): ?>
                            <a href="<?php echo get_timeline_permalink($next_article['id']); ?>" class="timeline-nav-next">
                                <?php echo esc_html($next_article['title']); ?> &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Timeline Statistics -->
                    <div class="timeline-article-stats">
                        <h3>Timeline Statistics</h3>
                        <?php
                        global $wpdb;
                        $total_articles = $wpdb->get_var("
                            SELECT COUNT(*) FROM {$wpdb->posts} 
                            WHERE post_type = 'timeline_article' AND post_status = 'publish'
                        ");
                        $current_article_position = $current_index + 1;
                        
                        // Get the current post data for author and dates
                        $current_post = get_post();
                        $author_id = $current_post->post_author;
                        $author_name = get_the_author_meta('display_name', $author_id);
                        $last_modified = get_the_modified_date('F j, Y', $current_post->ID);
                        $last_modified_time = get_the_modified_time('g:i a', $current_post->ID);
                        ?>
                        <p>Article <?php echo esc_html($current_article_position); ?> of <?php echo esc_html($total_articles); ?> total articles</p>
                        <p>Last edited on <?php echo esc_html($last_modified); ?> at <?php echo esc_html($last_modified_time); ?></p>
                        <p>Author: <?php echo esc_html($author_name); ?></p>
                    </div>
                </div>
                
                <script>
                function initializeArticleSparklineCalendar() {
                    console.log('Timeline template - initializeArticleSparklineCalendar called');
                    console.log('Timeline template - TimelineSparklineCalendar available:', typeof TimelineSparklineCalendar !== 'undefined');
                    
                    // Initialize sparkline calendar with 7-year view centered on current year
                    if (typeof TimelineSparklineCalendar !== 'undefined') {
                        const currentYear = <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>;
                        console.log('Timeline template - Current year:', currentYear);
                        
                        // Ensure we always show 7 years, with current year centered
                        let startYear, endYear;
                        if (currentYear === 0) {
                            // For Year 0, show years -3 to 3
                            startYear = -3;
                            endYear = 3;
                        } else if (currentYear < 0) {
                            // For negative years, center the view with 3 years before and 3 years after
                            startYear = currentYear - 3;
                            endYear = currentYear + 3;
                        } else {
                            // For positive years, center the view with 3 years before and 3 years after
                            startYear = currentYear - 3;
                            endYear = currentYear + 3;
                        }
                        
                        console.log('Timeline template - Article view debug - currentYear:', currentYear, 'startYear:', startYear, 'endYear:', endYear);
                        
                        new TimelineSparklineCalendar('#timeline-article-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>-<?php echo $timeline_month !== null ? intval($timeline_month) : 0; ?>-<?php echo $timeline_day !== null ? intval($timeline_day) : 0; ?>-<?php echo $timeline_article; ?>', {
                            startYear: startYear,
                            endYear: endYear,
                            showNavigation: true,
                            yearsPerView: 7
                        });
                    } else {
                        console.log('Timeline template - TimelineSparklineCalendar not available, retrying in 100ms');
                        setTimeout(initializeArticleSparklineCalendar, 100);
                    }
                }
                
                // Try to initialize when DOM is ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initializeArticleSparklineCalendar);
                } else {
                    // DOM is already ready, try to initialize immediately
                    initializeArticleSparklineCalendar();
                }
                </script>
            <?php
                endwhile;
            else:
            ?>
                <div class="timeline-not-found">
                    <h2>Article Not Found</h2>
                    <p>The requested article could not be found.</p>
                </div>
            <?php
            endif;
            wp_reset_postdata();
            ?>

        <?php elseif ($timeline_day !== null): ?>
            <!-- Day Overview - Sparkline calendar + Alphabetized list of articles for that day -->
            <div class="timeline-day-overview">
                <!-- Sparkline Calendar at the top -->
                <div class="timeline-sparkline-calendar" id="timeline-day-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>-<?php echo $timeline_month !== null ? intval($timeline_month) : 0; ?>-<?php echo $timeline_day !== null ? intval($timeline_day) : 0; ?>"></div>
                
                <h2><?php 
                    $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                    echo esc_html($month_names[intval($timeline_month)] . ' ' . intval($timeline_day) . ', Year ' . intval($timeline_year)); 
                ?></h2>
                <?php echo get_timeline_breadcrumbs($timeline_year !== null ? intval($timeline_year) : null, $timeline_month !== null ? intval($timeline_month) : null, $timeline_day !== null ? intval($timeline_day) : null); ?>
                
                <?php
                $args = [
                    'post_type' => 'timeline_article',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'meta_query' => [
                        [
                            'key' => 'timeline_year',
                            'value' => $timeline_year,
                            'compare' => '='
                        ],
                        [
                            'key' => 'timeline_month',
                            'value' => $timeline_month,
                            'compare' => '='
                        ],
                        [
                            'key' => 'timeline_day',
                            'value' => $timeline_day,
                            'compare' => '='
                        ]
                    ]
                ];
                $query = new WP_Query($args);
                
                if ($query->have_posts()):
                ?>
                    <div class="timeline-day-articles">
                        <div class="timeline-articles-list">
                            <?php while ($query->have_posts()): $query->the_post(); ?>
                                <article class="timeline-article-preview">
                                    <h3><a href="<?php echo get_timeline_permalink(get_the_ID()); ?>"><?php the_title(); ?></a></h3>
                                    <?php if (has_excerpt()): ?>
                                        <div class="timeline-article-excerpt"><?php the_excerpt(); ?></div>
                                    <?php endif; ?>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="timeline-not-found">
                        <h3>No Articles Found</h3>
                        <p>No articles found for <?php 
                            $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                            echo esc_html($month_names[intval($timeline_month)] . ' ' . intval($timeline_day) . ', Year ' . intval($timeline_year)); 
                        ?>.</p>
                    </div>
                <?php
                endif;
                wp_reset_postdata();
                ?>
                
                <!-- Day Navigation -->
                <div class="timeline-day-navigation">
                    <?php
                    $prev_day = intval($timeline_day) - 1;
                    $prev_month = intval($timeline_month);
                    $prev_year = intval($timeline_year);
                    
                    // Handle month/year rollover for previous day
                    if ($prev_day < 1) {
                        $prev_month = intval($timeline_month) - 1;
                        if ($prev_month < 1) {
                            $prev_month = 12;
                            $prev_year = intval($timeline_year) - 1;
                        }
                        // Get the last day of the previous month
                        $prev_day = date('j', mktime(0, 0, 0, $prev_month + 1, 0, $prev_year));
                    }
                    
                    $next_day = intval($timeline_day) + 1;
                    $next_month = intval($timeline_month);
                    $next_year = intval($timeline_year);
                    
                    // Handle month/year rollover for next day
                    $days_in_current_month = date('j', mktime(0, 0, 0, intval($timeline_month) + 1, 0, intval($timeline_year)));
                    if ($next_day > $days_in_current_month) {
                        $next_day = 1;
                        $next_month = intval($timeline_month) + 1;
                        if ($next_month > 12) {
                            $next_month = 1;
                            $next_year = intval($timeline_year) + 1;
                        }
                    }
                    ?>
                    <a href="<?php echo home_url('/timeline/' . $prev_year . '/' . $prev_month . '/' . $prev_day . '/'); ?>" class="timeline-nav-prev">
                        &larr; <?php echo $month_names[$prev_month]; ?> <?php echo $prev_day; ?>, Year <?php echo $prev_year; ?>
                    </a>
                    <a href="<?php echo home_url('/timeline/' . $next_year . '/' . $next_month . '/' . $next_day . '/'); ?>" class="timeline-nav-next">
                        <?php echo $month_names[$next_month]; ?> <?php echo $next_day; ?>, Year <?php echo $next_year; ?> &rarr;
                    </a>
                </div>
                
                <!-- Timeline Statistics -->
                <div class="timeline-day-stats">
                    <h3>Timeline Statistics</h3>
                    <?php
                    global $wpdb;
                    $day_articles = $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(*) FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
                        JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
                        JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id 
                        WHERE p.post_type = 'timeline_article' 
                        AND p.post_status = 'publish'
                        AND pm1.meta_key = 'timeline_year'
                        AND pm1.meta_value = %d
                        AND pm2.meta_key = 'timeline_month'
                        AND pm2.meta_value = %d
                        AND pm3.meta_key = 'timeline_day'
                        AND pm3.meta_value = %d
                    ", $timeline_year, $timeline_month, $timeline_day));
                    ?>
                    <p>Articles on <?php echo esc_html($month_names[intval($timeline_month)]); ?> <?php echo esc_html(intval($timeline_day)); ?>, Year <?php echo esc_html(intval($timeline_year)); ?>: <?php echo esc_html($day_articles); ?></p>
                </div>
            </div>
            
            <script>
            function initializeDaySparklineCalendar() {
                console.log('Timeline template - initializeDaySparklineCalendar called');
                console.log('Timeline template - TimelineSparklineCalendar available:', typeof TimelineSparklineCalendar !== 'undefined');
                
                // Initialize sparkline calendar with 7-year view centered on current year
                if (typeof TimelineSparklineCalendar !== 'undefined') {
                    const currentYear = <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>;
                    console.log('Timeline template - Current year:', currentYear);
                    
                    // Ensure we always show 7 years, with current year centered
                    let startYear, endYear;
                    if (currentYear === 0) {
                        // For Year 0, show years -3 to 3
                        startYear = -3;
                        endYear = 3;
                    } else if (currentYear < 0) {
                        // For negative years, center the view with 3 years before and 3 years after
                        startYear = currentYear - 3;
                        endYear = currentYear + 3;
                    } else {
                        // For positive years, center the view with 3 years before and 3 years after
                        startYear = currentYear - 3;
                        endYear = currentYear + 3;
                    }
                    
                    console.log('Timeline template - Day view debug - currentYear:', currentYear, 'startYear:', startYear, 'endYear:', endYear);
                    
                    new TimelineSparklineCalendar('#timeline-day-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>-<?php echo $timeline_month !== null ? intval($timeline_month) : 0; ?>-<?php echo $timeline_day !== null ? intval($timeline_day) : 0; ?>', {
                        startYear: startYear,
                        endYear: endYear,
                        showNavigation: true,
                        yearsPerView: 7
                    });
                } else {
                    console.log('Timeline template - TimelineSparklineCalendar not available, retrying in 100ms');
                    setTimeout(initializeDaySparklineCalendar, 100);
                }
            }
            
            // Try to initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeDaySparklineCalendar);
            } else {
                // DOM is already ready, try to initialize immediately
                initializeDaySparklineCalendar();
            }
            </script>

        <?php elseif ($timeline_month !== null): ?>
            <!-- Month Overview - Sparkline calendar + Single month calendar view -->
            <div class="timeline-month-overview">
                <!-- Sparkline Calendar at the top -->
                <div class="timeline-sparkline-calendar" id="timeline-month-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>-<?php echo $timeline_month !== null ? intval($timeline_month) : 0; ?>"></div>
                
                <h2><?php 
                    $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                    echo esc_html($month_names[intval($timeline_month)] . ', Year ' . intval($timeline_year)); 
                ?></h2>
                <?php echo get_timeline_breadcrumbs($timeline_year !== null ? intval($timeline_year) : null, $timeline_month !== null ? intval($timeline_month) : null); ?>
                
                <!-- Single Month Calendar -->
                <div class="timeline-single-month-calendar">
                    <div class="timeline-calendar-root" data-year="<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>" data-month="<?php echo $timeline_month !== null ? intval($timeline_month) : 0; ?>"></div>
                </div>
                
                <!-- Month Navigation -->
                <div class="timeline-month-navigation">
                    <?php
                    $prev_month = intval($timeline_month) - 1;
                    $prev_year = intval($timeline_year);
                    if ($prev_month < 1) {
                        $prev_month = 12;
                        $prev_year = intval($timeline_year) - 1;
                    }
                    
                    $next_month = intval($timeline_month) + 1;
                    $next_year = intval($timeline_year);
                    if ($next_month > 12) {
                        $next_month = 1;
                        $next_year = intval($timeline_year) + 1;
                    }
                    ?>
                    <a href="<?php echo home_url('/timeline/' . $prev_year . '/' . $prev_month . '/'); ?>" class="timeline-nav-prev">
                        &larr; <?php echo $month_names[$prev_month]; ?>, Year <?php echo $prev_year; ?>
                    </a>
                    <a href="<?php echo home_url('/timeline/' . $next_year . '/' . $next_month . '/'); ?>" class="timeline-nav-next">
                        <?php echo $month_names[$next_month]; ?>, Year <?php echo $next_year; ?> &rarr;
                    </a>
                </div>
                
                <!-- Timeline Statistics -->
                <div class="timeline-month-stats">
                    <h3>Timeline Statistics</h3>
                    <?php
                    global $wpdb;
                    $month_articles = $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(*) FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
                        JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
                        WHERE p.post_type = 'timeline_article' 
                        AND p.post_status = 'publish'
                        AND pm1.meta_key = 'timeline_year'
                        AND pm1.meta_value = %d
                        AND pm2.meta_key = 'timeline_month'
                        AND pm2.meta_value = %d
                    ", $timeline_year, $timeline_month));
                    
                    $month_days_with_articles = $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(DISTINCT pm3.meta_value) FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
                        JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
                        JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id 
                        WHERE p.post_type = 'timeline_article' 
                        AND p.post_status = 'publish'
                        AND pm1.meta_key = 'timeline_year'
                        AND pm1.meta_value = %d
                        AND pm2.meta_key = 'timeline_month'
                        AND pm2.meta_value = %d
                        AND pm3.meta_key = 'timeline_day'
                    ", $timeline_year, $timeline_month));
                    ?>
                    <p>Articles in <?php echo esc_html($month_names[intval($timeline_month)]); ?>, Year <?php echo esc_html(intval($timeline_year)); ?>: <?php echo esc_html($month_articles); ?></p>
                    <p>Days with Articles: <?php echo esc_html($month_days_with_articles); ?></p>
                </div>
            </div>
            
            <script>
            function initializeMonthSparklineCalendar() {
                console.log('Timeline template - initializeMonthSparklineCalendar called');
                console.log('Timeline template - TimelineSparklineCalendar available:', typeof TimelineSparklineCalendar !== 'undefined');
                
                // Initialize sparkline calendar with 8-year view centered on current year
                if (typeof TimelineSparklineCalendar !== 'undefined') {
                    const currentYear = <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>;
                    console.log('Timeline template - Current year:', currentYear);
                    
                    // Ensure we always show 7 years, with current year centered
                    let startYear, endYear;
                    if (currentYear === 0) {
                        // For Year 0, show years -3 to 3
                        startYear = -3;
                        endYear = 3;
                    } else if (currentYear < 0) {
                        // For negative years, center the view with 3 years before and 3 years after
                        startYear = currentYear - 3;
                        endYear = currentYear + 3;
                    } else {
                        // For positive years, center the view with 3 years before and 3 years after
                        startYear = currentYear - 3;
                        endYear = currentYear + 3;
                    }
                    
                    console.log('Timeline template - Month view debug - currentYear:', currentYear, 'startYear:', startYear, 'endYear:', endYear);
                    
                    new TimelineSparklineCalendar('#timeline-month-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>-<?php echo $timeline_month !== null ? intval($timeline_month) : 0; ?>', {
                        startYear: startYear,
                        endYear: endYear,
                        showNavigation: true,
                        yearsPerView: 7
                    });
                    
                    // Initialize single month calendar
                    const root = document.querySelector('.timeline-calendar-root');
                    if (root && typeof TimelineCalendar !== 'undefined') {
                        const year = parseInt(root.getAttribute('data-year'));
                        const month = parseInt(root.getAttribute('data-month'));
                        new TimelineCalendar(root, year, month);
                    }
                } else {
                    console.log('Timeline template - TimelineSparklineCalendar not available, retrying in 100ms');
                    setTimeout(initializeMonthSparklineCalendar, 100);
                }
            }
            
            // Try to initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeMonthSparklineCalendar);
            } else {
                // DOM is already ready, try to initialize immediately
                initializeMonthSparklineCalendar();
            }
            </script>

        <?php elseif ($timeline_year !== null): ?>
            <!-- Year Overview - Sparkline calendar + 12 month grid -->
            <div class="timeline-year-overview">
                <!-- Sparkline Calendar at the top -->
                <div class="timeline-sparkline-calendar" id="timeline-year-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>"></div>
                
                <h2>Year <?php echo esc_html(intval($timeline_year)); ?> Overview</h2>
                <?php echo get_timeline_breadcrumbs($timeline_year !== null ? intval($timeline_year) : null); ?>
                
                <!-- Month Grid -->
                <div class="timeline-year-calendar">
                    <!-- Debug: Year calendar div generated for year <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?> -->
                    <?php
                    $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                    $i = 0;
                    foreach ($months as $num => $name):
                        if ($i % 3 === 0) echo "<div class='timeline-year-row'>";
                    ?>
                        <div class='timeline-month-block'>
                            <h4><a href="<?php echo home_url('/timeline/' . ($timeline_year !== null ? intval($timeline_year) : 0) . '/' . $num . '/'); ?>"><?php echo $name; ?></a></h4>
                            <div class='timeline-calendar-root' data-year='<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>' data-month='<?php echo $num; ?>'></div>
                        </div>
                    <?php
                        $i++;
                        if ($i % 3 === 0) echo "</div>";
                    endforeach;
                    if ($i % 3 !== 0) echo "</div>";
                    ?>
                </div>
                
                <!-- Year Navigation -->
                <div class="timeline-year-navigation">
                    <?php
                    $prev_year = intval($timeline_year) - 1;
                    $next_year = intval($timeline_year) + 1;
                    ?>
                    <a href="<?php echo home_url('/timeline/' . $prev_year . '/'); ?>" class="timeline-nav-prev">
                        &larr; Year <?php echo $prev_year; ?>
                    </a>
                    <a href="<?php echo home_url('/timeline/' . $next_year . '/'); ?>" class="timeline-nav-next">
                        Year <?php echo $next_year; ?> &rarr;
                    </a>
                </div>
                
                <!-- Timeline Statistics -->
                <div class="timeline-year-stats">
                    <h3>Timeline Statistics</h3>
                    <?php
                    global $wpdb;
                    $year_articles = $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(*) FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                        WHERE p.post_type = 'timeline_article' 
                        AND p.post_status = 'publish'
                        AND pm.meta_key = 'timeline_year'
                        AND pm.meta_value = %d
                    ", $timeline_year));
                    
                    $year_months_with_articles = $wpdb->get_var($wpdb->prepare("
                        SELECT COUNT(DISTINCT pm2.meta_value) FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id 
                        JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id 
                        WHERE p.post_type = 'timeline_article' 
                        AND p.post_status = 'publish'
                        AND pm1.meta_key = 'timeline_year'
                        AND pm1.meta_value = %d
                        AND pm2.meta_key = 'timeline_month'
                    ", $timeline_year));
                    ?>
                    <p>Articles in Year <?php echo esc_html(intval($timeline_year)); ?>: <?php echo esc_html($year_articles); ?></p>
                    <p>Months with Articles: <?php echo esc_html($year_months_with_articles); ?></p>
                </div>
            </div>
            
            <script>
            function initializeSparklineCalendar() {
                console.log('Timeline template - initializeSparklineCalendar called');
                console.log('Timeline template - TimelineSparklineCalendar available:', typeof TimelineSparklineCalendar !== 'undefined');
                console.log('Timeline template - TimelineCalendar available:', typeof TimelineCalendar !== 'undefined');
                
                // Initialize sparkline calendar with 8-year view centered on current year
                if (typeof TimelineSparklineCalendar !== 'undefined') {
                    const currentYear = <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>;
                    console.log('Timeline template - Current year:', currentYear);
                    
                    // Ensure we always show 7 years, with current year centered
                    let startYear, endYear;
                    if (currentYear === 0) {
                        // For Year 0, show years -3 to 3
                        startYear = -3;
                        endYear = 3;
                    } else if (currentYear < 0) {
                        // For negative years, center the view with 3 years before and 3 years after
                        startYear = currentYear - 3;
                        endYear = currentYear + 3;
                    } else {
                        // For positive years, center the view with 3 years before and 3 years after
                        startYear = currentYear - 3;
                        endYear = currentYear + 3;
                    }
                    
                    console.log('Timeline template - Year view debug - currentYear:', currentYear, 'startYear:', startYear, 'endYear:', endYear);
                    console.log('Timeline template - Calculated range:', startYear, 'to', endYear);
                    console.log('Timeline template - Creating sparkline calendar with selector: #timeline-year-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>');
                    
                    const element = document.querySelector('#timeline-year-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>');
                    console.log('Timeline template - Element found:', element);
                    
                    new TimelineSparklineCalendar('#timeline-year-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>', {
                        startYear: startYear,
                        endYear: endYear,
                        showNavigation: true,
                        yearsPerView: 7
                    });
                    
                    // Initialize individual month calendars
                    console.log('Timeline template - Found calendar roots:', document.querySelectorAll('.timeline-calendar-root').length);
                    document.querySelectorAll('.timeline-calendar-root').forEach(function(root) {
                        const year = parseInt(root.getAttribute('data-year'));
                        const month = parseInt(root.getAttribute('data-month'));
                        console.log('Timeline template - Initializing calendar for year', year, 'month', month);
                        new TimelineCalendar(root, year, month);
                    });
                } else {
                    console.log('Timeline template - TimelineSparklineCalendar not available, retrying in 100ms');
                    setTimeout(initializeSparklineCalendar, 100);
                }
            }
            
            // Try to initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeSparklineCalendar);
            } else {
                // DOM is already ready, try to initialize immediately
                initializeSparklineCalendar();
            }
            
            // Also try to initialize when window loads (in case scripts load after DOMContentLoaded)
            window.addEventListener('load', function() {
                if (typeof TimelineSparklineCalendar === 'undefined') {
                    console.log('Timeline template - TimelineSparklineCalendar still not available on window load');
                }
            });
            </script>
        <?php endif; ?>
    </div>
</div>

 