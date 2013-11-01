{jrCore_module_url module="jrBlog" assign="murl"}
<div class="p5">
    <span class="action_item_title">
    {if $item['action_mode'] == 'create'}
        {jrCore_lang module="jrBlog" id="19" default="Posted a new Blog"}:
    {else}
        {jrCore_lang module="jrBlog" id="30" default="Updated a Blog"}:
    {/if}
    </span><br>
    <a href="{$jamroom_url}/{$item.profile_url}/{$murl}/{$item.action_item_id}/{$item.action_data.blog_title_url}" title="{$item.action_data.blog_title|htmlentities}"><h4>{$item.action_data.blog_title}</h4></a>
</div>
