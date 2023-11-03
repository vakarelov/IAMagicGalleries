/*
 * Copyright (c) 2018.  Orlin Vakarelov
 */

//# sourceURL=buttonEvents.js

//TODO: Make this interface relative
//clear selection button

// console.log("In ButtonEvent_presenter !!!!!!!!!!!!!!!!!!!!");

IA_Designer.addButtonFunction("uploadAction", function () {
    /**
     * @var Gui
     */
    this.controllerWidgets.fileUploader({id: "FileUploader", action: this.comManager.upload_url});
});


IA_Designer.addButtonFunction("openFileLocal", function () {
    /**
     * @var Gui
     */
    // this.controllerWidgets.fileUploader({id: "LocalFileUploader", action: this.comManager.upload_url, local_only:true});
    this.controllerWidgets.localFileOpener({
        id: "LocalFileOpener",
        allowed_types: "svg",
        action: (svg, file_link) => {
            IA_Designer.eve("presenter.endPresentation");
            this.clearGraphics();
            // this.layers.setDimensions([0, 0, 0, 0]);
            let nav = this.loadGraphicsFromString(svg, file_link.name, "empty");
            this.fitToPanel();
            // nav.data("linked_file", [file_link, svg.hashCode()]);
        }
    })
});

// IA_Designer.addButtonFunction("reloadFileLocal", function () {
//     if (!IA_Designer.API_Support.file_API) {
//         eve_ia(["gui", "error"], undefined, "Your Browser does not support local file access!");
//         return;
//     }
//     const navs = this.layers.getNavLayer();
//     const nav_with_file = navs.find((nav) => nav.data("linked_file"));
//     if (!nav_with_file) return;
//
//     const file_link = nav_with_file.data("linked_file");
//
//
//     console.log(file_link[0]);
//
//     const reader = new FileReader();
//
//     // Closure to capture the file information.
//     reader.onload = (e) => {
//         const svg = e.target.result;
//         const hashCode = svg.hashCode();
//         if (file_link[1] === hashCode) {
//             eve_ia(["gui", "warning"], undefined, "The file has not been changed", 5000);
//             return;
//         }
//         this.clearGraphics();
//         // this.layers.setDimensions([0, 0, 0, 0]);
//         let nav = this.loadGraphicsFromString(svg, file_link.name, "empty");
//         this.fitToPanel();
//         nav.data("linked_file", hashCode);
//     };
//
//     reader.onerror = (e)=>{
//         console.log(e);
//     };
//
//     reader.readAsText(file_link[0]);
//
// });


IA_Designer.addButtonFunction("upload_init", function () {
    setTimeout(() => {
        IA_Designer.eve("presenter.drag_and_drop.activate", 1000);
    });

    IA_Designer.eve.on("widget.fileUploader.open.FileUploader", function () {
        IA_Designer.eve(["upload", "deactivate"]);
    });
    IA_Designer.eve.on("widget.fileUploader.close.FileUploader", function () {
        IA_Designer.eve(["upload", "activate"]);
    })
});

IA_Designer.addButtonFunction("restart_pres_init", function () {
    IA_Designer.eve.on("presenter.started", ()=>IA_Designer.eve("gui.button.restart_pres.activate"));
    IA_Designer.eve.on("presenter.ended", ()=>IA_Designer.eve("gui.button.restart_pres.deactivate"));
});

IA_Designer.addButtonFunction("full_screenAction", function () {
    this.fullScreen();
});
