
module.exports = function ( grunt ) {

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		wp_readme_to_markdown: {
			dist: {
				options: {
					screenshot_url: 'https://github.com/gagan0123/{plugin}/raw/master/assets/{screenshot}.png'
				},
				files: {
					'README.md': 'readme.txt'
				}
			}
		},
		watch: {
			grunt: {
				files: [ 'Gruntfile.js' ]
			},
			wp_readme_to_markdown: {
				files: [ 'readme.txt' ],
				tasks: [ 'wp_readme_to_markdown' ]
			}
		}
	} );

	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.registerTask( 'default', [
		'watch'
	] );

};