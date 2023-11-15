/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 * Copyright (c) 2018.  Orlin Vakarelov
 */


const {requires} = require("grunt/lib/grunt/config");
module.exports = function (grunt) {

    require('load-grunt-tasks')(grunt);
    // JSON.minify = require('node-json-minify');
    // const fs = require('fs');

    const version = '1.1.1'

    const key_url = 'http://sandbox.pri/IADesigner/php-vector-graphic/src/IA_Designer/Com/keys.php?version=' + version;

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        // concat: {
        //     options: {
        //         separator: ';',
        //     },
        // },

        curl: {
            keys: {
                src: key_url,
                dest: '_key.txt'
            }
        },

        replace: {
            dist: {
                src: ['js/iaPresenter_loader.js'],
                overwrite: true,
                replacements: [
                    {
                        from: /\/\*\*PK\*\/"[a-zA-Z0-9=+\/]*"\/\*\*PK\*\//,
                        to: function () {
                            let text = grunt.file.read('_key.txt');
                            return '/**PK*/"' + text + '"/**PK*/';
                        }
                    }
                ]
            }
        },

        uglify: {
            my_target: {
                options: {
                    // the banner is inserted at the top of the output
                    banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> \nCopyright © 2023 Information Aesthetics. All rights reserved.\n' +
                        'This work is licensed under the GPL2, V2/ license.*/\n',
                    mangle: true,
                    compress: {
                        dead_code: true,
                        unused: true,
                        drop_console: true, //must be true
                    },
                    sourceMap: false,
                },

                // dist: {
                files: {
                    'js/iamg-block.min.js': 'js/iamg-block.js',
                    'js/boot_iamg.min.js': 'js/boot_iamg.js',
                    'js/boot_iamg_cache.min.js': 'js/boot_iamg_cache.js',
                    'js/boot_iamg_post_admin.min.js': 'js/boot_iamg_post_admin.js',
                    'js/iamg_helper.min.js': 'js/iamg_helper.js',
                    'js/iaPresenter_loader.min.js': 'js/iaPresenter_loader.js',
                    'js/presentation_expander.min.js': 'js/presentation_expander.js',
                    'js/presentation_full.min.js': 'js/presentation_full.js',
                    'js/parent_style_setter.min.js': 'js/parent_style_setter.js',
                },
            },

        },

        cssmin: {
            target: {
                files: {
                    'css/ia_general.min.css': ['css/ia_designer_general.css', 'css/ia_presenter_general.css'],
                    'css/ia_presenter_admin.min.css': ['css/ia_presenter_admin.css'],
                },
            },
        },

        clean: {
            options: {
                force: true
            },
            dest: ['C:/Users/okv/Desktop/IAMagicGalleries']
        },
        copy: {
            main: {
                expand: true,
                src: ['**/*', '!**/*.psd', '!resources/**', '!node_modules/**', '!Gruntfile.js', '!package.json', '!package-lock.json', '!composer.json', '!composer.lock', '!log.txt', '!notes/**', '!**/*.js.map', '!**/*.back'],
                dest: 'C:/Users/okv/Desktop/IAMagicGalleries/',
                filter: function (filepath) {
                    // Use a filter function to exclude files and directories starting with a dot or underscore
                    let test = !/^_.*|.*\\_.*/.test(filepath) && !/^\..*|.*\\\..*/.test(filepath)
                        && !filepath.startsWith('resources\\iamg');
                    // console.log(filepath, test);
                    return test;
                },
            },
            resources: {
                expand: false,
                src: ['resources/index.php',
                    'resources/process.php',
                    'resources/loading.gif',
                    'resources/iamg_editor_included_images_controller.json',
                    'resources/iamg_editor_libraries.json',
                    'resources/buttons_presenter.json',
                    'resources/controller_presenter_simple.json',],
                dest: 'C:/Users/okv/Desktop/IAMagicGalleries/',
            },
        },
        compress: {
            zip: {
                options: {
                    archive: 'C:/Users/okv/Desktop/IAMagicGalleries.zip',
                },
                files: [
                    {
                        cwd: 'C:/Users/okv/Desktop/IAMagicGalleries',
                        src: ['**'],
                        dest: 'IAMagicGalleries/',
                    }
                ]
            }
        }


    });

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify-es');
    // grunt.loadNpmTasks('grunt-contrib-uglify');
    // grunt.loadNpmTasks('grunt-babel');
    // grunt.loadNpmTasks('grunt-external-daemon');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-htmlmin');
    grunt.loadNpmTasks('grunt-json-minify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-curl');
    grunt.loadNpmTasks('grunt-text-replace');


    grunt.registerTask('default', ['curl', 'replace', 'uglify', 'cssmin']);

    grunt.registerTask('produce', ['clean', 'copy', 'compress']);

    grunt.registerTask('all', ['curl', 'replace', 'uglify', 'cssmin', 'clean', 'copy', 'compress']);

};
