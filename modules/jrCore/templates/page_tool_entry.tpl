<tr>

  {if isset($onclick) && strlen($onclick) > 0}
      <td class="element_left"><input type="button" value="{$label}" class="form_button" style="width:100%;" onclick="{$onclick}"></td>
  {elseif strlen($label_url) > 0}
      {if isset($target) && $target == "_self"}
          <td class="element_left"><span class="form_button_anchor"><a href="{$label_url}"><input type="button" value="{$label}" class="form_button" style="width:100%;"></a></span></td>
      {else}
          <td class="element_left"><span class="form_button_anchor"><a href="{$label_url}" target="{$target}"><input type="button" value="{$label}" class="form_button" style="width:100%;"></a></span></td>
      {/if}
  {else}
      <td class="element_left">{$label}</td>
  {/if}

  <td class="element_right">{$description}</td>
</tr>
