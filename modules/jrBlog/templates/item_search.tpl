{jrCore_module_url module="jrBlog" assign="murl"}
{if isset($_post._1) && $_post._1 == 'category'}
<div class="block">

    <div class="title">
        <div class="block_config">
            {jrCore_item_create_button module="jrBlog" profile_id=$_profile_id}
        </div>
        <h1>{jrCore_lang module="jrBlog" id="20" default="Category"}: {$_items[0].blog_category|default:"default"}</h1>
        <div class="breadcrumbs">
            <a href="{$jamroom_url}/{$profile_url}">{$profile_name}</a> &raquo; <a href="{$jamroom_url}/{$profile_url}/{$murl}">{jrCore_lang module="jrBlog" id="24" default="Blog"}</a> &raquo; {$_items[0].blog_category|default:"default"}
        </div>
    </div>

    <div class="block_content">
{/if}

        {if isset($_items)}
            {foreach from=$_items item="item"}
                <div class="item">

                    <div class="block_config">
                        {jrCore_item_update_button module="jrBlog" profile_id=$item._profile_id item_id=$item._item_id}
                        {jrCore_item_delete_button module="jrBlog" profile_id=$item._profile_id item_id=$item._item_id}
                    </div>

                    <h2><a href="{$jamroom_url}/{$item.profile_url}/{$murl}/{$item._item_id}/{$item.blog_title_url}">{$item.blog_title}</a></h2>
                    <br>
                    <div class="normal p5" style="margin-top:6px">
                        {$item.blog_text|truncate:200:"..."|jrCore_format_string:$item.profile_quota_id|jrCore_readmore|jrEmbed_embed}
                    </div>

                </div>
            {/foreach}
        {/if}

{if isset($_post._1) && $_post._1 == 'category'}
    </div>

</div>
{/if}