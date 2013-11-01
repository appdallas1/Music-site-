{if isset($_items)}

    {foreach from=$_items item="item"}
    <div class="item">

        <div class="container">
            <div class="row">
                <div class="col2">
                    <div class="block_image">
                        <a href="{$jamroom_url}/{$item.profile_url}">{jrCore_module_function function="jrImage_display" module="jrProfile" type="profile_image" item_id=$item._profile_id size="large" class="iloutline img_scale" alt=$item.profile_name title=$item.profile_name width=false height=false}</a>
                    </div>
                </div>
                <div class="col10 last">
                    <div class="p10">
                        <h1><a href="{$jamroom_url}/{$item.profile_url}">{$item.profile_name}</a></h1>
                        {if !empty($item.profile_bio)}
                        <br><span class="normal">&quot;{$item.profile_bio|jrEmbed_embed|jrCore_format_string:0|truncate:150:"..."}&quot;</span>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

    </div>
    {/foreach}
{/if}
