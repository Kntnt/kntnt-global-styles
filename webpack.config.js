/**
 * Webpack configuration for Kntnt Global Styles plugin.
 *
 * Extends the default WordPress Scripts webpack configuration to support
 * multiple entry points and custom CSS output directory. Generates both
 * the main editor bundle and the live preview script as separate files.
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config')
const path = require('path')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')

module.exports = {
  // Inherit all default WordPress Scripts configuration
  ...defaultConfig,

  // Define multiple entry points for different script bundles
  entry: {
    index: './src/index.js',          // Main editor functionality
    'live-preview': './src/live-preview.js', // Live preview CSS injection
  },

  // Configure output settings for generated files
  output: {
    ...defaultConfig.output,
    path: path.resolve(process.cwd(), 'js'), // Output JavaScript to js/ directory
    filename: '[name].js',                   // Use entry name for output filename
  },

  // Configure plugins with custom CSS extraction
  plugins: [
    // Remove the default MiniCssExtractPlugin instance to avoid conflicts
    ...defaultConfig.plugins.filter(
      (plugin) => plugin.constructor.name !== 'MiniCssExtractPlugin'
    ),
    // Add custom MiniCssExtractPlugin instance to output CSS to css/ directory
    new MiniCssExtractPlugin({
      filename: '../css/[name].css', // Output CSS files to css/ directory
    }),
  ],
}