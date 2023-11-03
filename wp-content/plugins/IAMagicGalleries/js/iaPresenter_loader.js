/*
 * Copyright (c) 2018.  Orlin Vakarelov
 */

/**
 * A single function that loads IA Designer system.
 * @param {function|string} script (1) a url to: js file starting the interface other custom operations, or
 *  a json file with basic setting to the interface.  -OR-
 *      (2) a js function to be executed after the system is loaded.
 * @param {string} script_type the type of the script: "js" (default) or "json"
 * @param {string} relative_script_path the (relative) path to the "dist" directory where the script are held.
 * @param {object} settings an objects providing additional settings
 *  {
 *      crypto: true (optional) whether to load the cryptography functions package, assessable as IA_Designer.crypto,
 *
 *      post_scripts: an array or comma-separated string of urls to additional js files to load. In array format, on
 *          object of the form {ES5: url_to_es5_js, ES6: url_to_es6_js} can be provided to allow for different versions
 *          of scripts, depending on whether the browser supports ES6
 *  }
 * @constructor
 */

var IA_Presenter_loader = (function (root) {

    const debug = false;

    var mainScripts = [
        [
            'dist/mousetrap.min.js', function () {
            return window.hasOwnProperty('Mousetrap');
        }],
        // 'dist/ie_polyfills.js',
        [
            'dist/simple-statistics.min.js', function () {
            return window.hasOwnProperty('ss') &&
                ss.hasOwnProperty('linearRegression');
        }],
        [
            'dist/math.min.js', function () {
            return window.hasOwnProperty('math') && math.hasOwnProperty('eval');
        }],

        [
            'dist/minify.json.min.js', function () {
            return JSON.hasOwnProperty('minify');
        }],
        [
            'dist/lz-string.min.js', function () {
            return window.hasOwnProperty('LZString');
        }],
        // [
        //     'dist/howler.min.js', function () {
        //     return window.hasOwnProperty('Howl');
        // }],
        // [
        //     'dist/video.min.js', function () {
        //     return window.hasOwnProperty('videojs');
        // }],
    ];


    var post_scripts = [
        // 'dist/Font.min.js',
        // [
        //   'dist/math.min.js', function() {
        //   return window.hasOwnProperty('math') && math.hasOwnProperty('eval');
        // }],
    ];

    var load_activated = false;

    var loaded_postscrpts = {};

    var processed_containers = {};

    var container_count = 0;

    function loader(
        script,
        script_type,
        relative_script_path,
        loader_settings) {

        if (typeof relative_script_path === 'object') {
            loader_settings = relative_script_path;
            relative_script_path = '';
        }
        relative_script_path = relative_script_path || '';

        loader_settings = loader_settings || {};

        script_type = script_type || 'js';

        if (loader_settings.load_condition
            && typeof loader_settings.load_condition === 'function'
            && !loader_settings.load_condition()) {
            return;
        }

        if (load_activated) {
            load_activated.push(
                [script, script_type, relative_script_path, loader_settings]);
            return;
        }

        load_activated = [];

        // debug && console.log(script);

        if (script.pre_scripts) {
            loader_settings.pre_scripts = script.pre_scripts;
            delete script.pre_scripts;
        }

        if (loader_settings.load_only) {
            return loadPreScripts(loader_settings, relative_script_path,
                loadPostScripts.bind(undefined, loader_settings, relative_script_path));
        }

        loadPreScripts(loader_settings, relative_script_path, process_presentaions);

        //Helper functions

        function process_presentaions() {
            if (typeof script === 'function') {
                script();
                loadPostScripts(loader_settings, relative_script_path);
            } else if (typeof script === 'string') {
                switch (script_type) {
                    case 'js':
                        jQuery.getScript(script).done(function () {
                            loadPostScripts(loader_settings, relative_script_path);
                        }).fail(error_loading);
                        break;
                    case 'json':
                        jQuery.get(script, function () {
                        }, 'text').done(load_from_settings).fail(error_loading);
                        break;
                }
            } else if (typeof script === 'object' && script.panel_setting) {
                load_from_settings(script);
            }
        }

        function load_from_settings(settings) {
            settings = (typeof settings === 'object') ? settings :
                JSON.parse(JSON.minify(settings));

            if (!settings.html_tab_id) {
                let ia_desing_targets = jQuery(
                    '.IA_Designer_Container, .IA_Presenter_Container');

                if (!ia_desing_targets.length) {
                    debug && console.log(
                        'IA_Designer_Loader: Cannot find any IA_Presenter containers...');
                    return;
                }

                let time = 0;

                ia_desing_targets.each(function (taget) {

                    taget = Snap_ia(ia_desing_targets[taget]);

                    if (settings.container_filter
                        && typeof settings.container_filter === 'function'
                        && !settings.container_filter(taget)) return;

                    if (settings.pre_process_container &&
                        typeof settings.pre_process_container === 'function') {
                        settings.pre_process_container(taget);
                    }

                    let gui_id = taget.attr('id');
                    if (!gui_id) {
                        gui_id = 'IA_Designer_Container_gui' + container_count++;
                        taget.attr('id', gui_id);
                    }

                    if (!processed_containers[gui_id]) {
                        const inner_svg = taget.select('svg');

                        let pres;
                        if (inner_svg) {
                            const svg = inner_svg.node.outerHTML;
                            settings.initial_graphics = {svg: svg};
                            svg.remove();
                        } else if (pres = taget.attr('presentation')) {
                            if (pres.startsWith('base64:')) {
                                let decomp = LZString.decompressFromBase64(pres.slice(7));
                                if (!decomp){
                                    decomp = atob(pres.slice(7))
                                }
                                settings.initial_graphics = {svg:decomp };
                                taget.attr('presentation', '');
                            } else if (pres.startsWith('http')) {
                                settings.initial_graphics = pres;
                                // taget.attr('presentation', '');
                            }
                        }

                        settings.html_tab_id = gui_id;

                        setTimeout(process_container.bind(undefined, gui_id,
                            Object.assign({}, settings)), time);
                        time += 200;

                        processed_containers[gui_id] = true;
                    }
                });
            } else {
                if (!processed_containers[settings.html_tab_id]) {
                    process_container(settings.html_tab_id, settings);
                    processed_containers[settings.html_tab_id] = true;
                }
            }

            function process_container(gui_id, settings) {
                let initial_graphics;
                if (initial_graphics = settings.initial_graphics) {
                    delete settings.initial_graphics;
                }

                var gui = IA_Designer.createInterface(gui_id, settings);

                const background_color = window.getComputedStyle(document.body, null).getPropertyValue('background-color');

                gui.div_container.setStyle({background: background_color});

                window.gui = gui; //for debugging

                if (initial_graphics) {
                    if (typeof initial_graphics === 'string') {
                        gui.openGraphics(initial_graphics, post_process_gui);
                    } else if (typeof initial_graphics === 'object' &&
                        initial_graphics.hasOwnProperty('svg')) {
                        gui.loadGraphicsFromString(initial_graphics.svg);
                        post_process_gui(gui);
                    }

                } else {
                    post_process_gui(gui);
                }
            }

            loadPostScripts(settings, relative_script_path);
        }
    }

    var post_process_gui = function (gui) {
        debug && console.log("Loaded Presentation");
        // gui.fitWindow();
        // gui.comManager.resetSession();
        // gui.eve("panel.resize");
    };

    function loadPreScripts(settings, relative_script_path, pres_gen) {

        if (settings.after_callback) {
            let old_callback = pres_gen;
            pres_gen = function () {
                old_callback && old_callback();
                settings.after_callback();
            };
        }

        if (window.IA_Designer || !window.jQuery) return pres_gen && pres_gen();

        debug && console.log('Loading IA Presenter');

        var main_scripts = mainScripts, post_scripts;

        // main_scripts.push('dist/iaDesignerLogo.js');

        if (settings.pre_scripts) {
            if (!Array.isArray(settings.pre_scripts)) {
                settings.pre_scripts = [settings.pre_scripts];
            }
            main_scripts = [...settings.pre_scripts, ...main_scripts];
        }

        var load_main = function (url, hash) {
            const defer = jQuery.Deferred();

            debug && console.log('Loading ', url);
            // if (hash) {
            //   jQuery.get(url, undefined, function(base64Script, textStatus) {
            //     const time = performance.now();
            //     let hexMd5 = jQuery.crypto.hex_md5(base64Script);
            //     debug && console.log(hexMd5, hash, performance.now() - time);
            //     if (hexMd5 === hash) {
            //       let script = atob(base64Script);
            //       jQuery.globalEval(script);
            //       debug && console.log('Loaded ', url);
            //       defer.resolve();
            //     }
            //   }, 'text');
            // } else {
            jQuery.getScript(url).done(function (script, textStatus) {
                defer.resolve();
            });
            // }
            return defer.promise();
        };

        var defers = [];
        jQuery.ajaxSetup({cache: true});

        main_scripts.forEach(function (url) {
            let base64_encoded_script = false;
            if (Array.isArray(url)) {
                if (typeof url[1] === 'function' && url[1]()) return;
                debug && console.log(url);
                if (url[1] === 'base64') base64_encoded_script = url[2];

                url = url[0];

            }

            url = (url.startsWith('http')) ? url : relative_script_path + url;
            defers.push(load_main(url, base64_encoded_script));
        });

        var promice = jQuery.when.apply($, defers);

        if (pres_gen) {
            promice.done(pres_gen);
        }

    }

    function loadPostScripts(settings, relative_script_path) {
        if (typeof settings === 'string') {
            relative_script_path = settings;
            settings = {};
        }

        if (settings.post_scripts) {
            if (typeof settings.post_scripts === 'string') {
                settings.post_scripts = settings.post_scripts.split(',');
            }
            post_scripts = post_scripts.concat(settings.post_scripts);
        }

        if (settings.crypto) {
            post_scripts.push('dist/ia_md5.min.js'); //todo: minify
        }

        post_scripts.forEach(function (url) {
            if (typeof url === 'object' && (url.ES6 || url.ES6)) {
                if (supportsES6) {
                    url = url.ES6 || '';
                } else {
                    url = url.ES5 || '';
                }

            }
            if (url) {
                if (Array.isArray(url)) {
                    if (typeof url[1] === 'function' && url[1]()) return;
                    if (typeof url[1] === 'string' &&
                        window.hasOwnProperty(url[1])) return;
                    url = url[0];
                }
                url = relative_script_path + url.trim();
                if (!loaded_postscrpts[url]) {
                    debug && console.log('Loading post ', url);
                    loaded_postscrpts[url] = true;
                    jQuery.getScript(url, function () {
                        debug && console.log('Loaded post ', url);
                    });
                }
            }
        });

        jQuery.ajaxSetup({cache: false});

        if (load_activated.length) {
            const to_load = load_activated;
            to_load.forEach((args) => {
                loader.apply(undefined, args);
            });
        }

        load_activated = undefined;
    }

    function error_loading(scr, textStatus) {
        debug && console.log('IA_Designer_Loader: Problem Loading script...');
    }

    loader.loadLibrariesOnly = function (relative_script_path) {
        debug && console.log('Loading Libraries ...........');
        relative_script_path = relative_script_path || '';
        loadPreScripts({}, relative_script_path, function () {
            loadPostScripts(relative_script_path);
        });
    };

    return loader;
}(window || this));

