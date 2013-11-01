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
                    <img src="{$jamroom_url}/modules/jrBlog/img/date_icon.png" alt="published" style="padding-right: 4px;padding-top: 2px;vertical-align: middle;width: 20px;"><span class="normal">{$item.blog_publish_date|jrCore_format_time:false:"%F"}</span><br>
                    <div class="normal p5">
                        {$item.blog_text|jrCore_format_string:$item.profile_quota_id|jrCore_readmore|jrEmbed_embed}
                    </div>
                    {if strpos($item.blog_text,'<!-- pagebreak -->')}{*check to see if the blog has a pagebreak in it *}
                        <div class="info_c right clear">
                            <a href="{$jamroom_url}/{$item.profile_url}/{$murl}/{$item._item_id}/{$item.blog_title_url}">{jrCore_lang module="jrBlog" id="25" default="Read more"} &raquo;</a>
                        </div>
                    {/if}
                    <hr>

                    <span class="info">{jrCore_lang module="jrBlog" id="26" default="Posted in"}:</span> <a href="{$jamroom_url}/{$item.profile_url}/{$murl}/category/{$item.blog_category_url|default:"default"}"><span class="info_c">{$item.blog_category|default:"default"}</span></a>

                    {if jrCore_module_is_active('jrComment')}
                        <span class="info_c"> | <a href="{$jamroom_url}/{$item.profile_url}/{$murl}/{$item._item_id}/{$item.blog_title_url}#comments"> {$item.blog_comment_count|default:0} {jrCore_lang module="jrBlog" id="27" default="comments"} &raquo;</a></span>
                    {/if}

                    <span class="info_c"> | </span>
                    <span class='st_facebook' st_title='{$item.blog_title|escape}' st_url='{$jamroom_url}/{$item.profile_url}/{$murl}/{$item._item_id}/{$item.blog_title_url}'></span><span class='st_twitter' st_title='{$item.blog_title|escape}' st_url='{$jamroom_url}/{$item.profile_url}/{$murl}/{$item._item_id}/{$item.blog_title_url}'></span><span class='st_email' st_title='{$item.blog_title|escape}' st_url='{$jamroom_url}/{$item.profile_url}/{$murl}/{$item._item_id}/{$item.blog_title_url}'></span><span class='st_sharethis'></span>

                </div>

            {/foreach}
        {/if}

{if isset($_post._1) && $_post._1 == 'category'}
    </div>

</div>
{/if}

{* share this http://sharethis.com *}
<script type="text/javascript">var switchTo5x = true;</script>
<script type="text/javascript" src="//w.sharethis.com/button/buttons.js"></script>
<script type="text/javascript">stLight.options({ publisher:"388dfdea-9f20-4afd-ad57-a451dd00eac1" });</script>
