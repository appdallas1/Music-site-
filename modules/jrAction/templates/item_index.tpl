<div class="block">

    <div class="title">
        <div class="block_config">
            {jrCore_item_create_button module="jrAction" profile_id=$_profile_id title="Create"}
        </div>
        <h2>{jrCore_lang module="jrAction" id="11" default="Activity Stream"}</h2>
    </div>

    <div class="block_content">

        {jrCore_list module="jrAction" profile_id=$_profile_id order_by="_item_id desc"}

    </div>

</div>
