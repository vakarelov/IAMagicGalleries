/*
 * Copyright © 2023 Information Aesthetics. All rights reserved.
 * This work is licensed under the GPL2, V2 license.
 */



IA_Designer.plugin(
    /**
     *
     * @param {IA_Designer} IA_Designer
     * @param {Snap} Snap
     * @param {eve} eve
     * @param {Gui} Gui
     * @param {ComManager} ComManager
     */
    function(IA_Designer, Snap, eve, Gui, ComManager) {
      'use strict';

      Gui.prototype.getBlockId = function() {

        return this._blockId ||
            (this._blockId = this.div_container.attr('data-block-id'));
      };

      Gui.prototype.getPresentationId = function() {
        return this.div_container.attr('presentation');
      };

      ComManager.prototype.wpCommand = function(
          command, after_callback, fail_callback) {
        command['action'] = 'iamg_com';

        this.sendCommand(false, command, 'json',
            after_callback, undefined,
            fail_callback);
      };

      ComManager.prototype.wpCommandSVG = function(
          command, after_callback, fail_callback) {
        command['action'] = 'iamg_com';

        this.sendCommand(false, command, 'svg',
            after_callback, undefined,
            fail_callback);
      };

    }, ['Gui', 'ComManager']);