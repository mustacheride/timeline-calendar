# Timeline Calendar WordPress Plugin

A comprehensive WordPress plugin for creating and managing timeline-based content with interactive calendar views, sparkline visualizations, and hierarchical navigation.

## Description

The Timeline Calendar plugin transforms WordPress into a powerful timeline management system, allowing you to create historical content organized by years, months, and days. Perfect for historical blogs, project timelines, or any content that needs chronological organization.

## Features

### 🗓️ Interactive Calendar Views
- **Year View**: 12-month grid showing all months with article counts
- **Month View**: Single month calendar with day-by-day article display
- **Day View**: Detailed view of articles for a specific day
- **Article View**: Individual article display with navigation

### 📊 Dynamic Sparkline Calendar
- Archive.org-style sparkline visualization
- **Dynamic height scaling**: Month heights scale based on article count
- **Global consistency**: Heights remain consistent across all timeline views
- **Visual data representation**: Quickly identify busy periods and patterns
- Interactive navigation between years
- Customizable date ranges and display options

### ⚙️ Configurable Settings
- **Reference Year**: Configure which year to use for day-of-week alignment
- **Year 0 Support**: Option to enable/disable Year 0 in timeline articles
- **Negative Years**: Option to enable/disable BC/BCE years (e.g., -100 for 100 BC)
- Admin settings page under Settings > Timeline Calendar

### 🔗 Smart URL Structure
- SEO-friendly URLs: `/timeline/year/month/day/article/`
- Automatic breadcrumb navigation
- Previous/next article navigation
- Day-to-day and month-to-month navigation
- **Admin permalink display**: Shows correct timeline URLs in WordPress admin

### 📝 Content Management
- Custom post type: `timeline_article`
- Meta fields for year, month, and day
- Automatic permalink generation
- Content organization by chronological order
- **Real-time permalink preview**: See timeline URLs update as you edit

### 🎨 Responsive Design
- Mobile-friendly interface
- Modern, clean design
- Consistent styling across all views
- Customizable CSS variables
- **Improved sparkline layout**: Year labels at top, sparklines at bottom

## Installation

1. **Upload the plugin** to your `/wp-content/plugins/timeline-calendar/` directory
2. **Activate the plugin** through the 'Plugins' menu in WordPress
3. **Flush rewrite rules** by going to Settings > Permalinks and clicking "Save Changes"
4. **Configure settings** at Settings > Timeline Calendar

## Configuration

### Plugin Settings

Go to **Settings > Timeline Calendar** to configure:

- **Reference Year for Day of Week Alignment**: Set the year used for calendar grid alignment (default: 1989)
- **Allow Year 0**: Enable/disable Year 0 in timeline articles
- **Allow Negative Years**: Enable/disable BC/BCE years (e.g., -100 for 100 BC)

### Customizing Styles

The plugin uses CSS custom properties for easy theming:

```css
:root {
    --calendar-bg: #ffffff;
    --calendar-border: #e1e5e9;
    --calendar-text: #2c3e50;
    --calendar-hover: #f8f9fa;
    --calendar-article-highlight: #0066cc;
    --calendar-article-highlight-hover: #0052a3;
    --year-nav-text: #6c757d;
    --year-nav-bg: #f8f9fa;
    --year-nav-hover: #e9ecef;
}
```

## Usage

### Creating Timeline Articles

1. Go to **Timeline Articles** in your WordPress admin
2. Click **Add New**
3. Fill in the article content as usual
4. Set the **Timeline Year**, **Timeline Month**, and **Timeline Day** meta fields
   - Year range depends on your settings (1+, 0+, or negative years)
5. **Watch the permalink update** in real-time as you change the timeline date
6. Publish your article

### Displaying the Timeline

#### Shortcodes

**Sparkline Calendar (Recommended):**
```
[timeline_sparkline]
[timeline_sparkline start_year="0" end_year="10"]
[timeline_sparkline years_per_view="5"]
```

**Legacy Sparkline Calendar:**
```
[timeline_sparkline_calendar]
```

**Full Calendar:**
```
[timeline_calendar]
```

**Year Calendar:**
```
[timeline_year_calendar year="3"]
```

**This Day in History:**
```
[timeline_this_day_in_history]
```

#### URL Structure

- **Overview**: `/timeline/`
- **Year**: `/timeline/3/`
- **Month**: `/timeline/3/8/` (August, Year 3)
- **Day**: `/timeline/3/8/15/` (August 15, Year 3)
- **Article**: `/timeline/3/8/15/article-name/`

### Navigation

The plugin provides automatic navigation between:
- Previous/next articles (sorted by year, month, day, title)
- Previous/next days
- Previous/next months
- Previous/next years

## Technical Details

### File Structure

```
timeline-calendar/
├── assets/
│   ├── style.css
│   ├── calendar.js
│   ├── sparkline-calendar.js
│   ├── year-view.js
│   └── timeline-header.js
├── templates/
│   └── timeline-template.php
├── timeline-calendar.php
├── uninstall.php
├── LICENSE
└── README.md
```

### Hooks and Filters

The plugin uses several WordPress hooks:

- `parse_request` - Handle timeline URL parsing
- `template_redirect` - Load timeline templates
- `redirect_canonical` - Prevent unwanted redirects
- `wp_enqueue_scripts` - Load assets
- `post_link` - Customize permalinks in admin
- `get_permalink` - Customize permalink generation
- `sample_permalink_html` - Update permalink preview

### Database

The plugin creates a custom post type `timeline_article` with meta fields:
- `timeline_year` (integer)
- `timeline_month` (integer, 1-12)
- `timeline_day` (integer, 1-31)

Settings are stored in `wp_options` table as `timeline_calendar_options`.

### AJAX Endpoints

The plugin provides several AJAX endpoints for dynamic content:

- `timeline_calendar_articles` - Fetch articles for a specific year/month
- `timeline_calendar_years` - Get available years
- `timeline_sparkline_data` - Get sparkline data

## Troubleshooting

### Common Issues

**Timeline URLs not working:**
- Flush rewrite rules: Settings > Permalinks > Save Changes
- Check that the plugin is activated

**Sparkline not displaying:**
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify the shortcode is correct: `[timeline_sparkline]`

**Homepage issues:**
- The plugin only affects `/timeline/` URLs
- Other pages should work normally

**Admin permalinks showing wrong URLs:**
- Clear browser cache
- Check that timeline date meta fields are set correctly
- Verify plugin settings are saved

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Changelog

### Version 1.0.0
- Initial release
- Basic timeline functionality
- Sparkline calendar
- URL routing system
- Navigation between articles

### Recent Updates
- **Configurable Settings**: Added admin settings page for reference year, Year 0, and negative years
- **Dynamic Sparkline Heights**: Month heights now scale based on article count with global consistency
- **Improved Layout**: Year labels aligned to top, sparklines to bottom
- **Admin Permalink Fix**: Timeline URLs now display correctly in WordPress admin
- **Real-time Permalink Preview**: See timeline URLs update as you edit articles
- **Enhanced Navigation**: Better alignment and sizing for sparkline calendar elements

## Support

For support, feature requests, or bug reports, please contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by mustacheride for WordPress timeline management. 