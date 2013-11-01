{if isset($_items)}
  {foreach from=$_items item="item"}
    <a href="{$jamroom_url}/{$_params.module_url}/{$item._item_id}/{$item.page_title|jrCore_url_string}" class="media_title">{$item.page_title}</a><br>
  {/foreach}
{/if}