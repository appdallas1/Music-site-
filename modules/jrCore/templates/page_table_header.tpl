<tr>
    <td colspan="2">
        <table class="page_table{$class}">
            <tr class="nodrag nodrop">
            {foreach from=$cells item="_cell"}
            {if isset($_cell.class)}
                <th class="page_table_header {$_cell.class}" style="width:{$_cell.width}">{$_cell.title}</th>
            {else}
                <th class="page_table_header" style="width:{$_cell.width}">{$_cell.title}</th>
            {/if}
            {/foreach}
            </tr>
