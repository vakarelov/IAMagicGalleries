/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */



(function () {
    const debug = true;
    window.addEventListener("load", function () {
        // let gui;
        let gal_id;
        const __ = wp.i18n.__;

        // console.log("In Boot Script", iamg_settings)

        let current_location = window.location.href;
        let is_new_post = current_location.includes("post-new.php");

        //hide minor-publishing-actions element
        let save_button = document.getElementById("save-post");

        //disable submit button with name "publish"
        let submit_button = document.getElementsByName("publish")[0];

        if (is_new_post) {
            if (submit_button) submit_button.disabled = true;
            if (save_button) save_button.disabled = true;
        }

        const after_load = () => {
            eve_ia.on(['ia', 'gui', 'created'], function (id) {
                const gui = this;
                debug && console.log('Creating gui..............', id);

                //remove element with class iamg-loading-gif
                let loading_gif = document.getElementsByClassName("iamg-loading-gif")[0];
                if (loading_gif) loading_gif.remove();

                // debug && console.log("prev_properties", prev_properties);
                gui.eve.once(['presenter', 'loaded'], //['presenter', 'presentation', 'loaded']
                    set_current_gallery_state.bind(undefined, gui))(100)
            })


            function set_current_gallery_state(gui) {

                let properties = iamg_settings.gallery_properties;
                // if (properties) properties = JSON.parse(properties)

                debug && console.log("Presenter loaded");
                debug && console.log("Setting previous properties: ", properties)

                gal_id = iamg_settings["id"];

                if (!properties["type"]) return;

                const gallery_type = properties["type"];
                let image_info = properties["images"] || [];
                const original_requested_images = properties["requested_images"] || [];
                const resource = properties["resource"];

                let panel_state;
                for (let param in properties) if (properties.hasOwnProperty(param) && param.startsWith(gallery_type)) {
                    panel_state = panel_state || {};
                    panel_state[param] = properties[param];
                }

                let images;

                images = match_images(image_info, original_requested_images);

                gui.eve(["iamg", "load_gallery_setup"], undefined, gallery_type, images, panel_state, resource);
            }

            function match_images(image_info, original_requested_images) {
                const images = []
                let original_length = original_requested_images.length;
                for (let i = 0, title; i < image_info.length; i++) {
                    title = image_info[i].title;
                    for (let j = 0; j < original_length; j++) {
                        let originalRequestedImage = original_requested_images[(i + j) % original_length];
                        if (title === originalRequestedImage.title) {
                            images.push({
                                id: title + "_" + originalRequestedImage.id,
                                url: image_info[i].thumbnail.url,
                                title: title
                            });
                            break;
                        }
                    }
                }
                return images;
            }

            eve_ia.on(['global', 'iamg', 'gallery', 'save'], function (locator) {
                const gui = this;

                debug && console.log('Saving', locator);

                //get value of input with name "post_title"
                let post_title = document.getElementsByName("post_title")[0].value;

                if (!post_title) {
                    gui.eve("gui.alert", __("Please, enter gallery title") + ".", [200, 100], [__("OK")])
                    return;
                }

                const command = {
                    command: 'save',
                    locator: locator,
                    title: post_title,
                    post_id: iamg_settings.post_id,
                    is_gallery_post: true
                };

                let process_responce = function (response) {
                    debug && console.log('Success', response);
                    gui.eve("gui.alert", __("Gallery is saved successfully") + ".", [200, 100], [__("OK")], [
                        function () {
                            //get page locaiton and check it contiains post-new.php

                            if (is_new_post) {
                                //replace the post-new.php and what follows with post.php?post=id&action=edit
                                current_location = current_location.replace(/post-new\.php.*/, "post.php?post=" + iamg_settings.post_id + "&action=edit");
                                //redirect to the post.php?post=id&action=edit
                                // window.location.href = current_location;
                                if (save_button) save_button.disabled = false;
                                save_button.click();
                            }
                        }

                    ])


                    if (submit_button) submit_button.disabled = false;
                };
                gui.comManager.wpCommand(command,
                    process_responce,
                    function (response) {
                        debug && console.log('Fail', response);
                    },
                );

            });
        }


        if (window.IA_Presenter_loader) {
            console.log("In Boot Script")
            IA_Presenter_loader(iamg_settings.settings, undefined, iamg_settings.resources,
                {after_callback: after_load}
            );
        }


    })


})()