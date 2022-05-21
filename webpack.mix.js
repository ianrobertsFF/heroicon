let mix = require('laravel-mix');
let tailwindcss = require('tailwindcss')
let path = require('path')
let postcssImport = require('postcss-import')

require('./nova.mix')

mix
  .setPublicPath('dist')
  .js('resources/js/field.js', 'js')
  .vue({ version: 3 })
  .postCss('./resources/css/field.css', 'dist/css/', [postcssImport(), tailwindcss('tailwind.config.js'),])
  .nova('formfeed/fontawesome')