/* eslint-disable comma-dangle */
const path = require( 'path' );
const webpack = require( 'webpack' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );
const ESLintPlugin = require('eslint-webpack-plugin');


// Check for production mode.
const isProduction = process.env.NODE_ENV === 'production';

// Plugin root folder.
const pluginPath = `${ path.resolve( __dirname ) }`;

// Plugin paths
const adminEntry = `${ pluginPath }/assets/scripts/admin.js`;
const output = `${ pluginPath }/assets/dist`;

// All loaders to use on assets.
const allModules = {
    rules: [
        {
            test: /\.js$/,
            exclude: /node_modules/,
            use: {
                loader: 'babel-loader',
                options: {
                // Do not use the .babelrc configuration file.
                    babelrc: false,

                    // The loader will cache the results of the loader in node_modules/.cache/babel-loader.
                    cacheDirectory: true,

                    // Enable latest JavaScript features.
                    presets: [ '@babel/preset-env' ],

                    plugins: [
                        '@babel/plugin-syntax-dynamic-import', // Enable dynamic imports.
                        '@babel/plugin-syntax-top-level-await', // Enable await functions on js context top level (in addition to async functions)
                    ],
                },
            },
        },
        {
            test: /\.scss$/,
            use: [
                MiniCssExtractPlugin.loader,
                {
                    loader: 'css-loader',
                    options: {
                        sourceMap: true,
                    },
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        sourceMap: true,
                    },
                },
                {
                    loader: 'sass-loader',
                    options: {
                        sourceMap: true,
                    },
                },
            ],
        },
    ]
};

// All optimizations to use.
const allOptimizations = {
    runtimeChunk: false,
    splitChunks: {
        cacheGroups: {
            vendor: {
                test: /[\\/]node_modules[\\/]/,
                name: 'vendor',
                chunks: 'all',
            },
        },
    },
};

// All plugins to use.
const allPlugins = [
    new ESLintPlugin({
        extensions: ['js'],
        exclude: 'node_modules',
        context: pluginPath,
        overrideConfigFile: `${pluginPath}/.eslintrc.json`,
        fix: false,
        failOnWarning: false,
        failOnError: false,
    }),

    // Convert JS to CSS.
    new MiniCssExtractPlugin( {
        filename: '[name].css',
        chunkFilename: '[name]-[contenthash].css',
    } ),

    // Provide jQuery instance for all modules.
    new webpack.ProvidePlugin( {
        jQuery: 'jquery',
    } ),
];

allOptimizations.minimizer = [

    // Optimize for production build.
    new TerserPlugin( {
        parallel: true,
        terserOptions: {
            output: {
                comments: false,
            },
            compress: {
                warnings: false,
                drop_console: true, // eslint-disable-line camelcase
            },
        },
    } ),
];

const experiments = {
    topLevelAwait: true,
};

module.exports = [
    {
        mode: 'development',

        entry: {
            admin: [ adminEntry ]
        },

        output: {
            path: output,
            filename: '[name].js'
        },

        module: allModules,

        optimization: allOptimizations,

        plugins: allPlugins,

        experiments,

        externals: {

            // Set jQuery to be an external resource.
            jquery: 'jQuery'
        },

        // Disable source maps for production build.
        devtool: isProduction ? undefined : 'source-map',
    }
];
