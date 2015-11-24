module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    propertiesToJSON: {
        main: {
            src: 'src/locale/de.properties',
            dest: 'src/locale'
        }
    },

    jsdoc: {
        dist : {
            src: ['src/js/*.js'],
            dest: 'doc'
        }
    }
  });

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-properties-to-json');
  grunt.loadNpmTasks('grunt-jsdoc');

  // Default task(s).
  grunt.registerTask('default', ['propertiesToJSON']);
  grunt.registerTask('translate', ['propertiesToJSON']);
  grunt.registerTask('doc', ['jsdoc']);
  grunt.registerTask('build', ['jsdoc', 'propertiesToJSON']);

};
