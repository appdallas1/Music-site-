<tr>
    <td colspan="2" class="page_banner_box">
        <table class="page_banner">
            <tr>
                {if strlen($icon_url) > 0}
                    {if jrUser_is_master()}
                        {jrCore_get_module_index module=$_post.module assign="url"}
                        <td class="page_banner_icon"><a href="{$jamroom_url}/{$_post.module_url}/{$url}"><img src="{$icon_url}" alt="icon" height="32" width="32"></a></td>
                    {else}
                        <td class="page_banner_icon"><img src="{$icon_url}" alt="icon" height="32" width="32"></td>
                    {/if}
                    <td class="page_banner_left">{$title}</td>
                    <td class="page_banner_right" style="width:69%">{$subtitle}</td>
                {else}
                    <td class="page_banner_left">{$title}</td>
                    <td class="page_banner_right">{$subtitle}</td>
                {/if}
            </tr>
        </table>
    </td>
</tr>
