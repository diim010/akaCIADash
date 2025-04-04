const mix = require('laravel-mix');
const fs = require('fs');
const path = require('path');

// Get all .scss files from the assets/scss directory
const scssFiles = fs.readdirSync('assets/scss')
    .filter(file => file.endsWith('.scss'))
    .map(file => path.join('assets/scss', file));

// Process each file individually
scssFiles.forEach(file => {
    const filename = path.basename(file, '.scss');
    mix.sass(file, 'assets/css')
       .minify(`assets/css/${filename}.css`);
});