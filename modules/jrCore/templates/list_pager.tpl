{* prev/next page profile footer links *}
{if $info.prev_page > 0 || $info.next_page > 0}
    <div class="block">
        <table style="width:100%">
            <tr>
                <td style="width:25%">
                {if $info.prev_page > 0}
                    <input type="button" class="form_button" value="&lt;" onclick="window.location='{$info.page_base_url}/p={$info.prev_page}'">
                {/if}
                </td>
                <td style="width:50%;text-align:center">
                    {$info.this_page}&nbsp;/&nbsp;{$info.total_pages}
                </td>
                <td style="width:25%;text-align:right">
                {if $info.next_page > 0}
                    <input type="button" class="form_button" value="&gt;" onclick="window.location='{$info.page_base_url}/p={$info.next_page}'">
                {/if}
                </td>
            </tr>
        </table>
    </div>
{/if}