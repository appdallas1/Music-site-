// Jamroom 5 jrFollower Javascript
// @copyright 2003-2011 by Talldude Networks LLC
// @author Brian Johnson - brian@talldude.net

/**
 * jrFollowProfile
 */
function jrFollowProfile(id,profile_id)
{
    var bid = '#'+ id;
    $(bid).attr('disabled','disabled');
    $.ajax({
        type: 'POST',
        cache: false,
        dataType: 'json',
        url: core_system_url +'/'+ jrFollower_url +'/follow/'+ profile_id +'/__ajax=1',
        success: function(_msg) {
            if (typeof _msg.error != "undefined") {
                alert('a system level error was encountered submitting the request - please try again');
            }
            else {
                if (_msg.PENDING != null && _msg.PENDING == '1') {
                    $(bid).attr('value',_msg.VALUE).removeAttr('onclick').removeClass('follow').addClass('follow_pending');
                }
                else {
                    var new_text = $('#'+ id).attr('value');
                    $(bid).attr('value',_msg.VALUE).attr('onclick','jrUnFollowProfile(\'follow\','+ profile_id +')').removeClass('follow').addClass('following');
                }
            }
            $(bid).removeAttr('disabled');
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
 * jrUnFollowProfile
 */
function jrUnFollowProfile(id,profile_id)
{
    var bid = '#'+ id;
    $(bid).attr('disabled','disabled');
    $.ajax({
        type: 'POST',
        cache: false,
        dataType: 'json',
        url: core_system_url +'/'+ jrFollower_url +'/unfollow/'+ profile_id +'/__ajax=1',
        success: function(_msg) {
            if (typeof _msg.error != "undefined") {
                alert('a system level error was encountered submitting the request - please try again');
            }
            else {
                $(bid).attr('value',_msg.VALUE).attr('onclick','jrFollowProfile(\'follow\','+ profile_id +')').removeClass('following').addClass('follow').removeAttr('disabled');
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

