{if isset($_items)}
    {foreach from=$_items item="item"}
    <div style="display:table">
        <div style="display:table-cell">
            <a href="{$jamroom_url}/{$item.profile_url}/{$_params.module_url}/{$item._item_id}/{$item.video_title_url}">{jrCore_module_function function="jrImage_display" module="jrVideo" type="video_image" item_id=$item._item_id size="small" crop="auto" alt=$item.video_title title=$item.video_title class="iloutline" width=false height=false}</a>
        </div>
        <div class="p5" style="display:table-cell;vertical-align:middle">
            <a href="{$jamroom_url}/{$item.profile_url}/{$_params.module_url}/{$item._item_id}/{$item.video_title_url}" class="media_title">{$item.video_title}</a>
        </div>
    </div>
    {/foreach}
{/if}