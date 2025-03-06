// Check for production mode.
const isProduction = process.env.NODE_ENV === 'production';

// All plugins to use.
const plugins = [

    // Add vendor prefixes to CSS rules.
    require( 'autoprefixer' ),

    // Pack same CSS media query rules into one.
    require( 'css-mqpacker' )( {
        sort: true,
    } ),
];

// Use only for production build.
if ( isProduction ) {

    // Optimize and minify CSS.
    plugins.push(
        require( 'cssnano' )( {
            preset: [
                'default',
                {
                    discardComments: {
                        removeAll: true,
                    },
                },
            ],
        } )
    );
}

module.exports = { plugins };
