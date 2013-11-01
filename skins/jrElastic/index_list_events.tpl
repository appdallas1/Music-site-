{jrCore_module_url module="jrEvent" assign="murl"}
{if isset($_items)}
    {foreach from=$_items item="item"}
        <table>
            <tr>
                <td style="width:1%;vertical-align:middle;">
                    <a href="{$jamroom_url}/{$item.profile_url}/{$murl}/{$item._item_id}/{$item.event_title_url}">{jrCore_module_function function="jrImage_display" module="jrEvent" type="event_image" item_id=$item._item_id size="small" crop="auto" alt=$item.event_title width=false height=false class="iloutline"}</a><br>
                </td>
                <td style="vertical-align:top;padding-left:8px;">
                    <a href="{$jamroom_url}/{$item.profile_url}/{$_params.module_url}/{$item._item_id}/{$item.event_title_url}" class="media_title">{$item.event_title}</a><br>
                    <a href="{$jamroom_url}/{$item.profile_url}/{$_params.module_url}/{$item._item_id}/{$item.event_title_url}" title="{$item.event_location}" class="normal">&#64;&nbsp;{$item.event_location|truncate:20:"...":false}</a><br>
                    <span class="normal">{$item.event_date|jrCore_date_format}</span>
                </td>
            </tr>
        </table>
    {/foreach}
{/if}