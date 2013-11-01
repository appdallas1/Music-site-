{if isset($rownum) && $rownum % 2 === 0}
<tr class="page_table_row{$class}">
{else}
<tr class="page_table_row_alt{$class}">
{/if}
{foreach from=$cells key="num" item="_cell"}
  {if isset($_cell.class)}
  <td class="page_table_cell {$_cell.class}"{$_cell.colspan}>{$_cell.title}</td>
  {else}
  <td class="page_table_cell"{$_cell.colspan}>{$_cell.title}</td>
  {/if}
{/foreach}
</tr>
