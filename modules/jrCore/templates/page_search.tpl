<tr>
  <td class="element_left">{$label}</td>
  <td class="element_right">
    {$html}
    {if $show_help == '1'}
    <input type="button" value="?" class="form_button" title="{jrCore_lang module="jrCore" id=34 default="expand help"}" onclick="$('#search_help').slideToggle(250);">
    {/if}
  </td>
</tr>
{if $show_help == '1'}
<tr>
  <td class="element_left form_input_left" style="padding:0;height:0px"></td>
  <td>
    <div id="search_help" class="form_help" style="display:none;">

      <table class="form_help_drop">
        <tr>
          <td class="form_help_drop_left">
            Item Search Options:<br>
            <b>value</b> - Search for <b>exact</b> value match.<br>
            <b>%value</b> - Search for items that <b>end in</b> value.<br>
            <b>value%</b> - Search for items that <b>begin with</b> value.<br>
            <b>%value%</b> - Search for items that <b>contain</b> value.<br><br>
            Item Key Search Options:<br>
            <b>key:value</b> - Search for specific key with exact value match.<br>
            <b>key:%value</b> - Search for specific key that <b>begins with</b> value.<br>
            <b>key:value%</b> - Search for specific key that <b>ends with</b> value.<br>
            <b>key:%value%</b> - Search for specific key that <b>contains</b> value.
          </td>
        </tr>
      </table>

    </div>
  </td>
</tr>
{/if}
