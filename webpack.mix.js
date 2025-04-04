 webpack.mix.jsconst mix = require('laravel-mix');


 mix.sass('assets/scss/app.scss', 'assets/css')
    .minify('public/css/app.css');