<?php
/**
 * Timeline Template
 * 
 * Handles all timeline URL structures:
 * /timeline/ - Overview with sparkline calendar
 * /timeline/{year}/ - Year overview (12 months)
 * /timeline/{year}/{month}/ - Month overview (all days in that month)
 * /timeline/{year}/{month}/{day}/ - Day overview (all articles for that day)
 * /timeline/{year}/{month}/{day}/{article}/ - Article view
 *
 * @package TimelineCalendar
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// DEBUG: Output timeline context at the top of the template
$debug_vars = array(
	'timeline_year' => isset( $timeline_year ) ? $timeline_year : 'unset',
	'timeline_month' => isset( $timeline_month ) ? $timeline_month : 'unset',
	'timeline_day' => isset( $timeline_day ) ? $timeline_day : 'unset',
	'timeline_article' => isset( $timeline_article ) ? $timeline_article : 'unset',
	'timeline_overview' => isset( $timeline_overview ) ? $timeline_overview : 'unset',
);
echo '<!-- Timeline template loaded: ' . esc_html( wp_json_encode( $debug_vars ) ) . ' -->';

/**
 * Generate breadcrumb navigation for timeline pages
 *
 * @param int|null    $year    The timeline year.
 * @param int|null    $month   The timeline month.
 * @param int|null    $day     The timeline day.
 * @param string|null $article The article slug.
 * @return string HTML for breadcrumb navigation.
 */
function get_timeline_breadcrumbs( $year = null, $month = null, $day = null, $article = null ) {
	$breadcrumbs = array();
	$breadcrumbs[] = '<a href="' . esc_url( home_url( '/timeline/' ) ) . '">' . esc_html__( 'Timeline', 'timeline-calendar' ) . '</a>';
	
	if ( $year !== null ) {
		$breadcrumbs[] = '<a href="' . esc_url( home_url( '/timeline/' . $year . '/' ) ) . '">' . esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $year ) ) . '</a>';
		
		if ( $month !== null ) {
			$month_names = array(
				1 => __( 'January', 'timeline-calendar' ),
				2 => __( 'February', 'timeline-calendar' ),
				3 => __( 'March', 'timeline-calendar' ),
				4 => __( 'April', 'timeline-calendar' ),
				5 => __( 'May', 'timeline-calendar' ),
				6 => __( 'June', 'timeline-calendar' ),
				7 => __( 'July', 'timeline-calendar' ),
				8 => __( 'August', 'timeline-calendar' ),
				9 => __( 'September', 'timeline-calendar' ),
				10 => __( 'October', 'timeline-calendar' ),
				11 => __( 'November', 'timeline-calendar' ),
				12 => __( 'December', 'timeline-calendar' )
			);
			$breadcrumbs[] = '<a href="' . esc_url( home_url( '/timeline/' . $year . '/' . $month . '/' ) ) . '">' . esc_html( $month_names[ $month ] ) . '</a>';
			
			if ( $day !== null ) {
				$breadcrumbs[] = '<a href="' . esc_url( home_url( '/timeline/' . $year . '/' . $month . '/' . $day . '/' ) ) . '">' . esc_html( $day ) . '</a>';
				
				if ( $article !== null ) {
					// Fetch the actual post title instead of using the slug
					$article_title = $article; // Default to slug if we can't find the post
					
					// Try to get the post by slug and year/month/day meta
					$args = array(
						'post_type' => 'timeline_article',
						'name' => $article,
						'meta_query' => array(
							array(
								'key' => 'timeline_year',
								'value' => $year,
								'compare' => '='
							),
							array(
								'key' => 'timeline_month',
								'value' => $month,
								'compare' => '='
							),
							array(
								'key' => 'timeline_day',
								'value' => $day,
								'compare' => '='
							)
						),
						'posts_per_page' => 1
					);
					
					$query = new WP_Query( $args );
					if ( $query->have_posts() ) {
						$query->the_post();
						$article_title = get_the_title();
						wp_reset_postdata();
					}
					
					$breadcrumbs[] = '<span>' . esc_html( $article_title ) . '</span>';
				}
			}
		}
	}
	
	return '<div class="timeline-breadcrumbs">' . implode( ' &raquo; ', $breadcrumbs ) . '</div>';
}

// Get URL parameters
$timeline_year = get_query_var( 'timeline_year', null );
$timeline_month = get_query_var( 'timeline_month', null );
$timeline_day = get_query_var( 'timeline_day', null );
$timeline_article = get_query_var( 'timeline_article', null );
$timeline_overview = get_query_var( 'timeline_overview', null );

// Get plugin settings for year restrictions
$allow_year_zero = get_timeline_calendar_option( 'allow_year_zero', false );
$allow_negative_years = get_timeline_calendar_option( 'allow_negative_years', false );
$min_year = get_timeline_min_year();

// Check if current year is allowed
$current_year_allowed = true;
if ( $timeline_year !== null ) {
    $current_year_allowed = is_timeline_year_allowed( intval( $timeline_year ) );
}

// Debug output
if ( isset( $_GET['debug'] ) ) {
	echo "<!-- Debug Info:\n";
	echo "timeline_year: " . ( $timeline_year !== null ? esc_html( $timeline_year ) : 'null' ) . "\n";
	echo "timeline_month: " . ( $timeline_month !== null ? esc_html( $timeline_month ) : 'null' ) . "\n";
	echo "timeline_day: " . ( $timeline_day !== null ? esc_html( $timeline_day ) : 'null' ) . "\n";
	echo "timeline_article: " . ( $timeline_article !== null ? esc_html( $timeline_article ) : 'null' ) . "\n";
	echo "timeline_overview: " . ( $timeline_overview !== null ? esc_html( $timeline_overview ) : 'null' ) . "\n";
	echo "-->\n";
}

// Always show debug info for now
echo "<!-- Debug Info:\n";
echo "timeline_year: " . ( $timeline_year !== null ? esc_html( $timeline_year ) : 'null' ) . "\n";
echo "timeline_month: " . ( $timeline_month !== null ? esc_html( $timeline_month ) : 'null' ) . "\n";
echo "timeline_day: " . ( $timeline_day !== null ? esc_html( $timeline_day ) : 'null' ) . "\n";
echo "timeline_article: " . ( $timeline_article !== null ? esc_html( $timeline_article ) : 'null' ) . "\n";
echo "timeline_overview: " . ( $timeline_overview !== null ? esc_html( $timeline_overview ) : 'null' ) . "\n";
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
                    $total_articles = $wpdb->get_var( $wpdb->prepare( "
                        SELECT COUNT(*) FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                        WHERE p.post_type = %s 
                        AND p.post_status = %s
                        AND pm.meta_key = %s
                        AND CAST(pm.meta_value AS SIGNED) >= %d
                    ", 'timeline_article', 'publish', 'timeline_year', $min_year ) );
                    $year_range = $wpdb->get_row( $wpdb->prepare( "
                        SELECT 
                            MIN(CAST(pm.meta_value AS SIGNED)) as min_year,
                            MAX(CAST(pm.meta_value AS SIGNED)) as max_year
                        FROM {$wpdb->posts} p
                        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                        WHERE p.post_type = %s 
                        AND p.post_status = %s
                        AND pm.meta_key = %s
                        AND CAST(pm.meta_value AS SIGNED) >= %d
                    ", 'timeline_article', 'publish', 'timeline_year', $min_year ) );
                    ?>
                    <p><?php echo esc_html__( 'Total Articles:', 'timeline-calendar' ); ?> <?php echo esc_html( $total_articles ); ?></p>
                    <?php if ( $year_range ) : ?>
                        <p><?php echo esc_html__( 'Year Range:', 'timeline-calendar' ); ?> <?php echo esc_html( $year_range->min_year ); ?> - <?php echo esc_html( $year_range->max_year ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($timeline_article !== null): ?>
            <!-- Individual Article View -->
            <?php
            $args = array(
                'post_type' => 'timeline_article',
                'name' => $timeline_article,
                'meta_query' => array(
                    array(
                        'key' => 'timeline_year',
                        'value' => $timeline_year,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'timeline_month',
                        'value' => $timeline_month,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'timeline_day',
                        'value' => $timeline_day,
                        'compare' => '='
                    )
                )
            );
            $query = new WP_Query( $args );
            
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
                                <div class="timeline-article-meta-row">
                                    <span class="timeline-date">
                                        <?php 
                                        $month_names = array(
                                            1 => __( 'January', 'timeline-calendar' ),
                                            2 => __( 'February', 'timeline-calendar' ),
                                            3 => __( 'March', 'timeline-calendar' ),
                                            4 => __( 'April', 'timeline-calendar' ),
                                            5 => __( 'May', 'timeline-calendar' ),
                                            6 => __( 'June', 'timeline-calendar' ),
                                            7 => __( 'July', 'timeline-calendar' ),
                                            8 => __( 'August', 'timeline-calendar' ),
                                            9 => __( 'September', 'timeline-calendar' ),
                                            10 => __( 'October', 'timeline-calendar' ),
                                            11 => __( 'November', 'timeline-calendar' ),
                                            12 => __( 'December', 'timeline-calendar' )
                                        );
                                        echo esc_html( sprintf( __( '%s %s, Year %s', 'timeline-calendar' ), $month_names[ $timeline_month ], $timeline_day, $timeline_year ) ); 
                                        ?>
                                    </span>
                                    <?php 
                                    $time_of_day = get_post_meta(get_the_ID(), 'timeline_time_of_day', true);
                                    if (!empty($time_of_day)): 
                                    ?>
                                        <span class="timeline-article-time-of-day"><?php echo esc_html($time_of_day); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </header>
                        <div class="timeline-article-content">
                            <?php the_content(); ?>
                        </div>
                        
                        <?php 
                        $reference = get_post_meta(get_the_ID(), 'timeline_reference', true);
                        if (!empty($reference)): 
                        ?>
                            <div class="timeline-article-reference">
                                <span class="timeline-reference-label">Reference:</span>
                                <?php 
                                // Check if the reference looks like a URL
                                if (filter_var($reference, FILTER_VALIDATE_URL) || strpos($reference, 'http') === 0) {
                                    echo '<a href="' . esc_url($reference) . '" target="_blank" rel="noopener noreferrer" class="timeline-reference-link">' . esc_html($reference) . '</a>';
                                } else {
                                    echo '<span class="timeline-reference-text">' . esc_html($reference) . '</span>';
                                }
                                ?>
                            </div>
                        <?php endif; ?>
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
                        
                        <div class="timeline-nav-prev-container">
                            <?php if ($prev_article): ?>
                                <a href="<?php echo get_timeline_permalink($prev_article['id']); ?>" class="timeline-nav-prev">
                                    &larr; Previous
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="timeline-nav-next-container">
                            <?php if ($next_article): ?>
                                <a href="<?php echo get_timeline_permalink($next_article['id']); ?>" class="timeline-nav-next">
                                    Next &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
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
                    
                    // Initialize sparkline calendar with year range based on plugin settings
                    if (typeof TimelineSparklineCalendar !== 'undefined') {
                        const currentYear = <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>;
                        console.log('Timeline template - Current year:', currentYear);
                        
                        // Get plugin settings for year restrictions
                        const allowYearZero = <?php echo $allow_year_zero ? 'true' : 'false'; ?>;
                        const allowNegativeYears = <?php echo $allow_negative_years ? 'true' : 'false'; ?>;
                        const minYear = <?php echo $min_year; ?>;
                        
                        // Calculate year range based on settings and current year
                        let startYear, endYear;
                        if (currentYear === 0 && allowYearZero) {
                            // For Year 0, show years -3 to 3 (if negative years allowed)
                            startYear = allowNegativeYears ? -3 : 0;
                            endYear = 3;
                        } else if (currentYear < 0 && allowNegativeYears) {
                            // For negative years, center the view with 3 years before and 3 years after
                            startYear = Math.max(minYear, currentYear - 3);
                            endYear = currentYear + 3;
                        } else {
                            // For positive years, center the view with 3 years before and 3 years after
                            startYear = Math.max(minYear, currentYear - 3);
                            endYear = currentYear + 3;
                        }
                        
                        console.log('Timeline template - Article view debug - currentYear:', currentYear, 'startYear:', startYear, 'endYear:', endYear, 'minYear:', minYear);
                        
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
                    // Collect articles and sort by time of day
                    $articles = [];
                    while ($query->have_posts()): $query->the_post();
                        $time_of_day = get_post_meta(get_the_ID(), 'timeline_time_of_day', true);
                        $articles[] = [
                            'id' => get_the_ID(),
                            'title' => get_the_title(),
                            'excerpt' => get_the_excerpt(),
                            'time_of_day' => $time_of_day
                        ];
                    endwhile;
                    wp_reset_postdata();
                    
                    // Sort articles by time of day order, then by title
                    $time_order = ['Morning', 'Day', 'Afternoon', 'Evening', 'Night'];
                    usort($articles, function($a, $b) use ($time_order) {
                        $a_time_index = $a['time_of_day'] ? array_search($a['time_of_day'], $time_order) : -1;
                        $b_time_index = $b['time_of_day'] ? array_search($b['time_of_day'], $time_order) : -1;
                        
                        // Articles without time of day come first
                        if ($a_time_index === -1 && $b_time_index !== -1) return -1;
                        if ($a_time_index !== -1 && $b_time_index === -1) return 1;
                        if ($a_time_index === -1 && $b_time_index === -1) {
                            return strcasecmp($a['title'], $b['title']);
                        }
                        
                        // Then sort by time of day order
                        if ($a_time_index !== $b_time_index) {
                            return $a_time_index - $b_time_index;
                        }
                        
                        // Finally by title
                        return strcasecmp($a['title'], $b['title']);
                    });
                ?>
                    <div class="timeline-day-articles">
                        <div class="timeline-articles-list">
                            <?php foreach ($articles as $article): ?>
                                <article class="timeline-article-preview">
                                    <div class="timeline-article-header-row">
                                        <h3><a href="<?php echo get_timeline_permalink($article['id']); ?>"><?php echo esc_html($article['title']); ?></a></h3>
                                        <?php if (!empty($article['time_of_day'])): ?>
                                            <span class="timeline-time-of-day"><?php echo esc_html($article['time_of_day']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($article['excerpt'])): ?>
                                        <div class="timeline-article-excerpt"><?php echo wp_kses_post($article['excerpt']); ?></div>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
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
                    
                    $prev_year_allowed = is_timeline_year_allowed( $prev_year );
                    $next_year_allowed = is_timeline_year_allowed( $next_year );
                    ?>
                    
                    <?php if ( $prev_year_allowed ) : ?>
                        <a href="<?php echo esc_url( home_url( '/timeline/' . $prev_year . '/' . $prev_month . '/' . $prev_day . '/' ) ); ?>" class="timeline-nav-prev">
                            &larr; <?php echo esc_html( $month_names[$prev_month] ); ?> <?php echo esc_html( $prev_day ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $prev_year ) ); ?>
                        </a>
                    <?php else : ?>
                        <span class="timeline-nav-prev timeline-nav-disabled" title="<?php echo esc_attr( sprintf( __( 'Year %s is not allowed with current settings', 'timeline-calendar' ), $prev_year ) ); ?>">
                            &larr; <?php echo esc_html( $month_names[$prev_month] ); ?> <?php echo esc_html( $prev_day ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $prev_year ) ); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ( $next_year_allowed ) : ?>
                        <a href="<?php echo esc_url( home_url( '/timeline/' . $next_year . '/' . $next_month . '/' . $next_day . '/' ) ); ?>" class="timeline-nav-next">
                            <?php echo esc_html( $month_names[$next_month] ); ?> <?php echo esc_html( $next_day ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $next_year ) ); ?> &rarr;
                        </a>
                    <?php else : ?>
                        <span class="timeline-nav-next timeline-nav-disabled" title="<?php echo esc_attr( sprintf( __( 'Year %s is not allowed with current settings', 'timeline-calendar' ), $next_year ) ); ?>">
                            <?php echo esc_html( $month_names[$next_month] ); ?> <?php echo esc_html( $next_day ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $next_year ) ); ?> &rarr;
                        </span>
                    <?php endif; ?>
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
                
                // Initialize sparkline calendar with year range based on plugin settings
                if (typeof TimelineSparklineCalendar !== 'undefined') {
                    const currentYear = <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>;
                    console.log('Timeline template - Current year:', currentYear);
                    
                    // Get plugin settings for year restrictions
                    const allowYearZero = <?php echo $allow_year_zero ? 'true' : 'false'; ?>;
                    const allowNegativeYears = <?php echo $allow_negative_years ? 'true' : 'false'; ?>;
                    const minYear = <?php echo $min_year; ?>;
                    
                    // Calculate year range based on settings and current year
                    let startYear, endYear;
                    if (currentYear === 0 && allowYearZero) {
                        // For Year 0, show years -3 to 3 (if negative years allowed)
                        startYear = allowNegativeYears ? -3 : 0;
                        endYear = 3;
                    } else if (currentYear < 0 && allowNegativeYears) {
                        // For negative years, center the view with 3 years before and 3 years after
                        startYear = Math.max(minYear, currentYear - 3);
                        endYear = currentYear + 3;
                    } else {
                        // For positive years, center the view with 3 years before and 3 years after
                        startYear = Math.max(minYear, currentYear - 3);
                        endYear = currentYear + 3;
                    }
                    
                    console.log('Timeline template - Day view debug - currentYear:', currentYear, 'startYear:', startYear, 'endYear:', endYear, 'minYear:', minYear);
                    
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
                    
                    $prev_year_allowed = is_timeline_year_allowed( $prev_year );
                    $next_year_allowed = is_timeline_year_allowed( $next_year );
                    ?>
                    
                    <?php if ( $prev_year_allowed ) : ?>
                        <a href="<?php echo esc_url( home_url( '/timeline/' . $prev_year . '/' . $prev_month . '/' ) ); ?>" class="timeline-nav-prev">
                            &larr; <?php echo esc_html( $month_names[$prev_month] ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $prev_year ) ); ?>
                        </a>
                    <?php else : ?>
                        <span class="timeline-nav-prev timeline-nav-disabled" title="<?php echo esc_attr( sprintf( __( 'Year %s is not allowed with current settings', 'timeline-calendar' ), $prev_year ) ); ?>">
                            &larr; <?php echo esc_html( $month_names[$prev_month] ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $prev_year ) ); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ( $next_year_allowed ) : ?>
                        <a href="<?php echo esc_url( home_url( '/timeline/' . $next_year . '/' . $next_month . '/' ) ); ?>" class="timeline-nav-next">
                            <?php echo esc_html( $month_names[$next_month] ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $next_year ) ); ?> &rarr;
                        </a>
                    <?php else : ?>
                        <span class="timeline-nav-next timeline-nav-disabled" title="<?php echo esc_attr( sprintf( __( 'Year %s is not allowed with current settings', 'timeline-calendar' ), $next_year ) ); ?>">
                            <?php echo esc_html( $month_names[$next_month] ); ?>, <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $next_year ) ); ?> &rarr;
                        </span>
                    <?php endif; ?>
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
                
                // Initialize sparkline calendar with year range based on plugin settings
                if (typeof TimelineSparklineCalendar !== 'undefined') {
                    const currentYear = <?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>;
                    console.log('Timeline template - Current year:', currentYear);
                    
                    // Get plugin settings for year restrictions
                    const allowYearZero = <?php echo $allow_year_zero ? 'true' : 'false'; ?>;
                    const allowNegativeYears = <?php echo $allow_negative_years ? 'true' : 'false'; ?>;
                    const minYear = <?php echo $min_year; ?>;
                    
                    // Calculate year range based on settings and current year
                    let startYear, endYear;
                    if (currentYear === 0 && allowYearZero) {
                        // For Year 0, show years -3 to 3 (if negative years allowed)
                        startYear = allowNegativeYears ? -3 : 0;
                        endYear = 3;
                    } else if (currentYear < 0 && allowNegativeYears) {
                        // For negative years, center the view with 3 years before and 3 years after
                        startYear = Math.max(minYear, currentYear - 3);
                        endYear = currentYear + 3;
                    } else {
                        // For positive years, center the view with 3 years before and 3 years after
                        startYear = Math.max(minYear, currentYear - 3);
                        endYear = currentYear + 3;
                    }
                    
                    console.log('Timeline template - Month view debug - currentYear:', currentYear, 'startYear:', startYear, 'endYear:', endYear, 'minYear:', minYear);
                    
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
                    
                    $prev_year_allowed = is_timeline_year_allowed( $prev_year );
                    $next_year_allowed = is_timeline_year_allowed( $next_year );
                    ?>
                    
                    <?php if ( $prev_year_allowed ) : ?>
                        <a href="<?php echo esc_url( home_url( '/timeline/' . $prev_year . '/' ) ); ?>" class="timeline-nav-prev">
                            &larr; <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $prev_year ) ); ?>
                        </a>
                    <?php else : ?>
                        <span class="timeline-nav-prev timeline-nav-disabled" title="<?php echo esc_attr( sprintf( __( 'Year %s is not allowed with current settings', 'timeline-calendar' ), $prev_year ) ); ?>">
                            &larr; <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $prev_year ) ); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ( $next_year_allowed ) : ?>
                        <a href="<?php echo esc_url( home_url( '/timeline/' . $next_year . '/' ) ); ?>" class="timeline-nav-next">
                            <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $next_year ) ); ?> &rarr;
                        </a>
                    <?php else : ?>
                        <span class="timeline-nav-next timeline-nav-disabled" title="<?php echo esc_attr( sprintf( __( 'Year %s is not allowed with current settings', 'timeline-calendar' ), $next_year ) ); ?>">
                            <?php echo esc_html( sprintf( __( 'Year %s', 'timeline-calendar' ), $next_year ) ); ?> &rarr;
                        </span>
                    <?php endif; ?>
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
            
            <div id="timeline-year-sparkline-<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>-init" 
                 data-current-year="<?php echo $timeline_year !== null ? intval($timeline_year) : 0; ?>"
                 data-allow-year-zero="<?php echo $allow_year_zero ? 'true' : 'false'; ?>"
                 data-allow-negative-years="<?php echo $allow_negative_years ? 'true' : 'false'; ?>"
                 data-min-year="<?php echo $min_year; ?>">
            </div>
        <?php endif; ?>
    </div>
</div>

 