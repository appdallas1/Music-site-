// Jamroom 5 Core Javascript
// @copyright 2003-2011 by Talldude Networks LLC
// @author Brian Johnson - brian@jamroom.net

/**
 * Creates a checkbox in form to prevent spam bots from submitting forms
 * @param {string} name Name of checkbox element to add
 * @param {number} idx Tab Index value for form
 * @return bool
 */
function jrFormSpamBotCheckbox(name,idx)
{
    $('#sb_'+ name).html('<input type="checkbox" id="'+ name +'" name="'+ name +'" tabindex="'+ idx +'">');
    return true;
}

/**
 * Submits a form handling validation
 * @param {string} form_id Form ID to submit
 * @param {string} vkey MD5 checksum for validation key
 * @param {string} method ajax/modal/post - post form as an AJAX form or normal (post) form
 */
function jrFormSubmit(form_id,vkey,method)
{
    var msg_id = form_id +'_msg';
    var retval = false;
    $('.field-hilight').removeClass('field-hilight');
    $('.form_submit_section input').attr("disabled","disabled").addClass('form_button_disabled');
    $('#form_submit_indicator').show(300,function() {

        var timeout = setTimeout(function() {
            // get all the inputs into an array.
            $('.form_editor').each(function(index) {
                $('#'+ this.name +'_editor_contents').val(tinyMCE.get(this.name).getContent());
            });
            var values = $(form_id).serializeArray();
            // See if we have saved off entries on load
            if (typeof values !== "object" || values.length === 0) {
                $('#form_submit_indicator').hide(300,function() {
                    $('.form_submit_section input').removeAttr("disabled").removeClass('form_button_disabled');
                    jrFormSystemError(msg_id,"Unable to serialize form elements for submitting!");
                });
                clearTimeout(timeout);
                return false;
            }
            var action = $(form_id).attr("action");
            if (typeof action === "undefined") {
                $('#form_submit_indicator').hide(300,function() {
                    $('.form_submit_section input').removeAttr("disabled").removeClass('form_button_disabled');
                    jrFormSystemError(msg_id,"Unable to retrieve form action value for submitting");
                });
                clearTimeout(timeout);
                return false;
            }

            // Handle form validation
            if (typeof vkey !== "undefined" && vkey !== null) {

                // Submit URL for validation
                $.ajax({
                    type: 'POST',
                    data: values,
                    cache: false,
                    dataType: 'json',
                    url: core_system_url +'/'+ jrCore_url +'/form_validate/__ajax=1',
                    success: function(_msg) {
                        // Handle any messages
                        if (typeof _msg === "undefined" || _msg === null) {
                            $('#form_submit_indicator').hide(300,function() {
                                $('.form_submit_section input').removeAttr("disabled").removeClass('form_button_disabled');
                                jrFormSystemError(msg_id,'Empty response received from server - please try again');
                            });
                        }
                        else if (typeof _msg.OK === "undefined" || _msg.OK != '1') {
                            if (typeof _msg.redirect != "undefined") {
                                clearTimeout(timeout);
                                window.location = _msg.redirect;
                                return true;
                            }
                            jrFormMessages(msg_id,_msg);
                        }
                        else {
                            // _msg is "OK" - looks OK to submit now
                            if (typeof method == "undefined" || method == "ajax") {
                                $.ajax({
                                    type: 'POST',
                                    url: action +'/__ajax=1',
                                    data: values,
                                    cache: false,
                                    dataType: 'json',
                                    success: function(_pmsg) {
                                        // Check for URL redirection
                                        if (typeof _pmsg.redirect != "undefined") {
                                            window.location = _pmsg.redirect;
                                        }
                                        else {
                                            jrFormMessages(msg_id,_pmsg);
                                        }
                                        retval = true;
                                    },
                                    error: function(x,t,e) {
                                        $('#form_submit_indicator').hide(300,function() {
                                            $('.form_submit_section input').removeAttr("disabled").removeClass('form_button_disabled');
                                            // See if we got a message back from the core
                                            var msg = 'a system level error was encountered trying to validate the form values: '+ t +': '+ e;
                                            if (typeof x.responseText !== "undefined" && x.responsText.length > 1) {
                                                msg = 'JSON response error: '+ x.responseText;
                                            }
                                            jrFormSystemError(msg_id,msg);
                                        });
                                    }
                                });
                            }

                            // Modal window
                            else if (method == "modal") {

                                $('#form_submit_indicator').hide(600,function() {

                                    var k = $('#jr_html_modal_token').val();
                                    $('#modal_window').modal();
                                    $('#modal_indicator').show();

                                    // Setup our "listener" which will update our work progress
                                    sid = setInterval(function() {
                                        $.ajax({
                                            cache: false,
                                            dataType: 'json',
                                            url: core_system_url +'/'+ jrCore_url +'/form_modal_status/k='+ k +'/__ajax=1',
                                            success: function(tmp,stat,xhr) {
                                                var fnc = 'jrFormModalSubmit_update_process';
                                                window[fnc](tmp,sid);
                                            },
                                            error: function(r,t,e) {
                                                clearInterval(sid);
                                                alert('An error was encountered communicating with the server: '+ t +': '+ e);
                                            }
                                        })
                                    },1000);

                                    // Submit form
                                    $.ajax({
                                        type: 'POST',
                                        url: action +'/__ajax=1',
                                        data: values,
                                        cache: false,
                                        dataType: 'json',
                                        success: function(_pmsg) {
                                            clearTimeout(timeout);
                                            return true;
                                        }
                                    });
                                });
                            }

                            // normal POST submit
                            else {
                                $(form_id).submit();
                                retval = true;
                            }
                        }
                    },
                    error: function(x,t,e) {
                        $('#form_submit_indicator').hide(300,function() {
                            $('.form_submit_section input').removeAttr("disabled").removeClass('form_button_disabled');
                            // See if we got a message back from the core
                            var msg = 'a system level error was encountered trying to validate the form values: '+ t +': '+ e;
                            if (typeof x.responseText !== "undefined" && x.responsText.length > 1) {
                                msg = 'JSON response error: '+ x.responseText;
                            }
                            jrFormSystemError(msg_id,msg);
                        });
                    }
                });
            }
            // No validation
            else {

                // AJAX or normal submit?
                if (typeof method == "undefined" || method == "ajax") {
                    $.ajax({
                        type: 'POST',
                        url: action +'/__ajax=1',
                        data: values,
                        cache: false,
                        dataType: 'json',
                        success: function(_msg) {
                            // Check for URL redirection
                            if (typeof _msg.redirect != "undefined") {
                                window.location = _msg.redirect;
                            }
                            else {
                                jrFormMessages(msg_id,_msg);
                            }
                            retval = true;
                        },
                        error: function(x,t,e) {
                            $('#form_submit_indicator').hide(300,function() {
                                $('.form_submit_section input').removeAttr("disabled").removeClass('form_button_disabled');
                                // See if we got a message back from the core
                                var msg = 'a system level error was encountered trying to validate the form values: '+ t +': '+ e;
                                if (typeof x.responseText !== "undefined" && x.responsText.length > 1) {
                                    msg = 'JSON response error: '+ x.responseText;
                                }
                                jrFormSystemError(msg_id,msg);
                            });
                        }
                    });
                }

                // Modal window
                else if (method == "modal") {

                    $('#form_submit_indicator').hide(600,function() {
                        var k = $('#jr_html_modal_token').val();
                        $('#modal_window').modal();
                        $('#modal_indicator').show();

                        // Setup our "listener" which will update our work progress
                        sid = setInterval(function() {
                            $.ajax({
                                cache: false,
                                dataType: 'json',
                                url: core_system_url +'/'+ jrCore_url +'/form_modal_status/k='+ k +'/__ajax=1',
                                success: function(tmp,stat,xhr) {
                                    var fnc = 'jrFormModalSubmit_update_process';
                                    window[fnc](tmp,sid);
                                },
                                error: function(r,t,e) {
                                    clearInterval(sid);
                                    alert('An error was encountered communicating with the server: '+ t +': '+ e);
                                }
                            })
                        },1000);

                        // Submit form
                        $.ajax({
                            type: 'POST',
                            url: action +'/__ajax=1',
                            data: values,
                            cache: false,
                            dataType: 'json',
                            success: function(_pmsg) {
                                clearTimeout(timeout);
                                return true;
                            }
                        });
                    });
                }

                else {
                    $(form_id).submit();
                    retval = true;
                }
            }
            clearTimeout(timeout);
            return retval;
        }, 500);
    });
}

/**
 * jrFormSystemError
 */
function jrFormSystemError(msg_id,text)
{
    jrFormMessages(msg_id,{"notices":[{'type':'error','text':text}]});
}

/**
 * jrFormMessages
 */
function jrFormMessages(msg_id,_msg)
{
    var rval = true;
    $('.page-notice-shown').hide(10);
    // Handle any messages
    if (typeof _msg.notices != "undefined") {
        for (var n in _msg.notices) {
            if (!_msg.notices.hasOwnProperty(n)) {
                continue;
            }
            $(msg_id).html(_msg.notices[n].text);
            $(msg_id).removeClass("error success warning notice").addClass(_msg.notices[n].type);
            if (_msg.notices[n].type == 'error') {
                rval = false;
            }
        }
    }
    // Handle any error fields
    if (typeof _msg.error_fields != "undefined") {
        for (var e in _msg.error_fields) {
            if (!_msg.error_fields.hasOwnProperty(e)) {
                continue;
            }
            $(_msg.error_fields[e]).addClass('field-hilight');
        }
    }
    else {
        // Remove any previous errors
        $('.field-hilight').removeClass('field-hilight');
    }
    $('#form_submit_indicator').hide(300,function() {
        $(msg_id).slideDown(150,function() {
            $('.form_submit_section input').removeAttr("disabled").removeClass('form_button_disabled');
        });
    });
    return rval;
}

/**
 * popwin() is a generic popup window creator
 */
function popwin(mypage,myname,w,h,scroll)
{
    LeftPosition = (screen.width) ? (screen.width-w)/2 : 0;
    TopPosition = (screen.height) ? (screen.height-h)/2 : 0;
    settings = 'height='+h+',width='+w+',top='+TopPosition+',left='+LeftPosition+',scrollbars='+scroll+',resizable';
    win = window.open(mypage,myname,settings)
    if (win.opener == null) {
        win.opener = self;
    }
}

/**
 * jrFormSubmitLock() will "lock" a form so it cannot be submitted twice
 */
function jrFormSubmitLock(lock)
{
    $('.form_submit_section input').attr("disabled","disabled").addClass('form_button_disabled');
}

/**
 * The newCaptcha() function resets the captcha image to a new image
 */
function newCaptcha(width,height,url)
{
    var now = new Date();
    $('#captcha').attr('src',url+'/image.php?mode=captcha&width='+width+'&height='+height+'&u='+ now.getTime());
    return true;
}

/**
 * The jrSetCookie function will set a Javascript cookie
 */
function jrSetCookie(name,value,days) {
    var expires = '';
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires="+ date.toGMTString();
    }
    document.cookie = name +"="+ value + expires +"; path=/";
}

/**
 * The jrReadCookie Function will return the value of a previoously set cookie
 */
function jrReadCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length); {
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
    }
    return null;
}

/**
 * The jrEraseCookie will remove a cookie set by jrSetCookie()
 */
function jrEraseCookie(name) {
    jrSetCookie(name,"",-1);
}

/**
 * jrAlertMessage
 */
function jrAlertMessage(msg)
{
    alert(msg);
}

/**
 * The jrFormAllowedFileTypes function is used to validate that the user
 * is attempting to upload a file that is allowed in their Quota.
 *
 * @param field_name string Form name of input=file
 * @param extensions string Comma separated list of allowed file extensions
 * @param error_text string Error message for file type not allowed
 * @return bool
 */
function jrFormAllowedFileTypes(field_name,extensions,error_text)
{
    var f = document.form;
    for (var i=0; i < f.elements.length; i++) {
        var e = f.elements[i];
        if (e.name == field_name && e.value.length > 0) {
            var fext = e.value.split('.').pop();
            fext = fext.toLowerCase();
            var _tmp = extensions.split(',');
            for (i = 0; i < _tmp.length ; i++) {
                if (fext == _tmp[i].toLowerCase()) {
                    return true;
                }
            }
            $('.error').removeClass('error');
            $('input[name=\''+ field_name +'\']').addClass('error');
            jrAlertMessage(error_text);
            return false;
        }
    }
    // Fall through means field was left empty, which is OK
    return true;
}

/**
 * The jrFormRequiredFile function will check to be sure a FILE is
 * being uploaded when required.
 *
 * @param field_name string Form name of input=file
 * @param error_text string Error message if field is empty
 */
function jrFormRequiredFile(field_name,error_text)
{
    var f = document.form;
    for (var i=0; i < f.elements.length; i++) {
        var e = f.elements[i];
        if (e.name == field_name && e.value.length == 0) {
            $('.error').removeClass('error');
            $('input[name=\''+ field_name +'\']').addClass('error');
            jrAlertMessage(error_text);
            return false;
        }
    }
    return true;
}

/**
 * jrFormModalSubmit_update_process
 * @param data Message Object
 * @param sid Update Interval Timer
 * @param skey string Form ID
 * @return bool
 */
function jrFormModalSubmit_update_process(data,sid,skey)
{
    // Check for any error/complete messages
    var k = false;
    for (var u in data) {
        if (data.hasOwnProperty(u)) {
            // When our work is complete on the server we will get a "type"
            // message back (complete,update,error)
            if (typeof data[u].t != "undefined") {
                switch (data[u].t) {
                    case 'complete':
                        clearInterval(sid);
                        $('#modal_error').hide();
                        $('#modal_success').prepend(data[u].m +'<br><br>').show();
                        k = $('#jr_html_modal_token').val();
                        jrFormModalCleanup(k);
                        break;
                    case 'update':
                        $('#modal_updates').prepend(data[u].m +'<br>');
                        break;
                    case 'empty':
                        return true;
                        break;
                    case 'error':
                        $('#modal_success').hide();
                        $('#modal_error').prepend(data[u].m +'<br><br>').show();
                        break;
                    default:
                        clearInterval(sid);
                        k = $('#jr_html_modal_token').val();
                        jrFormModalCleanup(k);
                        break;
                }
            }
            else {
                clearInterval(sid);
                k = $('#jr_html_form_token').val();
                jrFormModalCleanup(k);
            }
        }
    }
    return true;
}

/**
 * jrFormModalCleanup
 * @param skey string Form ID
 * @return bool
 */
function jrFormModalCleanup(skey)
{
    $('#modal_indicator').hide();
    $.ajax({
        cache: false,
        url:   core_system_url +'/'+ jrCore_url +'/form_modal_cleanup/k='+ skey +'/__ajax=1'
    });
    return true;
}

/**
 * jrCoreFieldOrder
 */
function jrCoreFieldOrder()
{
    // Save our original row order
    var old_order = false;
    var new_order = false;
    $('.table_drag_and_drop').tableDnD({
        onDragClass: 'page_drag_row',
        onDragStart: function(table,row) {
            // store our order for comparison
            old_order = $.tableDnD.serialize();
        },
        onDrop: function(table,row) {
            new_order = $.tableDnD.serialize();
            if (new_order == old_order) {
                // No change
                return;
            }
            old_order = new_order;
        }
    });
}

/**
 * jrE - encodeURIComponent
 * @param t string String to encode
 * @return string
 */
function jrE(t)
{
    return encodeURIComponent(t);
}

/**
 * jrFormChainedSelectGet
 */
function jrFormChainedSelectGet(base_name,level,vals,module,prefix)
{
    if (vals == '') {
        vals = 'null';
    }
    $.ajax({
        type: 'POST',
        cache: false,
        dataType: 'json',
        url: core_system_url+'/core/chained_select_get/'+base_name+'/'+level+'/'+vals+'/'+module+'/'+prefix+'/__ajax=1',
        success: function(_msg) {
            if (typeof _msg.error != "undefined") {
                alert('a system level error was encountered submitting the request - please try again');
            }
            else {
                if (_msg.OK != null && _msg.OK == '1') {
                     // Get values array
                    if (vals != 'null') {
                        var _vals = vals.split("|");
                    }
                    // Json decode returned option list
                    var _options = JSON.parse(_msg.VALUE);
                    // Insert the options into their field
                    if (prefix == 'null') {
                        var oSelField = document.getElementById(base_name+'_'+level+'_select');
                    }
                    else {
                        var oSelField = document.getElementById(base_name+'_'+level);
                    }
                    for(key in _options) {
                        var oOption = document.createElement("OPTION");
                        oSelField.options.add(oOption);
                        oOption.text = _options[key];
                    }
                    if (_vals[level] && _vals[level].length > 0) {
                        oSelField.value = _vals[level];
                    }
                    else {
                        oSelField.value = '-';
                    }
                }
            }
            return true;
        },
        error: function() {
            alert('a system level error was encountered submitting the request - please try again');
            return false;
        }
    });
    return false;
}

/**
 * jrFormChainedSelectSet
 */
function jrFormChainedSelectSet(base_name,level,vals,module,sel,prefix)
{
    if (vals == '') {
        vals = 'null';
    }
    $.ajax({
        type: 'POST',
        cache: false,
        dataType: 'json',
        url: core_system_url+'/core/chained_select_set/'+base_name+'/'+level+'/'+vals+'/'+module+'/'+sel+'/'+prefix+'/__ajax=1',
        success: function(_msg) {
            if (typeof _msg.error != "undefined") {
                alert('a system level error was encountered submitting the request - please try again');
            }
            else {
                if (_msg.OK != null && _msg.OK == '1') {
                    // Json decode returned option list
                    var _options = JSON.parse(_msg.VALUE);
                    // Clear options in target and subsequent fields
                    for (var i = ++level;i <= 10;i++) {
                        if (prefix == 'null') {
                            if (document.getElementById(base_name+'_'+i+'_select')) {
                                document.getElementById(base_name+'_'+i+'_select').options.length = 0;
                            }
                        }
                        else {
                            if (document.getElementById(base_name+'_'+i)) {
                                document.getElementById(base_name+'_'+i).options.length = 0;
                            }
                        }
                    }
                    // Insert the options into their field
                    if (prefix == 'null') {
                        var oSelField = document.getElementById(base_name+'_'+level+'_select');
                    }
                    else {
                        var oSelField = document.getElementById(base_name+'_'+level);
                    }
                    for(key in _options) {
                        var oOption = document.createElement("OPTION");
                        oSelField.options.add(oOption);
                        oOption.text = _options[key];
                    }
                }
            }
            return true;
        },
        error: function() {
            alert('a system level error was encountered submitting the request - please try again');
            return false;
        }
    });
    return false;
}


