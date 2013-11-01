<span style="{$jrSearch.style}" class="{$jrSearch.class}">
    {jrCore_lang module="jrSearch" id="2" default="recent searches"}:&nbsp;&nbsp;
    {if isset($jrSearchRecent) && is_array($jrSearchRecent)}
        {foreach from=$jrSearchRecent item="recent"}
            <a href="{$jamroom_url}/search/results/{$recent.module}/1/10/search_string={$recent.string}">
                {$recent.string}
            </a>
            &nbsp;&nbsp;
        {/foreach}
    {/if}
</span>
