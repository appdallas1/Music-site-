<div class="item">
    <div id="jraction_title">
        <h3>{$item.action_mode}</h3>
        <div class="block_config">
            {jrCore_item_create_button module="jrPage" profile_id=$item._profile_id}
            {jrCore_item_delete_button module="jrPage" profile_id=$item._profile_id item_id=$item._item_id}
        </div>
    </div>
</div>

<div class="item">
    <div id="jrpage_body">
        {$item.page_body|jrCore_format_string:$item.profile_quota_id}
    </div>
</div>

