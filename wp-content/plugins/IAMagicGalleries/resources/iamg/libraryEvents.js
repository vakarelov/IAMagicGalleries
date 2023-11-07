
/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

/*
 * Copyright © 2023  Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */

(function () {
    const debug = false;
    
    debug && console.log("In Library events");

    const image_library_json_converter = function (img) {
        const gui = this;
        const img_svg = gui.defs.image(img.url, 0, 0, img.width, img.height, {id: img.title + "_" + img.id});

        const sizes = [img.full];
        if (img.large) sizes.push(img.large);
        if (img.medium) sizes.push(img.medium);
        img_svg.data({id: img.id, sizes: sizes, media: "image"});

        return img_svg;
    };
    IA_Designer.addButtonFunction("iamg_image_library_json_converter", image_library_json_converter)

    const video_lib_json_converter = function (vid) {
        const gui = this;
        let img_svg;
        if (vid.thumbnail) {
            img_svg = gui.defs.image(vid.thumbnail.url, 0, 0, vid.thumbnail.width, vid.thumbnail.height,
                {id: "lib_" + vid.title});
        } else {
            const ratio = vid.height / vid.width
            img_svg = gui.defs.g().attr({id: "lib_" + vid.title});
            const rect = img_svg.rect(0, 0, 200, 200 * ratio, {
                fill: "#555555",
            })

            const text = img_svg.text(0, 0, vid.title).attr({id: "lib_text_" + vid.title, fontSize: 32, fill: "white"})
            text.fitInBox(rect.getBBox(), true, true);
        }

        img_svg.data({id: vid.id, sizes: [[vid.url, vid.width, vid.height]], media: "video"});

        return img_svg;
    };
    IA_Designer.addButtonFunction("iamg_video_library_json_converter", video_lib_json_converter)

    let gui = IA_Designer.getGui();

    let added_elements = {};
    window.added_elements = added_elements;

    let added_elements_external = {};
    window.added_elements_external = added_elements_external;

    let element_container;

    let panel;

    function get_element_container() {
        if (!element_container) {
            element_container = gui.eve(["widget", "elementList", "get", "selected_images"]).firstDefined();
        }
        return element_container;
    }

    function get_included_images_panel() {
        panel = panel || gui.eve(["widget", "panel", "getPanel", "included_images"]).firstDefined();
        window.panel_lib = panel; //for debugging purposes
        return panel;
    }

    function process_apply(el, index, location, add_only) {
        if (expanded_image) expanded_image.remove();
        const id = el.getId();
        // const udate_event = ["widget", "elementList", "external", "update", "selected_images"];
        get_included_images_panel();
        if (!panel) return;
        if (added_elements[id]) {
            if (!add_only) {
                el.attr("opacity", 1);
                added_elements[id] = undefined;
                // gui.eve(udate_event, undefined, id, -1);
                panel.externalUpdate({selected_images: [id, -1]})
            }
        } else {
            const copy = el.clone();
            const place_use = location.place.clone();
            place_use.repositionInGroup(gui.overlay)
            el.attr("opacity", .3);
            added_elements[el.getId()] = el;
            // gui.eve(udate_event, undefined, copy, undefined, 300);
            panel.externalUpdate({selected_images: [copy, undefined, 300]})
            gui.eve(["widget", "elementList", "toBottom", "selected_images"])
            const panel_bbox = panel.getBBox();
            const elementContainer = get_element_container();
            const el_bbox = elementContainer.getBBox();
            const bBox = place_use.getBBox();
            const place_use_h = bBox.height;

            // debug && console.log(place_use_h, el_bbox, bBox);

            place_use.animateTransform(Snap_ia.matrix().translate(gui.panelWidth + el_bbox.x,
                    panel_bbox.y + el_bbox.y2 - place_use_h), 300,
                undefined, () => place_use.remove());
        }

        gui.eve('controller.included_images.open');
        // debug && console.log(el.data("id"));
    }

    gui.eve.on("iamg.addImage", process_apply)

    function set_to_available(value, removed) {
        let run_external_checked = false;
        if (removed) {
            if (added_elements[removed]) {
                added_elements[removed].attr("opacity", 1);
                added_elements[removed] = undefined;
            }
            if (added_elements_external[removed]) {
                added_elements_external[removed] = undefined;
            }
        } else {
            for (var i = 0; i < value.length; ++i) {
                if (!added_elements[value[i]]
                    && !added_elements_external[value[i]]) {
                    added_elements_external[value[i]] = true;
                    run_external_checked = true;
                }
            }
        }

        if (run_external_checked) {
            Object.values(gui.libraries)
                .forEach((lib) => check_for_externally_added_images(lib))
        }

        debug && console.log("Element List", value, removed);
    }

    gui.eve.on(["widget", "elementList", "update", "selected_images"], set_to_available)

    let lib_width

    function image_remove(img) {
        img.animate({opacity: 0}, 200, mina.linear, () => img.remove());
    }

    const libraries = ["iamg_image_library", "iamg_video_library"]

    function check_for_externally_added_images(lib) {
        const library = lib || this;
        const elements = library.getElements().filter(Boolean);
        if (elements && elements.length) {
            for (let i = 0; i < elements.length; i++) {
                let id = elements[i].getId();
                if (added_elements_external[id]) {
                    elements[i].attr("opacity", .3);
                    added_elements[id] = elements[i];
                    added_elements_external[id] = undefined;
                }
            }
        }
    }

    libraries.forEach((name) => {
        gui.eve.on(["library", name, "home_change"], check_for_externally_added_images)
    })

    // Processes clicking on images, where the images are expected.
    function clickImage(el, index, ev) {
        const library = this;
        const sizes = el.data("sizes");

        if (sizes) {
            if (expanded_image) {
                if (!sizes.find((size) => size[0] === expanded_image.attr("href"))) {
                    image_remove(expanded_image)
                    expanded_image = undefined;
                } else {
                    image_remove(expanded_image)
                    expanded_image = undefined;
                    return;
                }
            }

            lib_width = lib_width || library.getWidth() * 2.6
            const width = gui.panelWidth - lib_width;
            const height = gui.panelHeight - 50;

            let url = sizes[0][0];
            for (let i = 0, min = Number.MAX_VALUE; i < sizes.length; i++) {
                if ((sizes[i][1] > width * .75 || sizes[i][2] > height * .75) && sizes[i][1] < min) {
                    min = sizes[i][1];
                    url = sizes[i][0];
                }
            }

            let target = Snap_ia(ev.target);
            const c = target.getBBox().center()
            const nc = Snap_ia.groupToGroupChangeOfBase(target, gui.centerGroup).apply(c);

            expanded_image = gui.centerGroup.image(url, nc.x, nc.y, 0, 0, {preserveAspectRatio: "xMidYMid"});

            expanded_image.animate({x: -width / 2, y: -height / 2, width: width, height: height}, 250)

            expanded_image.click(() => {
                image_remove(expanded_image)
                expanded_image = undefined;
            })
        }
        debug && console.log("Image Clicked", index);
    }

    gui.eve.on("iamg.clikedImage", clickImage)


    // Processes selecting multiple images with the region-select tool.
    function region_select(select_rect, selected) {
        const locations = this.locations;
        selected.forEach((i_el, i) => {
            const index = i_el[0];
            if (!i) {
                process_apply(i_el[1], index, locations[index], true);
            } else {
                setTimeout(() => process_apply(i_el[1], index, locations[index], true), i * 64);
            }


        })
    }

    gui.eve.on("iamg.region_select", region_select)

    //Change diminutions to expanded images when panel resizes.
    let expanded_image;
    gui.eve.on(['gui', 'panel', 'resized'], function () {
        if (expanded_image) {
            const width = gui.panelWidth - lib_width;
            const height = gui.panelHeight - 20;
            expanded_image.attr({x: -width / 2, y: -height / 2, width: width, height: height});
        }
    })

    //Clicking the panel closed expanded image
    gui.eve.on(['gui', 'panel', 'clickEnd'], () => {
        if (expanded_image) {
            image_remove(expanded_image);
            expanded_image = undefined;
        }
    });

})();


