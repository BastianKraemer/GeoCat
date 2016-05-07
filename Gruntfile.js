module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		propertiesToJSON: {
			main: {
				src: 'src/locale/*',
				dest: 'src/locale'
			}
		},

		clean: {
			build: {
				src: ["dest/*"]
			}
		},

		uglify: {
			options: {
				mangle: false
			},
			GeoCatJavaScriptFiles: {
				files: {
					'dest/js/geocat.min.js':
						[
							'src/js/GeoCat.js',
							'src/js/etc/*.js',
							'src/js/Substance.js',
							'src/js/PagePrototype.js',
							'src/js/Dialogs.js',
							'src/js/ScrollLoader.js',
							'src/lib/jquery.minicolors.min.js'
						],

					'dest/js/gpscat.min.js':
						[
							'src/js/gps/*.js'
						],

					'dest/js/controller.min.js':
						[
							'src/js/gpsnavigator/GPSNavigationController.js',
							'src/js/controller/*.js',
							'src/js/Account.js',
							'src/js/challenges/*.js',
							'src/js/etc/SafariFix.js'
						]
				}
			}
		},

		copy: {
			main: {
				cwd: 'src/',
				src: [	'app/**',  'query/**', 'views/**', 'config/config.php', 'locale/*.json', 'locale/sites/*',
						'img/tile/*', 'img/etc/*', 'lib/jquery_package.min.js', 'lib/ol.js'],
				dest: 'dest/',
				expand: true
			},
		},

		processhtml: {
			main: {
				files: {
					'dest/index.php': ['src/index.php']
				}
			},
		},

		cssmin: {
			options: {
				shorthandCompacting: false,
				roundingPrecision: -1
			},
			target: {
				files: {
					'dest/css/geocat.min.css':
						[
							'src/css/style.css',
							'src/css/animations.css',
							'src/css/substance.css',
							'src/css/geocat-images.css',
							'src/css/tiles.css',
							'src/css/theme.css',
							'src/css/ol.css'
						],
					'dest/css/jquery_package.min.css':
						[
							'src/css/jquery.mobile-1.4.5.css',
							'src/css/jquery.minicolors.css',
							'src/css/listview-grid.css'
						]
				}
			}
		},

		jsdoc: {
			main : {
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

	grunt.loadNpmTasks('grunt-properties-to-json');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-processhtml');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-jsdoc');
	grunt.loadNpmTasks('grunt-phpdoc');

	grunt.registerTask('default', ['propertiesToJSON']);
	grunt.registerTask('translate', ['propertiesToJSON']);
	grunt.registerTask('doc', ['jsdoc', 'phpdoc']);
	grunt.registerTask('build', ['propertiesToJSON', 'clean', 'copy', 'processhtml', 'cssmin', 'uglify']);
};
