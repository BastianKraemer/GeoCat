module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    propertiesToJSON: {
        main: {
            src: 'src/locale/de.properties',
            dest: 'src/locale'
        }
    }
  });

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-properties-to-json');

  // Default task(s).
  grunt.registerTask('default', ['propertiesToJSON']);

};
