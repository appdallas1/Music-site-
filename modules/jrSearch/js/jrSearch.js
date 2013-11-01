/**
 * jrSearch Javascript functions
 * @copyright 2012 Talldude Networks, LLC.
 */

/**
 * Display a modal search form
 */
function jrSearch_modal_form()
{
    $('#searchform').modal({

        onOpen: function (dialog) {
            dialog.overlay.fadeIn(75, function () {
                dialog.container.slideDown(5, function () {
                    dialog.data.fadeIn(75);
                });
            });
        },
        onClose: function (dialog) {
            dialog.data.fadeOut('fast', function () {
                dialog.container.hide('fast', function () {
                    dialog.overlay.fadeOut('fast', function () {
                        $.modal.close();
                    });
                });
            });
        },
        overlayClose:true
    });
}