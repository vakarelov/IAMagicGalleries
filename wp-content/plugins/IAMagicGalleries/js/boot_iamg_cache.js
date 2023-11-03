/*
 *
 *  * Copyright (c) 2021. Orlin Vakarelov
 *
 */

window.addEventListener('load', function() {
  setTimeout(function() {
    console.log("In Cached Scripts")
    if (window.IA_Presenter_loader) {
      IA_Presenter_loader(iamg_settings.settings, undefined,
          iamg_settings.resources, {load_only: true});
    }
  }, 5000);
});
