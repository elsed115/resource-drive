const mix = require('laravel-mix');

mix.setPublicPath('dist')
    .js('resources/js/tool.js', 'js')
    .vue()
    .css('resources/css/tool.css', 'css')
    .webpackConfig({
        resolve: {
            alias: {
                'vue$': 'vue/dist/vue.esm.js'
            }
        }
    });
