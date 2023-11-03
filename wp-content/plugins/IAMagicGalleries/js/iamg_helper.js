/*
 *
 *  * Copyright (c) 2021. Orlin Vakarelov
 *
 */

'use strict';

console.log("IAMG Helper loaded!!");

if (IA_Designer) {
    document.body.addEventListener('pointerdown', function(event) {
       IA_Designer.deactivateInterface();
       console.log("Deactivating Interface");
    });

}