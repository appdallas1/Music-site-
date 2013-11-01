
<div id="modal_window" style="display:none;width:{$modal_width|default:"500"}px;height:{$modal_height|default:"500"}px;bottom:0;">
    <div id="modal_indicator" style="float:right"><img src="{$jamroom_url}/skins/{$_conf.jrCore_active_skin}/img/modal_spinner.gif" width="24" height="24" alt="{jrCore_lang module="jrCore" id="73" default="working..."}"></div>

    {$note}

    <div id="modal_error" class="element page_notice error" style="display:none;">
        <input type="button" value="{jrCore_lang module="jrCore" id="28" default="close"}" class="form_button modal_button" onclick="window.location.reload();">
    </div>
    <div id="modal_success" class="element page_notice success" style="display:none;">
        <input type="button" value="{jrCore_lang module="jrCore" id="28" default="close"}" class="form_button modal_button" onclick="window.location.reload();">
    </div>

    {$html}

    {* This hidden iframe is required - the actual "work" of the form submit happens here *}
    <iframe id="modal_work_frame" name="modal_work_frame" style="display:none;"></iframe>

</div>
