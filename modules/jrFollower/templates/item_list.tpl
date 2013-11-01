{if isset($_items)}
{foreach from=$_items item="item"}
    <a href="{$jamroom_url}/{$item.profile_url}">{jrCore_module_function function="jrImage_display" module="jrUser" type="user_image" item_id=$item._user_id size="xsmall" crop="auto" class="img_shadow" style="padding:2px;margin-bottom:4px;" alt="{$item.user_name}" title="{$item.user_name}" width=false height=false}</a>
{/foreach}
{/if}
