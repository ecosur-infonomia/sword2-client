module.exports = function (grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        clean: ["dist"],

        phplint: {
            source: ['src/*.php'],
            test: ['test/*.php']
        },

        phpunit: {
            classes: {
                dir: 'test/'
            },
            options: {
                bin: 'src/vendor/phpunit/phpunit/phpunit.php',
                bootstrap: 'phpunit.php',
                colors: true
            }
        }
    });

    // tasks
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks("grunt-phplint");
    grunt.loadNpmTasks('grunt-phpunit');

    // Default task(s).
    grunt.registerTask('default', ['clean', 'phplint', 'phpunit']);
}