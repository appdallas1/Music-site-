{jrCore_module_url module="jrPage" assign="murl"}
{if $item.page_location == 1}

    {*this is on the profile.*}
    <div class="block">

        <div class="title">
            <div class="block_config">
                {jrCore_item_create_button module="jrPage" profile_id=$item._profile_id}
                {jrCore_item_update_button module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}
                {jrCore_item_delete_button module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}
            </div>
            <h1>{$item.page_title}</h1>
            <div class="breadcrumbs">
                <a href="{$jamroom_url}/{$item.profile_url}/{$murl}">{jrCore_lang module="jrPage" id="19" default="Pages"}</a> &raquo; {$item.page_title}
            </div>
        </div>

        <div class="block_content">

            <div class="item">
                <div id="jrpage_body">
                    <div class="normal">
                        {jrCore_module_function function="jrImage_display" module="jrUser" type="user_image" class="ioutline img_shadow" item_id=$item._user_id alt=$item.user_name size="medium" style="float:right;margin-left:8px;margin-bottom:8px;"}
                        {$item.page_body|jrCore_format_string:$item.profile_quota_id|jrEmbed_embed}
                    </div>
                </div>
            </div>

            {* Are tags enabled for this item? *}
            {jrTags_add module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}

            {* Are comments enabled for this page? *}
            {jrComment_form module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}

            {jrDisqus_comments disqus_identifier="jrPage_`$item._item_id`"}

        </div>

    </div>

{else}

    {*this is on the main site.*}
    <div class="block">

        <div class="title">
            <div class="block_config">
                {jrCore_item_create_button module="jrPage" profile_id=$item._profile_id}
                {jrCore_item_update_button module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}
                {jrCore_item_delete_button module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}
            </div>
            <h1>{$item.page_title}</h1>
            <div class="breadcrumbs">
                <a href="{$jamroom_url}/">{jrCore_lang module="jrPage" id="20" default="home"}</a> &raquo; {$item.page_title}
            </div>
        </div>

        <div class="block_content">

            <div class="item">
                <div id="jrpage_body">
                    <div class="normal">
                        {$item.page_body|jrCore_format_string:$item.profile_quota_id|jrEmbed_embed}
                    </div>
                </div>
            </div>

            {* Are tags enabled for this item? *}
            {jrTags_add module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}

            {* Are comments enabled for this page? *}
            {jrComment_form module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}

            {jrDisqus_comments disqus_identifier="jrPage_`$item._item_id`"}

        </div>

    </div>

{/if}
