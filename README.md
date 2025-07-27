# Kntnt Global Styles

[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Requires PHP: 8.2+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Requires WordPress: 6.8+](https://img.shields.io/badge/WordPress-6.8+-blue.svg)](https://wordpress.org)

WordPress plugin that manages a global CSS file editable in the block editor.

> [!NOTE]
> **For WordPress Users:** [Install](#installation) the [the ready-to-use plugin](https://github.com/Kntnt/kntnt-global-styles/releases/latest/download/kntnt-global-styles.zip).\
> **For Developers:** [Build](#building-from-source-for-developers) the plugin yourself.

## Description

Kntnt Global Styles provides a powerful yet simple way to add custom CSS to your WordPress site directly through the block editor. The plugin creates a dedicated sidebar panel where you can write, edit, and save CSS that applies site-wide, with live preview functionality that shows your changes immediately in the editor.

Perfect for theme customization, quick design fixes, and adding custom styles without modifying theme files. All CSS is safely stored in the database and automatically generated as an optimized file for fast frontend loading.

### Key Features:

- **Block Editor Integration**: Native sidebar panel in the WordPress block editor
- **Live Preview**: See your CSS changes instantly while editing posts and pages
- **Dual Editor Modes**: Compact sidebar editor and full-screen modal for larger projects
- **Automatic Optimization**: CSS is minified and cached for optimal performance
- **Static File Performance**: CSS saved as optimized static file for fast loading and cache compatibility
- **Theme Independent**: Your custom styles remain active even if you switch themes
- **Developer Friendly**: Hooks and filters for extending functionality
- **Accessibility Ready**: Full keyboard navigation and screen reader support
- **Internationalization**: Ready for translation into any language

## Installation

1. [Download the plugin zip archive.](https://github.com/Kntnt/kntnt-global-styles/releases/latest/download/kntnt-global-styles.zip)
2. Go to WordPress admin panel → Plugins → Add New.
3. Click "Upload Plugin" and select the downloaded zip archive.
4. Activate the plugin.

## Usage

You can edit your global styles directly from within the block editor.

Click the brush icon in the editor's top-right toolbar to open the **Global Styles** sidebar. A panel titled **CSS Editor** will appear with a field where you can write your custom CSS.

If you prefer a larger editing window, click the **Larger Editor** button below the text area. This will open the editor in a full-size modal dialog.

When you click the **Save** button, your CSS is applied instantly to both the editor preview and your live site.

### Applying Your Styles

You can apply your custom CSS classes to any block using the standard WordPress method.

1.  Create a CSS class in the **Global Styles** editor. For example:
    ```css
    .highlight {
      background-color: #f0f8ff;
      border-left: 4px solid #0073aa;
      padding: 1rem;
    }
    ```
2.  Select the block you want to style in the editor.
3.  In the block settings sidebar on the right, open the **Advanced** section.
4.  In the **"Additional CSS class(es)"** field, type your class name without the leading dot (e.g., `highlight`).

The styles will now be applied to the selected block.

## CSS Annotations

The CSS Annotations feature allows your custom global styles to become discoverable by other plugins or themes. By adding special comments to your CSS, you can "register" your classes, making it possible for other developer tools to integrate with them seamlessly.

### Purpose

The primary goal is to bridge the gap between your custom CSS and the WordPress block editor. For instance, a custom block could use these annotations to automatically populate a dropdown menu in the Inspector, allowing users to select from a list of predefined styles (like `highlight-box`) without having to remember and type the class names manually.

This creates a more user-friendly and integrated experience, turning your static CSS classes into interactive options within the WordPress UI.

### How It Works

The plugin automatically parses your global CSS file for comments containing `@class` tags. When it finds these annotations, it triggers a WordPress action hook, `kntnt-global-styles-annotated-classes`, and passes an array of the parsed class names and their descriptions. Other plugins can then listen for this action to access your list of custom styles.

### Syntax

To make a class available, you need to define it within a standard CSS comment block (`/* ... */`). The syntax is as follows:

```css
/*
 * @class <class-name> | <description>
 */
```

* `@class`: This tag is required to identify the line as a class annotation.
* `<class-name>`: The name of your CSS class (e.g., `highlight-box`). This is required.
* `|`: A pipe character used to separate the class name from its description.
* `<description>`: An optional, human-readable description of what the class does (e.g., "A yellow highlight box for important content").

### Example

```css
/*
 * @class highlight-box | A yellow highlight box for important content
 * @class call-to-action | Styled button for call-to-action elements
 */
.highlight-box {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 1rem;
}

.call-to-action {
    background: #007cba;
    color: white;
    padding: 12px 24px;
    border-radius: 4px;
}
```

## Developer Hooks

The plugin provides several hooks for developers to extend and customize its functionality:

### Actions

**`kntnt-global-styles-saved`**
```php
add_action( 'kntnt-global-styles-saved', function( $css_content ) {
    // Triggered after CSS is successfully saved
    // $css_content contains the sanitized CSS
    error_log( 'Global styles updated: ' . strlen( $css_content ) . ' characters' );
} );
```

**`kntnt-global-styles-annotated-classes`**
```php
add_action( 'kntnt-global-styles-annotated-classes', function( $classes ) {
    // Triggered when CSS contains @class annotations
    // $classes is an array of parsed class definitions
    foreach ( $classes as $class ) {
        // $class['name'] - class name
        // $class['description'] - optional description
    }
} );
```

### Filters

**`kntnt-global-styles-minimize`**
```php
add_filter( 'kntnt-global-styles-minimize', function( $css ) {
    // Customize CSS minification process
    // Return minified CSS string
    return your_custom_minifier( $css );
} );
```

### Programmatic Access

**Get saved CSS:**
```php
$css = \Kntnt\Global_Styles\Plugin::get_option( 'css' );
```

**Check file paths:**
```php
$css_url = \Kntnt\Global_Styles\Plugin::get_css_url();
$css_path = \Kntnt\Global_Styles\Plugin::get_css_path();
```

## Building from Source (for Developers)

If you want to modify the block editor components or create a distribution package, you'll need to install dependencies and build the plugin.

### Development Setup

You need Node.js installed on your system. Download the LTS version from [https://nodejs.org/](https://nodejs.org/)

You can verify the installation by running:

```bash
node -v
npm -v
```

### Install Dependencies

Navigate to the plugin's root directory and run:

```bash
npm install
```

### Available Scripts

The plugin provides several npm scripts for different development tasks:

#### Development Scripts

**`npm start`**
- Starts development mode with live reload
- Builds JavaScript from `src/index.js` and watches for changes
- Automatically rebuilds when you modify source files
- Use this while actively developing the plugin

**`npm run build`**
- Creates development builds of the React components
- Builds from `src/index.js` to `js/index.js`
- Use this for testing builds without watching for changes

#### Production & Distribution

**`npm run dist`**
- Creates a complete distribution package ready for WordPress installation
- Runs production build with optimizations
- Creates a `dist/kntnt-global-styles/` directory with all plugin files
- Minifies CSS and JavaScript files for optimal performance
- Creates a `dist/kntnt-global-styles.zip` file ready for distribution
- **This is what you use to create the final plugin package**

#### Maintenance Scripts

**`npm run clean`**
- Removes all build files and distribution directories
- Cleans up `js/` and `dist/` folders
- Use this to start fresh or before committing code

**`npm run lint`**
- Checks JavaScript code quality and style
- Reports potential issues and coding standard violations
- Run this before committing changes

**`npm run format`**
- Automatically formats JavaScript code according to WordPress standards
- Fixes indentation, spacing, and other style issues
- Use this to ensure consistent code formatting

### Development Workflow

Here's the typical workflow for developing the plugin:

1. **Start development:**
   ```bash
   npm start
   ```

2. **Make your changes** to files in the `src/` directory

3. **Check code quality:**
   ```bash
   npm run lint
   npm run format
   ```

4. **Test the build:**
   ```bash
   npm run build
   ```

5. **Create distribution package:**
   ```bash
   npm run dist
   ```

### Build Output

The build process creates files in different locations:

**Development builds (`npm start` or `npm run build`):**
- `js/index.js` - Compiled JavaScript
- `js/index.asset.php` - WordPress dependency information
- `js/index.css` - Compiled CSS (if any)

**Distribution builds (`npm run dist`):**
- `dist/kntnt-global-styles/` - Complete plugin directory with optimized files
- `dist/kntnt-global-styles.zip` - ZIP archive ready for WordPress installation

The distribution build excludes development files like `src/`, `node_modules/`, and `scripts/`, creating a clean package with only the files needed for the plugin to function.

### File Structure

```
kntnt-global-styles/
├── src/                # Source files for development
│   ├── index.js        # Main React component
│   ├── useCssEditor.js # Custom React hook
│   └── constants.js    # Constants and configuration
├── js/                 # Built files (generated)
│   ├── index.js        # Compiled JavaScript
│   ├── index.asset.php # WordPress dependencies
│   └── index.css       # Compiled CSS
├── classes/            # PHP classes
├── scripts/            # Build scripts
└── dist/               # Distribution files (generated)
```

### Updating Dependencies

To check if there are newer versions of the dependencies:

```bash
npm outdated
```

To update dependencies:

```bash
npm update
```

**Important:** After updating dependencies, you must run the build command again: `npm run build`.

### Troubleshooting

**Build fails with missing dependencies:**
```bash
npm install
```

**JavaScript errors in the browser:**
```bash
npm run lint
npm run format
npm run build
```

**Distribution ZIP doesn't work:**
- Make sure you ran `npm run dist` (not just `npm run build`)
- Check that all required files are included in the ZIP

**Development server not updating:**
- Stop `npm start` and restart it
- Clear browser cache
- Run `npm run clean` and then `npm start`

## Frequently Asked Questions

**Why use this plugin instead of the Customizer's "Additional CSS"?**

1. **Performance:** This plugin saves CSS to a static file, which can be cached by browsers and performance plugins. The Customizer often adds CSS inline, which can slow down page rendering and is harder to cache.
2. **Theme Independence:** Styles added with this plugin are not tied to your theme. They will persist even if you change themes, saving you from migrating your custom CSS.

**How does the plugin work internally?**

When you save, the CSS is written to the `wp_options` table in the database and simultaneously saved to a static file in your `wp-content/uploads` directory. The frontend of your site loads the static file, ensuring no database queries are needed to serve the styles. The editor loads its content from the database option, while the static file contains minified CSS for optimal performance.

## Questions & Answers

### How can I get help?

If you have questions about the plugin and cannot find an answer here, start by looking at issues and pull requests on our GitHub repository. If you still cannot find the answer, feel free to ask in the plugin's issue tracker on GitHub.

### How can I report a bug?

If you have found a potential bug, please report it on the plugin's issue tracker on GitHub.

### How can I contribute?

Contributions to the code or documentation are much appreciated.
If you are familiar with Git, please do a pull request.
If you are not familiar with Git, please create a new ticket on the plugin's issue tracker on GitHub.

## Changelog

## 1.0.0

- Initial release
- Complete rewrite and modernization of [Kntnt Style Editor](https://github.com/Kntnt/kntnt-style-editor)
- New React-based block editor integration
- Live preview functionality in editor
- Dual editor modes (sidebar and modal)
- Improved performance with static CSS file generation
- Enhanced accessibility and internationalization support
- Modern development workflow with webpack and npm scripts