<tr>
  <td class="element_left">
    {$label}
    {if isset($sublabel) && strlen($sublabel) > 0}
      <br><span class="sublabel">{$sublabel}</span>
    {/if}
  </td>
  <td class="element_right">
    {if strpos($url,'http') === 0}
      <a href="{$url}" target="_blank">{$url}</a>
    {else}
      {$url}
    {/if}
  </td>
</tr>
