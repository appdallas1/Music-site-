<span style="{$jrSearch.style}" class="{$jrSearch.class}">
    {jrCore_lang module="jrSearch" id="3" default="popular searches"}:&nbsp;&nbsp;
    {if isset($jrSearchPopular) && is_array($jrSearchPopular)}
        {foreach from=$jrSearchPopular item="popular"}
            <a href="{$jamroom_url}/search/results/{$popular.module}/1/10/search_string={$popular.string}">
                {$popular.string} ({$popular.count})
            </a>
            &nbsp;&nbsp;
        {/foreach}
    {/if}
</span>
