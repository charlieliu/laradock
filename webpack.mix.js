const mix = require('laravel-mix');

mix.styles([
    'resources/css/app.css',
    'resources/css/btn.css',
], 'public/css/app.css');

mix.minify('public/css/app.css');

// mix.js([
//     'resources/js/app.js',
//     'resources/js/bootstrap.js'
// ], 'public/js/app.js');

// mix.minify('public/js/app.js');

// mix.js('resources/js/app.js', 'public/js')
//     .postCss('resources/css/app.css', 'public/css', [
//         //
//     ]);
