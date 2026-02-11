const fs = require('fs');
const path = require('path');
const sass = require('sass');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const CleanCSS = require('clean-css');
const Terser = require('terser');

// Define paths
const srcDir = path.join(__dirname, '..', 'src');
const distDir = path.join(__dirname, '..', 'dist');

// Ensure dist directory exists
if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir, { recursive: true });
}

// Check if required files exist
const requiredFiles = [
    path.join(srcDir, 'shiki.scss'),
    path.join(srcDir, 'shiki.js'),
    path.join(srcDir, 'shiki.php')
];
for (const file of requiredFiles) {
    if (!fs.existsSync(file)) {
        console.error(`Required file not found: ${file}`);
        process.exit(1);
    }
}

// Compile and minify SCSS to CSS
async function compileSCSS() {
    const scssFile = path.join(srcDir, 'shiki.scss');

    // 1. SCSS → CSS
    const result = sass.compile(scssFile, {
        style: 'expanded',
        sourceMap: false,
        quietDeps: true
    });

    let css = result.css;

    // 2. autoprefix
    const postcssResult = await postcss([
        autoprefixer({
            overrideBrowserslist: [
                '>= 1%',
                'last 2 versions',
                'not dead'
            ]
        })
    ]).process(css, { from: undefined });

    css = postcssResult.css;

    // 3. minify
    return new CleanCSS({
        level: {
            1: { specialComments: 0 },
            2: { restructureRules: false }
        }
    }).minify(css).styles;
}


// Read and minify JavaScript file
async function minifyJS(jsContent) {
    try {
        const result = await Terser.minify(jsContent, {
            ecma: 2022,
            module: true,
            sourceMap: false,
            compress: {
                drop_console: true,
                drop_debugger: true,
                passes: 2,
            },
            mangle: true,
            format: {
                comments: false
            }
        });

        if (!result.code) {
            throw new Error('Terser returned no code');
        }

        return result.code;
    } catch (error) {
        console.error('Error minifying JS:', error);
        throw error;
    }
}


// Main build function
async function build() {
    try {
        console.log('Starting build process...');

        // Step 1: Compile and minify SCSS to CSS
        console.log('Compiling SCSS...');
        const minifiedCSS = await compileSCSS();
        console.log('SCSS compiled and minified successfully');

        // Step 2: Read and modify shiki.js
        console.log('Updating shiki.js...');
        const jsContent = fs.readFileSync(path.join(srcDir, 'shiki.js'), 'utf8');
        let modifiedJsContent = jsContent.replace(/{css}/g, minifiedCSS);

        // Step 3: Minify shiki.js content
        console.log('Minifying shiki.js...');
        const minifiedJS = await minifyJS(modifiedJsContent, {
            compress: {
                drop_console: true,
                drop_debugger: true
            },
            mangle: true
        });

        // Step 4: Read and modify shiki.inc.php
        console.log('Updating shiki.inc.php...');
        const phpContent = fs.readFileSync(path.join(srcDir, 'shiki.php'), 'utf8');
        let modifiedPhpContent = phpContent.replace(/{js}/g, minifiedJS);
        fs.writeFileSync(path.join(distDir, 'shiki.inc.php'), modifiedPhpContent);
        console.log('shiki.inc.php updated in dist directory');

        console.log('Build completed successfully!');
        console.log('Files created in dist directory:');
        console.log('- shiki.inc.php');

    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
}

// Run build
build();