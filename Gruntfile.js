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
		
		clean: {
			build: {
				src: ["dest/*"]
			}
		},

		uglify: {
			options: {
				mangle: false
			},
			geocatJS: {
				files: {
					'dest/js/geocat.min.js':
						[
							'src/js/locale.js',
							'src/js/tools.js',
							'src/js/Uplink.js',
							'src/js/LocalCoordinateStore.js',
							'src/js/places/*.js'
						]
				}
			},
			gpscatJS: {
				files: {
					'dest/js/gpscat.min.js': ['src/js/geotools.js', 'src/js/gpsnavigator/*.js']
				}
			},
		},

		copy: {
			main: {
				cwd: 'src/',
				src: ['app/**', 'config/config.php', 'css/**', 'js/**', 'lib/*.min.js', 'locale/*.json', 'query/**', 'sites/**', 'index.php'],
				dest: 'dest/',
				expand: true,
				options: {
					process: function (content, srcpath) {
						// Check if the file has a ".html" or ".php" extenson
						if(new RegExp(".*\.[html|php]$").test(srcpath)){
							var regEx = /<!-- <## (.*\.js) ##> --\>[\s\S]*?<!-- <\/## (.*\.js) ##> -->/;
							while(true){
								var match = regEx.exec(content);
								if(match != null){
									if(match[1] != match[2]){
										console.log("WARNING: Replacement missmatch in file '" + srcpath + "'. Assuming '" + match[1] + "' as desired replacement.");
									}
									var replaceRegEx = new RegExp(match[0],"g");
									content = content.replace(replaceRegEx, "<script type=\"text/javascript\" src=\"" + match[1] + "\"><\/script>");
								}
								else{
									break;
								}
							}
						}
						return content;
					}
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

	// Load the plugin that provides the "uglify" task.
	grunt.loadNpmTasks('grunt-properties-to-json');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-jsdoc');
	grunt.loadNpmTasks('grunt-phpdoc');

	// Default task(s).
	grunt.registerTask('default', ['propertiesToJSON']);
	grunt.registerTask('translate', ['propertiesToJSON']);
	grunt.registerTask('doc', ['jsdoc', 'phpdoc']);
	grunt.registerTask('build', ['propertiesToJSON', 'clean', 'copy', 'uglify']);
};
