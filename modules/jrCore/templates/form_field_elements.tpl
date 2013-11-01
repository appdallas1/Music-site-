<tr>
  <td class="element_left form_input_left {$type}_left {$name}_element_left">
    <a id="ff-{$name}"></a>{$label}
    {if isset($sublabel) && strlen($sublabel) > 0}
      <br><span class="sublabel">{$sublabel}</span>
    {/if}
  </td>
  <td class="element_right form_input_right {$type}_right {$name}_element_right">
    {$html}
    {if isset($help) && strlen($help) > 0}
      <input type="button" value="?" class="form_button form_help_button" title="{jrCore_lang module="jrCore" id=34 default="expand help"}" onclick="$('#h_{$name}').slideToggle(250);">
    {/if}
  </td>
</tr>
{if isset($help) && strlen($help) > 0}
<tr>
  <td class="element_left form_input_left" style="padding:0;height:0px"></td>
  <td>
    <div id="h_{$name}" class="form_help" style="display:none;">

      <table class="form_help_drop">
        <tr>
          <td class="form_help_drop_left">
            {$help}
            {* we only show updated time on Global / Quota config entries *}
            {if isset($show_update_in_help) && $show_update_in_help == '1'}
              {if isset($default) && !is_array($default) && strlen($default) > 0}
                {if isset($default_label)}
                {* default label will be set on select and checkbox items *}
                <br><span class="form_help_default">Default: {$default_label}</span>
                {else}
                <br><span class="form_help_default">Default: {$default}</span>
                {/if}
              {/if}
              {if $_conf.jrDeveloper_developer_mode == 'on' && strpos($_post._uri,'global')}
                  <br><br>Template Variable: {ldelim}&#36;_conf.{$_post.module}_{$name}{rdelim}
              {/if}
              {if isset($updated) && $updated > 0}
                <br><span class="form_help_small">Last Updated: {$updated|jrCore_date_format} by {$user|default:"installer"}</span>
              {/if}
            {/if}
          </td>
          <td class="form_help_drop_right">
            {* we only show updated time on Global / Quota config entries *}
            {if isset($show_update_in_help) && $show_update_in_help == '1'}
              {if isset($default) && !is_array($default) && strlen($default) > 0 && $default != $value}
                {* we have to do things a bit differently for checkboxes *}
                {if isset($type) && $type == 'checkbox'}
                  {if isset($default) && $default == "on"}
                    <input type="button" value="use default" class="form_button" style="width:100px" onclick="$('#{$name}').prop('checked','checked');">
                  {else}
                    <input type="button" value="use default" class="form_button" style="width:100px" onclick="$('#{$name}').prop('checked','');">
                  {/if}
                {else}
                  <input type="button" value="use default" class="form_button" style="width:100px;" onclick="var v=$(this).val();if (v == 'use default'){ldelim}$('#{$name}').val('{$default_value}');$(this).val('cancel');{rdelim}else{ldelim}$('#{$name}').val('{$saved_value}');$(this).val('use default');{rdelim}">
                {/if}
              {/if}
            {/if}
          </td>
        </tr>
      </table>

    </div>
  </td>
</tr>
{/if}
