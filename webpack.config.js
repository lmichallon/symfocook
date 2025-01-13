// webpack.config.js
const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js') // Point d'entrée principal
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .enableSassLoader() // Si vous utilisez Sass
    .addAliases({
        '@symfony/stimulus-bridge/controllers.json': './assets/controllers.json'
    })
;

module.exports = Encore.getWebpackConfig();
