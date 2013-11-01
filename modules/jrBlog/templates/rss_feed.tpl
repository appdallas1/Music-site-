<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
    <channel>
        <title>{$items[0].profile_name}</title>
        <atom:link href="{$jamroom_url}/feed/{$blog_profile_id}" rel="self" type="application/rss+xml"/>
        <link>{$jamroom_url}/{$items[0].profile_url}/</link>
        <description>{$items[0].profile_name} Blog</description>
        <lastBuildDate>{$lastBuildDate}</lastBuildDate>
        <language>en</language>
        <sy:updatePeriod>hourly</sy:updatePeriod>
        <sy:updateFrequency>1</sy:updateFrequency>
    {jrCore_module_url module="jrBlog" assign="murl"}
    {foreach $items as $_b}
        <item>
            <title>{$_b.blog_title}</title>
            <link>{$jamroom_url}/{$_b.profile_url}/{$murl}/{$_b._item_id}/{$_b.blog_title_url}</link>
            <comments>{$jamroom_url}/{$_b.profile_url}/{$murl}/{$_b._item_id}/{$_b.blog_title_url}#disqus_thread</comments>
            <pubDate>{$_b.blog_publish_date|date:'D, d M Y H:i:s O'}</pubDate>
            <dc:creator>{$_b.profile_name}</dc:creator>
            <category><![CDATA[{$_b.blog_category}]]></category>
            <description><![CDATA[{$_b.blog_text|jrCore_format_string:$item.profile_quota_id|jrCore_readmore}]]></description>
            <content:encoded><![CDATA[{$_b.blog_text|jrCore_format_string:$item.profile_quota_id|jrCore_readmore}]]></content:encoded>
            <slash:comments>0</slash:comments>
        </item>
    {/foreach}
    </channel>
</rss>
