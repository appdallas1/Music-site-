{if isset($label)}
  <tr>
    <td class="element_left form_input_left {$type}_left">
      {$label}{if isset($sublabel) && strlen($sublabel) > 0}<br><span class="sublabel">{$sublabel}</span>{/if}
    </td>
    <td class="element_right form_input_right {$type}_right">{$html}</td>
  </tr>
{else}
  <tr>
    <td colspan="2" class="element page_custom">{$html}</td>
  </tr>
{/if}
