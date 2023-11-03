/*
 * Copyright (c) 2018.  Orlin Vakarelov
 */


const {requires} = require("grunt/lib/grunt/config");
module.exports = function (grunt) {

    require('load-grunt-tasks')(grunt);
    // JSON.minify = require('node-json-minify');
    // const fs = require('fs');


    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        // concat: {
        //     options: {
        //         separator: ';',
        //     },
        // },

        uglify: {
            my_target: {
                options: {
                    // the banner is inserted at the top of the output
                    banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> \nCopyright: Orlin Vakarelov - Information Aesthetics, all rights reserved */\n',
                    mangle: true,
                    compress: {
                        dead_code: true,
                        unused: true,
                        drop_console: true, //must be true
                    },
                    sourceMap: true,
                },

                // dist: {
                files: {
                    'js/iamg-block.min.js': 'js/iamg-block.js',
                    'js/boot_iamg.min.js': 'js/boot_iamg.js',
                    'js/boot_iamg_cache.min.js': 'js/boot_iamg_cache.js',
                    'js/iaPresenter_loader.min.js': 'js/iaPresenter_loader.js',
                    'js/presentation_expander.min.js': 'js/presentation_expander.js',
                    'js/presentation_full.min.js': 'js/presentation_full.js',
                    'js/iamg_helper.min.js': 'js/iamg_helper.js',
                },

                // }
            },
        },
        // copy: {
        //     main: {
        //         files: [
        //             // includes files within path
        //             // {
        //             //     expand: true,
        //             //     cwd: '../homepage/docs/',
        //             //     src: '**',
        //             //     dest: '../ia_home/docs',
        //             //
        //             // },
        //         ],
        //     },
        // },
    });

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify-es');
    // grunt.loadNpmTasks('grunt-babel');
    // grunt.loadNpmTasks('grunt-external-daemon');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-htmlmin');
    grunt.loadNpmTasks('grunt-json-minify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');

    grunt.registerTask('default', [
        // 'concat',
        'uglify']);



};
