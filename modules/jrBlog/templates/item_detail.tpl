{jrCore_module_url module="jrBlog" assign="murl"}

<div class="block">

    <div class="title">
        <div class="block_config">
            {jrCore_item_create_button module="jrBlog" profile_id=$item._profile_id}
            {jrCore_item_update_button module="jrBlog" profile_id=$item._profile_id item_id=$item._item_id}
            {jrCore_item_delete_button module="jrBlog" profile_id=$item._profile_id item_id=$item._item_id}
        </div>
        <h1>{$item.blog_title}</h1>
        <div class="breadcrumbs">
            <a href="{$jamroom_url}/{$item.profile_url}">{$item.profile_name}</a> &raquo; <a href="{$jamroom_url}/{$item.profile_url}/{$murl}">{jrCore_lang module="jrBlog" id="24" default="Blog"}</a> &raquo; <a href="{$jamroom_url}/{$item.profile_url}/{$murl}/category/{$item.blog_category_url}">{$item.blog_category}</a> &raquo; {$item.blog_title}
        </div>
    </div>

    <div class="block_content">

        <div class="item blogpost">

            <div class="blog_info" style="font-size: 0.8em">
                {jrCore_module_function function="jrImage_display" module="jrUser" type="user_image" item_id=$item._user_id size="small" class="action_item_user_img iloutline" style="margin-right:12px"}
                <span class="info_c">{$item.blog_publish_date|jrCore_format_time:false:"%F"}</span><br>
                <span class="info">{jrCore_lang module="jrBlog" id="28" default="By"}:</span> <span class="info_c">{$item.user_name}</span> <span class="info">{jrCore_lang module="jrBlog" id="26" default="Posted in"}:</span> <a href="{$jamroom_url}/{$item.profile_url}/{$murl}/category/{$item.blog_category_url}"><span class="info_c">{$item.blog_category}</span></a><br>
                <span style="display:inline-block;margin-top:6px;">{jrCore_module_function function="jrRating_form" type="star" module="jrBlog" index="1" item_id=$item._item_id current=$item.blog_rating_1_average_count|default:0 votes=$item.blog_rating_1_count|default:0}</span>
            </div>

            <div class="normal p5">
                {if isset($item.blog_image_size) && $item.blog_image_size > 0}
                    <div class="float-right">
                        {jrCore_module_function function="jrImage_display" module="jrBlog" type="blog_image" item_id=$item._item_id size="large" alt=$item.blog_title width=false height=false class="iloutline img_shadow" style="margin-left:12px;margin-bottom:12px;"}
                    </div>
                {/if}
                {$item.blog_text|jrCore_format_string:$item.profile_quota_id|jrEmbed_embed}
            </div>
            <div class="clear"></div>

        </div>

        {* share this http://sharethis.com *}
        <script type="text/javascript">var switchTo5x = true;</script>
        <script type="text/javascript" src="//w.sharethis.com/button/buttons.js"></script>
        <script type="text/javascript">stLight.options({ publisher:"388dfdea-9f20-4afd-ad57-a451dd00eac1" });</script>

        {* Are tags enabled for this item? *}
        {jrTags_add module="jrBlog" profile_id=$item._profile_id item_id=$item._item_id}

        {* Are comments enabled for this blog? *}
        {jrComment_form module="jrBlog" profile_id=$item._profile_id item_id=$item._item_id}

        {jrDisqus_comments disqus_identifier="jrBlog_`$item._item_id`"}

    </div>

</div>
