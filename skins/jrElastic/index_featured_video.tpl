{if isset($_items)}
    {foreach from=$_items item="item"}
    <a href="{$jamroom_url}/{$item.profile_url}">{jrCore_module_function function="jrImage_display" module="jrVideo" type="video_image" item_id=$item._item_id size="large" crop="auto" alt=$item.video_title title=$item.video_title class="iloutline" width="130" height="130"}</a><br>
    <span class="rank">{$item.list_rank}.</span>&nbsp;<a href="{$jamroom_url}/{$item.profile_url}/{$_params.module_url}/{$item._item_id}/{$item.video_title_url}" class="media_title">{$item.video_title}</a><br>
    {/foreach}
{/if}