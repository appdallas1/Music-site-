/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function () {
    // Load plugin specific language pack
    tinymce.PluginManager.requireLangPack('jrembed');

    tinymce.create('tinymce.plugins.JrembedPlugin', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init:function (ed, url) {
            // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceJrembed');
            ed.addCommand('mceJrembed', function () {
                ed.windowManager.open({
                    file:core_system_url + '/embed/tabs',
                    width:800 + parseInt(ed.getLang('jrembed.delta_width', 0)),
                    height:510 + parseInt(ed.getLang('jrembed.delta_height', 0)),
                    inline:1
                }, {
                    plugin_url:url, // Plugin absolute URL
                    some_custom_arg:'custom arg' // Custom argument
                });
            });

            // Register jrembed button
            ed.addButton('jrembed', {
                title:'jrembed.desc',
                cmd:'mceJrembed',
                image:core_system_url +'/modules/jrEmbed/img/jrembed.png'
            });

            // Add a node change handler, selects the button in the UI when a image is selected
            ed.onNodeChange.add(function (ed, cm, n) {
                cm.setActive('jrembed', n.nodeName == 'IMG');
            });
        },

        /**
         * Creates control instances based in the incoming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like list boxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use in order to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl:function (n, cm) {
            return null;
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo:function () {
            return {
                longname:'Jrembed plugin',
                author:'Jamroom.net - Michael',
                authorurl:'http://jamroom.net',
                infourl:'http://www.jamroom.net/phpBB2/index.php',
                version:"1.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('jrembed', tinymce.plugins.JrembedPlugin);
})();