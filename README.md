# Kntnt Global Styles

[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Requires PHP: 8.3+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Requires WordPress: 6.8+](https://img.shields.io/badge/WordPress-6.8+-blue.svg)](https://wordpress.org)

WordPress plugin that manages a global CSS file editable in the block editor.

> [!NOTE]
> **For WordPress Users:** [Install](#installation) the [the ready-to-use plugin](https://github.com/Kntnt/kntnt-global-styles/releases/latest/download/kntnt-global-styles.zip).\
> **For Developers:** [Build](#building-from-source-for-developers) the plugin yourself.

## Description

Modern WordPress site builders like Oxygen and Bricks have popularized a "class-first" workflow. This approach allows developers and designers to create a system of reusable CSS classes (often called "global styles" or "utility classes") that can be applied to elements to ensure design consistency and dramatically speed up development. This powerful functionality, however, is typically confined to the ecosystem of a specific builder or product.

Kntnt Global Styles is a lightweight plugin designed to bring this same, proven methodology to the native WordPress block editor. Its purpose is to provide a centralized system for managing a global stylesheet and applying its classes to *any* block—whether it's a core WordPress block or one added by a third-party plugin. It achieves this by replacing the default "Additional CSS class(es)" field with a more capable and user-friendly interface.

## Why use this plugin?

### For designers and developers

- **Efficiency**: Create reusable CSS classes once and apply them anywhere in your site. No more copying and pasting the same styles or hunting through theme files.
- **Consistency**: Maintain design consistency across your entire site with a centralized style system that works with any block or theme.
- **Workflow**: Adopt the proven "utility-first" approach used by modern frameworks like Tailwind CSS, but within the familiar WordPress block editor.

### For site performance

- **Speed**: CSS is served as a static, cached file rather than inline styles, improving page load times and reducing server overhead.
- **Optimization**: Automatic minification ensures your styles are delivered efficiently without manual optimization.

### For site management

- **Theme independence**: Your styles survive theme changes, updates, and switching between themes—no more lost customizations.
- **Future-proof**: Works with any WordPress theme and all block types, including third-party plugins and future WordPress updates.
- **No vendor lock-in**: Unlike page builders, your styles remain accessible as standard CSS that works everywhere.
- **Accessibility**: Built with accessibility in mind, supporting keyboard navigation and screen readers.

### Compared to alternatives

- **vs. Customizer's Additional CSS**: Better performance, theme independence, and organized class management
- **vs. Page builders**: Standard CSS without proprietary markup, works with any theme, lighter weight
- **vs. Manual theme editing**: No risk of losing changes during theme updates, works across multiple themes

## Core functionality

The plugin introduces two core functionalities to the block editor:

* An enhanced input field on every block for applying classes.
* A central CSS editor for managing a single, site-wide stylesheet.

While designed to work in tandem—defining styles in the central editor and applying them via the input field—these two features also offer significant flexibility by working independently.

The class input field is not restricted to suggested classes; you can add any CSS class name directly, just as you would with the standard WordPress "Additional CSS class(es)" field. The interface works with any block—whether it's a core WordPress block, one added by a third-party plugin, or custom blocks. Conversely, you have full control over which classes within the global stylesheet appear as suggestions by using the @hint annotation. For advanced integration, developers can also use a filter to programmatically add or remove classes from the suggestion list.

All CSS managed by the plugin is stored in the database and also saved as a minified, static .css file for optimal performance. This file is loaded in the editor and on the front end, ensuring styles are applied consistently.

The plugin's functionality is theme-independent. The defined global styles will persist and remain active even if the site's theme is changed.

## Installation

1. [Download the plugin zip archive.](https://github.com/Kntnt/kntnt-global-styles/releases/latest/download/kntnt-global-styles.zip)
2. Go to WordPress admin panel → Plugins → Add New.
3. Click "Upload Plugin" and select the downloaded zip archive.
4. Activate the plugin.

## Usage

### 1\. Applying Classes to a Block

When a block is selected in the editor, the plugin adds a new *Global Styles* panel to the settings sidebar (also known as the block inspector).

Add a class by selecting from the drop-down list that appears when you click the arrow to the right, or start typing the name of a class in the list. You can also type the full name of any class and press Enter or Tab to finish.

Remove a class by clicking on the `×` after the class name.

### 2\. The Global Style Editor

All global styles can be edited in the Global Style Editor. There are four ways to open it:

1. With a block selected, click the *Edit Global Styles* link in the *Global Styles* panel of the inspector.
2. Select *Edit Global Styles* i the *More tools & options menu* (displayed when you click on the three vertical dots in the upper right corner)
3. Use the keyboard shortcut <kbd>Command</kbd> + <kbd>Shift</kbd> + <kbd>G</kbd> on Mac and <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>G</kbd> on Linux and Windows.
4. Open the command palette (<kbd>Command</kbd> + <kbd>K</kbd> on Mac and <kbd>Ctrl</kbd> + <kbd>K</kbd> on Linux and Windows) and start type one of the words ”global”, ”style”, and ”editor” and select *Open Global Style Editor*.

To control which classes appear in the dropdown list, a specific comment annotation, `@hint`, must be used in the CSS code. The annotation must be on its own line within a comment. The start of a comment (`/*`), the continuation of a comment (`*`), and the end of a comment (`*/`), as well as surrounding spaces, are allowed on the line. After `@hint`, specify the name of a class. Optionally, you can add a vertical bar (`|`), followed by a description that will appear next to the class name in the drop-down list. Leading and trailing spaces are trimmed.

You can place comments with annotations anywhere. However, we recommend collecting them all in an introductory comment or placing them next to the relevant style rule.

Any class with a valid `@hint` annotation becomes immediately available for selection in the *Global Styles* input field for all blocks when the CSS in the editor modal is saved. The live, front-end version of the stylesheet updates when the post or page is saved.

Classes without this annotation will still work if applied manually but will not be suggested in the list.

Example:

```css
/**
 * @hint outline
 * @hint bold | Emphasizes in various ways
 */

.outline {
  outline: 1px solid brown;
}

.outline.bold {
  outline: 4px solid red;
}

p.bold,
.bold:not(.outline) p {
  font-weight: bold;
}

/* @hint emphasize */
p.emphasize,
.emphasize p {
  font-style: italic;
}

/* This class is globally available, but it will not appear in the drop-down list. */
p {
  color: blue;
}
```

For those of you who are more nerdy, here is the regular expression used to find the annotations:

```php
^\s*\/?\*+\s@hint\s+(?<name>\S+)\s*(?:\|\s*(?<description>.*?)\s*)?(?:\*\/.*)?$
```

## How does the plugin work?

The plugin operates through a dual-storage system that balances editing flexibility with performance:

### In the WordPress admin

When you open the CSS editor modal, you're editing the master stylesheet stored in your WordPress database. This raw CSS includes all your comments, formatting, and `@hint` annotations that make classes available in the dropdown selector.

### On your website

When visitors load your site, they receive a separate, optimized static CSS file saved in your `/wp-content/uploads/` directory. This file is automatically minified (comments removed, whitespace reduced) for faster loading and is served directly by your web server—no database queries needed.

### The synchronization process

1. **Edit**: You write CSS in the modal editor, including `@hint` annotations for dropdown suggestions
2. **Save**: The plugin saves your complete CSS to the database and simultaneously creates a minified version as a static file
3. **Load**: The block editor reads from the database to populate class suggestions, while your website serves the optimized static file to visitors
4. **Cache**: Since it's a static file, browsers and caching plugins can optimize delivery automatically

#### Class suggestion system

The plugin scans your CSS for special `@hint` comments (like `/* @hint button | Primary button style */`) and makes these classes available in an intelligent dropdown when editing blocks. You can still manually add any class name—the suggestions just make common classes faster to apply.

This architecture ensures you get the best of both worlds: a rich editing experience with all your documentation and comments preserved, plus optimal front-end performance with cached, minified CSS delivery.

## For developers

### Filters

The plugin provides several filters for developers to extend and customize its functionality:

#### `kntnt-global-styles-hints`

After the global stylesheet has been retrieved from the database and parsed for the `@hint` annotation, the filter `kntnt-global-styles-hints` is called with an associative array where the keys are the class names and the values are the corresponding descriptions or empty strings if no description has been specified. The filter should return an array with the same format. The returned array is used to populate the list of suggested classes in the class selector in the *Global Styles* panel.

Note that `@hint` annotations added in the modal style editor are not filtered until after the stylesheet has been saved and reloaded.

Example:

```php
add_filter( 'kntnt-global-styles-hints', function( $hints ) {
    return array_merge( $hints, your_custom_hints() );
} );
```

#### `kntnt-global-styles-pre-save`

CSS specified in the modal style editor is run through the `kntnt-global-styles-pre-save` filter before being saved to the database and to an external file. Use this filter to amend and sanitize the stylesheet before it is saved to the database, sent for minification, and saved to file.

Example:

```php
add_filter( 'kntnt-global-styles-pre-save', function( $css ) {
    $css = your_amendments( $css );
    $css = your_sanitization( $css );
    return $css;
} );
```

#### `kntnt-global-styles-minimize`

The CSS entered in the modal style editor is minified after it is saved to the database but before it is saved as an external file to be loaded by the editor and on the front end. This plugin has a simple minifier that removes comments, the final semicolon in declaration blocks, and extra spaces. If you prefer to minify the CSS yourself or skip this step entirely, you can implement the filter `kntnt-global-styles-minimize`, which gets the stored CSS as its argument and should return the CSS to be saved to the external file.

Example:

```php
add_filter( 'kntnt-global-styles-minimize', function( $css ) {
    return your_custom_minifier( $css );
} );
```

### Programmatic access

The plugin provides several getters that can be used by external code.

#### `\Kntnt\Global_Styles\Plugin::get_css()`

This method returns the CSS stored in the database.

Example:

```php
$css = \Kntnt\Global_Styles\Plugin::get_css();
```

#### `\Kntnt\Global_Styles\Plugin::get_css_path()`

This method returns the path to the minified CSS file.

Example:

```php
$css_file_path = \Kntnt\Global_Styles\Plugin::get_css_path();
```

#### `\Kntnt\Global_Styles\Plugin::get_css_url()`

This method returns the URL of the minified CSS file.

Example:

```php
$css_file_url = \Kntnt\Global_Styles\Plugin::get_css_url();
```

## Building from source

If you want to modify the block editor components or create a distribution package, you'll need to install dependencies and build the plugin yourself.

### Development setup

You need Node.js installed on your system. Download the LTS version from [https://nodejs.org/](https://nodejs.org/).

Open a terminal and verify your installation by running:

```bash
node -v
npm -v
```

### Install dependencies

Download this plugin [repository](https://github.com/Kntnt/kntnt-global-styles). In a terminal, navigate to the plugin's root directory and run:

```bash
npm install
```

### Build the plugin

To compile the React components in `/src` into JavaScript and CSS files placed in `/js` and `/css` respectively, navigate to the plugin's root directory in a terminal and run:

```bash
npm run build
```

If the plugin root directory is located inside a WordPress plugins directory (e.g., `/wp-content/plugins`), the plugin is now ready to be activated.

Alternatively, you can use the following command, which automatically rebuilds the plugin whenever you make changes to any file. Stop the process by pressing <kbd>CTRL</kbd>+<kbd>C</kbd>.

```bash
npm start
```

### Update the translation template

To update the `/languages/kntnt-global-styles.pot` file with all translatable strings for plugin localization, navigate to the plugin's root directory in a terminal and run:

```bash
npm run makepot
```

### Create a distribution-ready plugin

To create a plugin zip file without the React source code and other build-related files, navigate to the plugin's root directory in a terminal and run:

```bash
npm run dist
```

The resulting `kntnt-global-styles.zip` file in `/dist` is now a complete and self-contained plugin that can be [installed in the usual way](#installation).

### Delete generated files

To delete all files generated by the build and dist scripts (i.e., the directories `/js`, `/css` and `/dist`, and their contents), navigate to the plugin's root directory in a terminal and run:

```bash
npm run clean
```

### Update Node packages

To see which packages can be updated, run:

```bash
npm outdated
```

To update packages to the latest versions allowed by the SemVer ranges in `package.json` (i.e., no major versions with breaking changes), run:

```bash
npm update
```

This command updates your `package-lock.json` and `node_modules`, but does not change `package.json`.

### Upgrade Node packages

To upgrade all packages to their latest available versions, including major ones:

**Step 1: Update `package.json`**

```bash
npx npm-check-updates -u
```

**Step 2: Install updated packages**

```bash
npm install
```

This workflow modifies `package.json` and is used for major upgrades, which may require code changes.

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

## 1.1.0

### New Features

- **CSS Class Selector**: Completely new interface that replaces WordPress default "Additional CSS class(es)" field with an intelligent dropdown selector
- **@hint Annotation System**: Use `@hint classname | description` in CSS comments to make classes available in the dropdown selector
- **Multiple Editor Access Methods**: Open the CSS editor via:
    - Keyboard shortcut: `Cmd+Shift+G` (Mac) / `Ctrl+Shift+G` (Windows/Linux)
    - Command Palette: Search for "Open Global Style Editor"
    - More menu: "Edit Global Styles" option
    - Block inspector: "Edit Global Styles" link
- **Draft System**: CSS changes are now previewed instantly in the editor, with permanent saving when the document is saved
- **Enhanced Live Preview**: Improved CSS injection system that works reliably across all editor iframes and contexts

### User Experience Improvements

- **Redesigned CSS Editor Modal**: Larger, more user-friendly interface optimized for CSS editing
- **Intelligent Class Suggestions**: Dropdown shows available classes with descriptions for easier selection
- **Better Error Handling**: React Error Boundary prevents editor crashes when plugin encounters errors
- **Improved Accessibility**: Enhanced keyboard navigation and screen reader support

### Developer Features

- **New Filters**:
    - `kntnt-global-styles-hints`: Modify available CSS class hints programmatically
    - `kntnt-global-styles-pre-save`: Process CSS before saving to database
- **Enhanced Architecture**: Better separation of concerns with specialized components
- **Improved Build System**: More efficient webpack configuration with CSS extraction to dedicated directory

### Technical Improvements

- **Better Asset Organization**: CSS and JavaScript files now organized in separate directories (`css/`, `js/`)
- **Translation Ready**: Complete internationalization support with generated `.pot` file
- **Performance Optimizations**: More efficient CSS injection and file handling
- **Code Quality**: Enhanced error handling, better TypeScript-like patterns with enums

### Bug Fixes

- Fixed CSS injection timing issues in block editor iframes
- Improved reliability of live preview across different editor contexts
- Better handling of upload directory permissions and file system operations
- Enhanced nonce validation and security measures

### Internal Changes

- Refactored plugin architecture for better maintainability
- Consolidated CSS annotation parsing (moved from separate Integrator class to Editor class)
- Enhanced AJAX handling with standardized error codes
- Improved singleton pattern implementation with better error handling

## 1.0.0

- Initial release
- Complete rewrite and modernization of [Kntnt Style Editor](https://github.com/Kntnt/kntnt-style-editor)
- New React-based block editor integration
- Live preview functionality in editor
- Dual editor modes (sidebar and modal)
- Improved performance with static CSS file generation
- Enhanced accessibility and internationalization support
- Modern development workflow with webpack and npm scripts