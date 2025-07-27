#!/usr/bin/env node
const fs = require('fs-extra')
const path = require('path')
const { minify } = require('terser')
const archiver = require('archiver')

const PLUGIN_NAME = 'kntnt-global-styles'
const DIST_DIR = 'dist'
const PLUGIN_DIST_DIR = path.join(DIST_DIR, PLUGIN_NAME)

// Files and directories to copy
const COPY_ITEMS = [
  'LICENSE',
  'README.md',
  'autoloader.php',
  'classes',
  'js',  // Back to 'js' instead of 'build'
  'kntnt-global-styles.php',
  'uninstall.php'
]

async function build () {
  console.log('Starting build process...')

  try {
    // Step 1: Clean dist directory
    console.log('Cleaning dist directory...')
    await fs.remove(DIST_DIR)
    await fs.ensureDir(PLUGIN_DIST_DIR)

    // Step 2: Copy files and directories
    console.log('Copying files...')
    for (const item of COPY_ITEMS) {
      const srcPath = path.join('.', item)
      const destPath = path.join(PLUGIN_DIST_DIR, item)

      if (await fs.pathExists(srcPath)) {
        await fs.copy(srcPath, destPath)
        console.log(`   Copied ${item}`)
      } else {
        console.log(`   Skipped ${item} (not found)`)
      }
    }

    // Step 3: Minify JavaScript
    console.log('Minifying JavaScript...')
    const jsFilePath = path.join(PLUGIN_DIST_DIR, 'js', 'index.js')
    if (await fs.pathExists(jsFilePath)) {
      const jsContent = await fs.readFile(jsFilePath, 'utf8')
      const minified = await minify(jsContent, {
        compress: {
          drop_console: true, // Remove console in production
          drop_debugger: true,
          passes: 2 // Multiple passes for better compression
        },
        mangle: {
          toplevel: true // Mangle top-level variable names
        },
        format: {
          comments: false // Remove comments
        },
        sourceMap: false // No source maps in production
      })

      if (minified.error) {
        throw new Error(`JavaScript minification failed: ${minified.error}`)
      }

      await fs.writeFile(jsFilePath, minified.code)
      console.log('   JavaScript minified')
    }

    // Step 4: Minify CSS if exists
    console.log('Checking for CSS files...')
    const cssFilePath = path.join(PLUGIN_DIST_DIR, 'js', 'index.css')
    if (await fs.pathExists(cssFilePath)) {
      const cssContent = await fs.readFile(cssFilePath, 'utf8')
      // Simple CSS minification
      const minifiedCss = cssContent
        .replace(/\/\*[\s\S]*?\*\//g, '') // Remove comments
        .replace(/\s+/g, ' ') // Collapse whitespace
        .replace(/;\s*}/g, '}') // Remove unnecessary semicolons
        .trim()

      await fs.writeFile(cssFilePath, minifiedCss)
      console.log('   CSS minified')
    }

    // Step 5: Create ZIP file
    console.log('Creating ZIP archive...')
    const zipPath = path.join(DIST_DIR, `${PLUGIN_NAME}.zip`)

    await new Promise((resolve, reject) => {
      const output = fs.createWriteStream(zipPath)
      const archive = archiver('zip', {
        zlib: { level: 9 } // Maximum compression
      })

      output.on('close', () => {
        console.log(`   ZIP created: ${zipPath}`)
        resolve()
      })

      archive.on('error', (err) => {
        reject(err)
      })

      archive.pipe(output)
      archive.directory(PLUGIN_DIST_DIR, PLUGIN_NAME)
      archive.finalize()
    })

    // Show build summary
    const stats = await fs.stat(zipPath)
    const sizeMB = (stats.size / 1024 / 1024).toFixed(2)

    console.log('\nBuild completed successfully!')
    console.log(`Build summary:`)
    console.log(`   • Plugin directory: ${PLUGIN_DIST_DIR}`)
    console.log(`   • ZIP file: ${zipPath}`)
    console.log(`   • ZIP size: ${sizeMB} MB`)
    console.log(`   • Files included: ${COPY_ITEMS.join(', ')}`)

  } catch (error) {
    console.error('\nBuild failed:', error.message)
    process.exit(1)
  }
}

// Run the build
build()