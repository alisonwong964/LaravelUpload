const mix = require('laravel-mix');

mix.js('resources/js/formHandler.js', 'public/js')
   .js('resources/js/uploadBox.js', 'public/js')
   .css('resources/css/style.css', 'public/css');
