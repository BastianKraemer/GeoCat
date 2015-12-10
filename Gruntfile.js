module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		propertiesToJSON: {
			main: {
				src: 'src/locale/*',
				dest: 'src/locale'
			}
		},

		jsdoc: {
			dist : {
				src: ['src/js/**/*.js'],
				dest: 'doc',
				options: {
					verbose: true,
					package: 'package.json'
				}
			}
		},

		phpdoc: {
			main: {
				src: './src',
				dest: 'doc/backend',
				options: {
					verbose: true
				}
			}
		}
	});

	// Load the plugin that provides the "uglify" task.
	grunt.loadNpmTasks('grunt-properties-to-json');
	grunt.loadNpmTasks('grunt-jsdoc');
	grunt.loadNpmTasks('grunt-phpdoc');

	// Default task(s).
	grunt.registerTask('default', ['propertiesToJSON']);
	grunt.registerTask('translate', ['propertiesToJSON']);
	grunt.registerTask('doc', ['jsdoc', 'phpdoc']);
	grunt.registerTask('build', ['propertiesToJSON', 'doc']);

};
