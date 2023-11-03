/*
 *
 *  * Copyright (c) 2021. Orlin Vakarelov
 *
 */

window.addEventListener("load", function(){
  if (window.IA_Presenter_loader){
    console.log("In Boot Script")
    IA_Presenter_loader(iamg_settings.settings, undefined, iamg_settings.resources);
  }
})
