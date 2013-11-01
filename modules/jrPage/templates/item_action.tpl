{jrCore_module_url module="jrPage" assign="murl"}
<div class="p5">
    <h4>{jrCore_lang module="jrPage" id="18" default="Created a new Page"}:</h4><br>
    <h3><a href="{$jamroom_url}/{$item.profile_url}/{$murl}/{$item.action_item_id}/{$item.action_data.page_title|jrCore_url_string}" title="{$item.action_data.page_title|htmlentities}">{$item.action_data.page_title}</a></h3>
</div>
