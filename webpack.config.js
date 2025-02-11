/* eslint-disable comma-dangle */
const path = require( 'path' );
const webpack = require( 'webpack' );
const CleanWebpackPlugin = require( 'clean-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );

// Plugin root folder.
const pluginPath = `${ path.resolve( __dirname ) }`;

// Plugin paths
const adminEntry = `${ pluginPath }/assets/scripts/admin.js`;
const output = `${ pluginPath }/assets/dist`;

// All loaders to use on assets.
const allModules = {
    rules: [
        {
            enforce: 'pre',
            test: /\.js$/,
            exclude: /node_modules/,
            use: {
                loader: 'eslint-loader',
                options: {
                    configFile: `${ pluginPath }/.eslintrc.json`,
                    fix: false,
                    failOnWarning: false,
                    failonError: true
                }
            }
        },
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

                    // Enable dynamic imports.
                    plugins: [ '@babel/plugin-syntax-dynamic-import' ]
                }
            }
        },
        {
            test: /\.scss$/,
            use: [
                MiniCssExtractPlugin.loader,
                {
                    loader: 'css-loader',
                    options: {
                        sourceMap: true
                    }
                },
                {
                    loader: 'sass-loader',
                    options: {
                        sourceMap: true
                    }
                }
            ]
        }
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
                chunks: 'all'
            }
        }
    }
};

// All plugins to use.
const allPlugins = [

    // Convert JS to CSS.
    new MiniCssExtractPlugin( {
        filename: '[name].css'
    } ),

    // Provide jQuery instance for all modules.
    new webpack.ProvidePlugin( {
        jQuery: 'jquery'
    } )
];

allOptimizations.minimizer = [

    // Optimize for production build.
    new TerserPlugin( {
        cache: true,
        parallel: true,
        sourceMap: true,
        terserOptions: {
            output: {
                comments: false
            },
            compress: {
                warnings: false,
                drop_console: true // eslint-disable-line camelcase
            }
        }
    } )
];

// Delete distribution folder for production build.
allPlugins.push( new CleanWebpackPlugin() );

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

        externals: {

            // Set jQuery to be an external resource.
            jquery: 'jQuery'
        },
    }
];
