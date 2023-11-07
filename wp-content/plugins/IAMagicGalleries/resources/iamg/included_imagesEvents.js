/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

(function () {
    // included_imaged stuff

    const debug = true;

    debug && console.log("In included images events");

    let panel;
    let num_pics_text_element
    let is_open = false;
    let gui;
    let block_id;
    let locator;
    let behaviour_settings = {}

    // The name to the gallery type
    let gallery_type;
    // Any Library associated with the gallery type
    let library = {id: undefined, selected: undefined};
    // The name of a local panel where user can set library-specific settings
    let local_panel;

    function open() {
        if (panel && panel.attr("display") === "none") {
            panel.attr({opacity: 0, display: ""})
            panel.animate({opacity: 1}, 500);
        }
        is_open = true;
    }

    function close() {
        if (panel && panel.attr("display") !== "none") {
            panel.animate({opacity: 0}, 500, () => panel.attr("display", "none"));
        }
        is_open = false;
    }

    function toggle() {
        if (is_open) {
            close()
        } else {
            open();
        }
    }


    IA_Designer.addButtonFunction("included_imagesToggle", toggle);

    function included_images_init(controllers, id, settings) {
        gui = this;

        panel = gui.controllerWidgets.controllerPanel({
            id: id,
            controllers: controllers,
            use_scrollbox: false,
            change_event: settings.changeEvent
        });
        panel.attr({display: 'none'});

        panel.addEveListener(["controller", "included_images", "open"], open);
        panel.addEveListener(["controller", "included_images", "close"], close);

        panel.addEveListener(settings.changeEvent, function () {
            const panel_state = panel.panel_state["selected_images"];
            if (panel_state && panel_state.length) {
                gui.eve(["gui", "button", "clear_included_images", "activate"])
                if (gallery_type) {
                    gui.eve(["gui", "button", "iamg_create_gallery", "activate"])
                }
            } else {
                gui.eve(["gui", "button", "clear_included_images", "deactivate"])
                gui.eve(["gui", "button", "iamg_create_gallery", "deactivate"])
            }
            if (num_pics_text_element && num_pics_text_element.type === "text") {
                num_pics_text_element.node.innerHTML = panel_state.length || 0;
            }

            // if (panel_state) debug && console.log(panel_state);
        })

        panel.addEveListener(["iamg", "included_images", "remove_image"], function (id) {
            debug && console.log("To remove", id);
            panel.externalUpdate({selected_images: [id, -1]})
        })

        gui.controllers["included_images"] = panel;

        num_pics_text_element = gui.defs.select("#iamg_num_pictures")

        setup_periodic_checks();
        setup_gallery_builder();
        setup_external_loader_listener(panel);
    }

    IA_Designer.addButtonFunction("included_images_init", included_images_init);

    const clearAll = function () {
        debug && console.log(panel);
        const state = panel.panel_state["selected_images"];
        if (state && state.length) {
            state.forEach((id) => {
                panel.externalUpdate({selected_images: [id, -1]})
            })
        }
    };
    IA_Designer.addButtonFunction("included_images_clear", clearAll);

    function setup_gallery_builder() {
        //This event is sent by the presentation view.
        panel.addEveListener(["iamg", "set_gallery_type", "*"], set_gallery_type);

        function set_gallery_type() {
            let _gui = gui;
            if (!_gui && this && this.eve) {
                _gui = this;
            }

            if (!_gui) return;

            const commands = _gui.eve.nts();

            if (gallery_type === commands[2]) return;

            const old_library = library.id;
            gallery_type = commands[2];
            local_panel = commands[3];
            let lib_id = commands[4];


            //open associated library
            if (library.id && library.id !== lib_id) {
                _gui.eve(["library", old_library, "close"]);
            }
            lib_id && _gui.eve(["library", lib_id, "open"]);

            library.id = lib_id;
            library.selected = undefined;

            if (gallery_type === "none") {
                gallery_type = undefined;
                _gui.eve(["gui", "button", "iamg_create_gallery", "deactivate"]);
                return;
            }

            if (panel.panel_state["selected_images"] && panel.panel_state["selected_images"].length) {
                _gui.eve(["gui", "button", "iamg_create_gallery", "activate"]);
            } else {
                _gui.eve(["library", "iamg_image_library", "open"]);
            }

            debug && console.log(gallery_type, local_panel, library);
        }

        panel.addEveListener("library.*.apply", library_apply_process);

        function library_apply_process(index) {
            /**
             * @type {Library}
             */
            let lib = this;
            if (library.id === lib.id && lib.targetGet) {
                library.selected = lib.targetGet(index);
            }
        }
    }

    function setup_periodic_checks() {
        const id = gui.div_container.getId();
        let timer = setInterval(function () {
            //remove closed containers
            if (!document.getElementById(id)) {
                clearInterval(timer);
                gui.eve('global.iamg.gallery.removed', gui);
                gui.svgRoot.remove();
                gui = undefined;
            }

            // let blocks = document.querySelectorAll('[data-type="ia-mg/gallery"] ');// .is-selected
            // debug && console.log(blocks);
            // blocks.length && blocks.forEach((node)=>{
            //     node.classList.remove('is-selected');
            // })

            // debug && console.log("hi");
        }, 1000)
    }

    //Load images externally;
    function setup_external_loader_listener(panel) {
        let temp_images_location = gui.defs.g({id: "included-images-temp-location"});
        panel.addEveListener(["iamg", "load_gallery_setup"], function (type, images, panel_state, recourse) {
            debug && console.log(images);
            for (var i = 0; i < images.length; i++) {
                const id = images[i].id;
                const url = images[i].url;
                let image = temp_images_location.image(url, 0, 0, 150, 150, {id: id});
                panel.externalUpdate({selected_images: [image, i]})
            }
            gui.eve(["controller", "included_images", "open"])

            if (recourse) {
                //todo
            }

            if (panel_state) {
                gui.eve(["widget", "panel", "update", type + "_panel"], undefined, panel_state);
                const panel_inner = gui.eve(["widget", "panel", "getPanel", type + "_panel"])[0];
                debug && console.log("Updated panel", type + "_panel", panel_inner.panel_state, "passed", panel_state);
            }
            gui.eve('presenter.linkFollow', undefined, type)
        })
    }

    function process_preview(data) {
        debug && console.log(data);

        if (!data || !data.svg || !data.locator) {
            gui.eve(["gui", "button", "iamg_create_gallery", "activate"]);
            gui.eve(["gui", "error"], "Gallery could not be created by the server!")
            return;
        }

        let svg = LZString.decompressFromBase64(data.svg);
        if (!svg) svg = atob(data.svg)

        // debug && console.log(svg);
        locator = data.locator;

        //close all libraries
        for (let lib in gui.libraries) if (gui.libraries.hasOwnProperty(lib)) {
            gui.eve("library." + gui.libraries[lib].id + ".close")
        }

        //close panel

        gui.eve(['controller', 'included_images', 'close']);

        //hide all interface buttons
        gui.eve("gui.button.get_by_class", undefined, "iamg-button")
            .firstDefined().forEach(
            (button) => gui.eve("gui.button." + button.name + ".hide")
        )

        gui.eve("gui.button.get_by_class", undefined, "iamg-preview-button")
            .firstDefined().forEach(
            (button) => gui.eve("gui.button." + button.name + ".activate")
        )

        gui.eve("presenter.backupPresentation", undefined, "selector_presentation");

        gui.loadGraphicsFromString(svg, "preview_gallery")


    }

    function creatGallery() {
        gui.eve(["gui", "button", "iamg_create_gallery", "deactivate"]);
        debug && console.log("Creating gallery based on", gallery_type, local_panel, library);
        let images = panel.panel_state["selected_images"];
        if (!(images && images.length)) {
            gui.eve("gui.error", "There are no images selected!");
            return;
        }

        images = images.map((id) => {
            id = id.split("_");
            const orig_id = id.pop();
            return {id: orig_id, title: id.join("_")};
        })

        const parameters = {
            gallery_type: gallery_type,
            images: images
        }
        const settings = {};

        if (local_panel) {
            const settigns_panel = gui.eve("widget.panel.getPanel." + local_panel).firstDefined();
            if (settigns_panel && settigns_panel.panel_state) Object.assign(settings, settigns_panel.panel_state);
            parameters.settings = settings;
        }

        if (library.selected) {
            parameters.resource = library.selected.getId();
        }

        // gui.getBlockId && (post.block_id = gui.getBlockId());

        // debug && console.log(block_id);

        const post = Object.assign({
            command: "make_gallery",
        }, parameters);

        gui.comManager.sendCommand(undefined, post, "json", process_preview, undefined, (resp) => {
            debug && console.log("Error,", resp);
            gui.eve(["gui", "button", "iamg_create_gallery", "activate"])
        })

    }

    function go_home() {
        gui.eve(["presenter", "linkFollow"], "home");
        gui.eve(["gui", "button", "iamg_create_gallery", "deactivate"])
    }

    IA_Designer.addButtonFunction("iamg_go_home", go_home)

    IA_Designer.addButtonFunction("iamg_create_gallery", creatGallery);

    function backToSetup() {
        //hide all interface buttons
        gui.eve("gui.button.get_by_class", undefined, "iamg-button")
            .firstDefined().forEach(
            (button) => gui.eve("gui.button." + button.name + ".show")
        )

        gui.eve("gui.button.get_by_class", undefined, "iamg-preview-button")
            .firstDefined().forEach(
            (button) => gui.eve("gui.button." + button.name + ".deactivate")
        )


        gui.eve("presenter.restorePresentation", undefined, "selector_presentation");

        gui.eve(["gui", "button", "iamg_create_gallery", "activate"]);

    }

    IA_Designer.addButtonFunction("iamg_back_to_setup", backToSetup)

    function save_gallery() {
        if (!locator) return;
        debug && console.log("saving gallery");

        gui.eve("global.iamg.gallery.save", gui, locator);
    }

    IA_Designer.addButtonFunction("iamg_save_gallery", save_gallery)

    //Behaviour Settings
    let behavoir = {};
    let select_sub_buttons;
    const iamgSettingsIconId = "iamg_behave_icon";
    const default_behaviour = "fixed";
    const allowed_behaviours = {fixed: 1, adaptive: 1, full: 1};

    function set_setting_button_state(behavior) {
        select_sub_buttons.forEach((sub_button) => {
            if (sub_button.getId() === behavior) {
                sub_button.attr("display", "")
            } else {
                sub_button.attr("display", "none")
            }
        })
    }

    let setting_panel;

    function process_behaviour_panel_events(gui) {
        let nts = gui.eve.nts();
        const behavior = nts[nts.length - 1];

        const panel_state = setting_panel.panel_state;
        // debug && console.log(behavior);

        if (allowed_behaviours[behavior]) {
            set_setting_button_state(behavior);
            // debug && console.log("Set Behavior", behavior);
            gui.eve(["controller", "behaviour_settings", "close"]);
            behaviour_settings.behaviour = behavior;
            return;
        }

        switch (behavior) {
            case "use_height":
                behaviour_settings.height = {
                    val: round(panel_state.height_pixel) || 600,
                    type: "pixel"
                };
                break;
            case "not_use_height":
                behaviour_settings.height = undefined
                break;

            case "use_width":
                behaviour_settings.width = {
                    val: round(panel_state.width_pixel) || 600,
                    type: "pixel"
                };
                break;
            case "not_use_width":
                behaviour_settings.width = {
                    val: round(panel_state.width_pixel) || 600,
                    type: "pixel"
                };
                break;
            case "height_toggle_pixel":
                behaviour_settings.height = {
                    val: round(panel_state.height_pixel) || 600,
                    type: "pixel"
                };
                break;

            case "height_toggle_percent":
                behaviour_settings.height = {
                    val: round(panel_state.height_percent) || 100,
                    type: "percent"
                };
                break;
            case "width_toggle_pixel":
                behaviour_settings.width = {
                    val: round(panel_state.width_pixel) || 600,
                    type: "pixel"
                };
                break;

            case "width_toggle_percent":
                behaviour_settings.width = {
                    val: round(panel_state.width_percent) || 100,
                    type: "percent"
                };
                break;
            case "height_pixel":
                behaviour_settings.height = {
                    val: round(panel_state.height_pixel),
                    type: "pixel"
                };
                break;
            case "height_percent":
                behaviour_settings.height = {
                    val: round(panel_state.height_percent),
                    type: "percent"
                };
                break;
            case "width_pixel":
                behaviour_settings.width = {
                    val: round(panel_state.width_pixel),
                    type: "pixel"
                };
                break;
            case "width_percent":
                behaviour_settings.width = {
                    val: round(panel_state.width_percent),
                    type: "percent"
                };
                break;
        }

        debug && console.log(behaviour_settings);

        // if (panel_state) debug && console.log(panel_state);
    }

    function behaviour_settings_init(controllers, id, settings) {
        gui = this;

        if (!select_sub_buttons) {
            const button = gui.defs.select("#" + iamgSettingsIconId);
            select_sub_buttons = button.getChildren();
            set_setting_button_state(default_behaviour);
        }

        setting_panel = gui.controllerWidgets.controllerPanel({
            id: id,
            controllers: controllers,
            use_scrollbox: false,
            change_event: settings.changeEvent,
            background: settings.background
        });
        setting_panel.attr({display: 'none'});
        const open = () => {
            if (setting_panel && setting_panel.attr("display") === "none") {
                setting_panel.attr({opacity: 0, display: ""})
                setting_panel.animate({opacity: 1}, 500);
            }
            is_open = true;
        };

        const close = () => {
            if (setting_panel && setting_panel.attr("display") !== "none") {
                setting_panel.animate({opacity: 0}, 500, () => setting_panel.attr("display", "none"));
            }
            is_open = false;
        };

        setting_panel.addEveListener(["controller", "behaviour_settings", "open"], open);
        setting_panel.addEveListener(["controller", "behaviour_settings", "close"], close);

        // setting_panel.addEveListener(settings.changeEvent, process_behaviour_panel_events.bind(undefined, gui))


        gui.controllers["behaviour_settings"] = setting_panel;

    }

    IA_Designer.addButtonFunction("behaviour_settings_init", behaviour_settings_init);

    IA_Designer.addButtonFunction("behave_setting_toggle", function () {
        if (setting_panel.attr("display") === "none") {
            gui.eve(["controller", "behaviour_settings", "open"]);
        } else {
            gui.eve(["controller", "behaviour_settings", "close"]);
        }
    })

    function set_behavior(behavior) {

    }


})()