{if isset($_items)}
    {foreach from=$_items item="row"}
        <li><a href="{$jamroom_url}/{$row.profile_url}">{jrCore_module_function function="jrImage_display" module="jrProfile" type="profile_image" item_id=$row._profile_id size="xxlarge" crop="auto" class="img_scale" alt=$row.profile_name title=$row.profile_name}</a><p class="caption"><a href="{$jamroom_url}/{$row.profile_url}"><span style="color:#FFF;">{$row.profile_name}</span></a></p></li>
    {/foreach}
{/if}
