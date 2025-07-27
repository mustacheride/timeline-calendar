# Timeline Calendar WordPress Plugin

A comprehensive WordPress plugin for creating and managing timeline-based content with interactive calendar views, sparkline visualizations, and hierarchical navigation. Fully integrates with any WordPress theme.

## Description

The Timeline Calendar plugin transforms WordPress into a powerful timeline management system, allowing you to create historical content organized by years, months, and days. Perfect for historical blogs, project timelines, or any content that needs chronological organization. The plugin seamlessly integrates with your WordPress theme, respecting your site's design, colors, and layout.

## Features

### 🗓️ Interactive Calendar Views
- **Year View**: 12-month grid showing all months with article counts
- **Month View**: Single month calendar with day-by-day article display
- **Day View**: Detailed view of articles for a specific day
- **Article View**: Individual article display with navigation

### 📊 Dynamic Sparkline Calendar with Interactive Modals
- Archive.org-style sparkline visualization
- **Interactive month modals**: Hover or click on months to see all articles in a compact popup
- **Chronological article listing**: Articles organized by day within each month
- **Dynamic height scaling**: Month heights scale based on article count
- **Global consistency**: Heights remain consistent across all timeline views
- **Visual data representation**: Quickly identify busy periods and patterns
- **Smart navigation**: Ctrl+click for direct navigation, regular click for modal preview
- Customizable date ranges and display options

### 🎨 Full Theme Integration
- **Seamless theme compatibility**: All views respect your site's design and branding
- **WordPress template hierarchy**: Proper integration with your theme's layout system
- **Theme-aware styling**: Automatically adapts to your theme's colors and fonts
- **Responsive design**: Works with your theme's responsive breakpoints
- **Header and footer integration**: Timeline pages include your site's navigation and branding

### ⚙️ Configurable Settings
- **Reference Year**: Configure which year to use for day-of-week alignment
- **Year 0 Support**: Option to enable/disable Year 0 in timeline articles
- **Negative Years**: Option to enable/disable BC/BCE years (e.g., -100 for 100 BC)
- Admin settings page under Timeline Articles > Settings

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

## Installation

1. **Upload the plugin** to your `/wp-content/plugins/timeline-calendar/` directory
2. **Activate the plugin** through the 'Plugins' menu in WordPress
3. **Flush rewrite rules** by going to Settings > Permalinks and clicking "Save Changes"
4. **Configure settings** at Timeline Articles > Settings

## Configuration

### Plugin Settings

Go to **Timeline Articles > Settings** to configure:

- **Reference Year for Day of Week Alignment**: Set the year used for calendar grid alignment (default: 1989)
- **Allow Year 0**: Enable/disable Year 0 in timeline articles
- **Allow Negative Years**: Enable/disable BC/BCE years (e.g., -100 for 100 BC)
- **Flush Rewrite Rules**: Manual option to refresh URL routing if needed

### Theme Customization

The plugin automatically respects your WordPress theme's styling through CSS custom properties:

```css
:root {
    /* Plugin uses theme-aware variables that adapt to your site */
    --timeline-calendar-bg: var(--wp--preset--color--background, #ffffff);
    --timeline-calendar-border: var(--wp--preset--color--primary, #e1e5e9);
    --timeline-calendar-text: var(--wp--preset--color--foreground, currentColor);
    --timeline-calendar-hover: var(--wp--preset--color--tertiary, rgba(0,0,0,0.05));
    --timeline-calendar-highlight: var(--wp--preset--color--primary, #0073aa);
    --timeline-calendar-highlight-hover: var(--wp--preset--color--primary, #005a87);
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

**Sparkline Calendar (Primary):**
```
[timeline_sparkline_calendar]
[timeline_sparkline_calendar start_year="0" end_year="10"]
[timeline_sparkline_calendar years_per_view="5"]
```

**Full Year Calendar:**
```
[timeline_year_calendar year="3"]
```

**Single Month Calendar:**
```
[timeline_calendar]
```

**This Day in History:**
```
[timeline_this_day_in_history]
```

#### URL Structure

- **Overview**: `/timeline/` - Shows sparkline calendar with modal previews
- **Year**: `/timeline/3/` - 12-month grid view for Year 3
- **Month**: `/timeline/3/8/` - Calendar view for August, Year 3
- **Day**: `/timeline/3/8/15/` - Article list for August 15, Year 3
- **Article**: `/timeline/3/8/15/article-name/` - Individual article view

### Interactive Features

#### Sparkline Modal System
- **Hover Preview**: Hover over any month with articles to see a preview modal
- **Click for Details**: Click on months to see all articles organized by day
- **Compact Layout**: Dates and article titles displayed on the same row for efficiency
- **Smart Navigation**: 
  - Regular click: Show modal preview
  - Ctrl/Cmd+click: Navigate directly to month page
  - Right-click: Access browser context menu for new tabs

#### Calendar Navigation
The plugin provides automatic navigation between:
- Previous/next articles (sorted by year, month, day, title)
- Previous/next days with article hover previews
- Previous/next months
- Previous/next years

## Technical Details

### File Structure

```
timeline-calendar/
├── assets/
│   ├── style.css              # Theme-aware styling
│   ├── calendar.js            # Month/day calendar functionality
│   ├── sparkline-calendar.js  # Sparkline + modal system
│   ├── year-view.js           # Year overview functionality
│   └── timeline-header.js     # Navigation enhancements
├── templates/
│   ├── timeline-template.php  # Main template integrated with themes
│   └── calendar.php          # Calendar shortcode template
├── timeline-calendar.php      # Main plugin file
├── uninstall.php             # Clean uninstall
├── LICENSE
└── README.md
```

### WordPress Integration

The plugin uses proper WordPress integration patterns:

- **Template Hierarchy**: Uses `template_include` filter for theme compatibility
- **Asset Management**: Conditional loading of CSS/JS only when needed
- **Query Integration**: Creates virtual posts that work with theme systems
- **Hook System**: Proper use of WordPress actions and filters

### Database

The plugin creates a custom post type `timeline_article` with meta fields:
- `timeline_year` (integer)
- `timeline_month` (integer, 1-12)
- `timeline_day` (integer, 1-31)

Settings are stored in `wp_options` table as `timeline_calendar_options`.

### AJAX Endpoints

The plugin provides AJAX endpoints for dynamic content:

- `timeline_calendar_articles` - Fetch articles for sparkline modals
- `timeline_calendar_years` - Get available years for navigation
- Includes proper nonce security and error handling

## Troubleshooting

### Common Issues

**Timeline URLs not working:**
- Flush rewrite rules: Timeline Articles > Settings > "Flush Rewrite Rules"
- Alternative: Settings > Permalinks > Save Changes
- Check that the plugin is activated

**Timeline pages don't match site design:**
- Ensure you're using the latest version with theme integration
- Check that your theme supports WordPress standards
- Clear any caching plugins

**Sparkline modal not displaying:**
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify timeline articles exist for the year/month

**Modal shows "No articles found":**
- Check that articles have proper timeline meta fields set
- Verify articles are published (not draft)
- Ensure the year/month combinations match your articles

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Changelog

### Version 1.0.0 - Initial Release
- Basic timeline functionality
- Sparkline calendar visualization
- URL routing system
- Timeline article management

### Recent Major Updates

#### Theme Integration & JavaScript Improvements
- **Full WordPress theme integration**: Timeline pages now properly integrate with any WordPress theme
- **Template hierarchy support**: Uses WordPress `template_include` filter for seamless theme compatibility
- **Theme-aware styling**: CSS variables automatically adapt to WordPress theme colors and fonts
- **JavaScript initialization fixes**: Resolved syntax errors and improved asset loading
- **Conditional asset loading**: Better performance by only loading assets when needed

#### Interactive Sparkline Modal System
- **Hover-to-preview modals**: Interactive month previews on sparkline calendar
- **Compact article display**: Dates and titles on same row for space efficiency
- **Chronological organization**: Articles sorted by day within each month
- **Smart navigation options**: Click for modal, Ctrl+click for direct navigation
- **Enhanced visual feedback**: Improved hover effects and animations
- **AJAX-powered content**: Dynamic loading of month articles with proper security

#### User Experience Enhancements
- **Improved accessibility**: Better keyboard and screen reader support
- **Mobile responsiveness**: Enhanced mobile experience with theme integration
- **Performance optimization**: Faster loading and better caching compatibility
- **Debug improvements**: Better error logging and troubleshooting information

## Support

For support, feature requests, or bug reports, please visit the plugin's GitHub repository or contact the plugin author.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by mustacheride for comprehensive WordPress timeline management with modern theme integration and interactive user experience. 