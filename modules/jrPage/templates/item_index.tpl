{jrCore_module_url module="jrPage" assign="murl"}
<div class="block">

    <div class="title">
        <div class="block_config">
            {jrCore_item_order_button module="jrPage" profile_id=$_profile_id icon="refresh"}
            {jrCore_item_create_button module="jrPage" profile_id=$_profile_id}
        </div>
        <h1>{if isset($_post._1) && strlen($_post._1) > 0}{$_post._1}{else}{jrCore_lang module="jrPage" id="19" default="Pages"}{/if}</h1>
        <div class="breadcrumbs">
            <a href="{$jamroom_url}/{$profile_url}/">{$profile_name}</a> &raquo; <a href="{$jamroom_url}/{$profile_url}/{$murl}">{if isset($_post._1) && strlen($_post._1) > 0}{$_post._1}{else}{jrCore_lang module="jrPage" id="19" default="Pages"}{/if}</a>
        </div>
    </div>

    <div class="block_content">

        {jrCore_list module="jrPage" profile_id=$_profile_id search="page_location = 1" order_by="page_display_order numerical_asc" pagebreak="6" page=$_post.p}

    </div>

</div>
