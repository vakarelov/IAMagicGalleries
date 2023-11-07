/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */



'use strict';

console.log("IAMG Helper loaded!!");

if (IA_Designer) {
    document.body.addEventListener('pointerdown', function(event) {
       IA_Designer.deactivateInterface();
       console.log("Deactivating Interface");
    });

}