<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <title>{$_conf.jrCore_system_name} - {$jrFeed._items.0.profile_name} actions</title>
        <description>{jrCore_lang module="jrAction" id="19" default="A list of the most recent %1 actions by %2" 1=$jrFeed.info.limit 2=$jrFeed._items.0.profile_name}</description>
        <language>en-us</language>
        <link>{$_conf.jrCore_base_url}</link>
        <lastBuildDate>{$smarty.now|jrCore_date_format:"%a, %d %b %Y %T %Z"}</lastBuildDate>
        <generator>Jamroom5</generator>

        {if isset($jrFeed._items) && is_array($jrFeed._items)}
            {foreach from=$jrFeed._items item="item"}
                {if isset($item.action_data)}
                    <item>
                        <title>@{$item.profile_name}</title>
                        <link>{$jamroom_url}/{$item.profile_url}/{$_mods[$item.action_module]['module_url']}/{$item.action_item_id}</link>
                        {jrCore_include module=$item.action_module template="item_action.tpl" assign="description"}
                        <description>{$description|strip_tags}</description>
                        <pubDate>{$item._created|jrCore_date_format:"%a, %d %b %Y %T %Z"}</pubDate>
                    </item>
                {elseif isset($item.action_text)}
                    <item>
                        <title>@{$item.profile_name}</title>
                        <link>{$jamroom_url}/{$item.profile_url}</link>
                        <description>{$item.action_text}</description>
                        <pubDate>{$item._created|jrCore_date_format:"%a, %d %b %Y %T %Z"}</pubDate>
                    </item>
                {/if}
            {/foreach}
        {/if}

    </channel>
</rss>
