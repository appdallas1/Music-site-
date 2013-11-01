{if isset($_items)}

    {foreach from=$_items item="item"}

    {* Activity Updates *}
    {if isset($item.action_text)}
        <div class="action_item_holder">

            <div class="action_item action_item_content">
                {jrCore_module_url module="jrUser" assign="murl"}
                <div class="action_item_media" style="float:left;padding-right:10px">
                    {jrCore_module_function function="jrImage_display" module="jrUser" type="user_image" item_id=$item._user_id size="small" crop="auto" alt=$item.user_name class="action_item_user_img img_shadow"}
                </div>
                <span class="action_item_desc">
                    {if isset($item.action_original_profile_url)}
                        <a href="{$jamroom_url}/{$item.action_original_profile_url}" title="{$item.action_original_profile_name}">@{$item.action_original_profile_name}:</a><br>{$item.action_text|jrCore_convert_at_tags|jrAction_convert_hash_tags|jrCore_string_to_url}
                        <br><span class="action_item_share">Shared by <a href="{$jamroom_url}/{$item.profile_url}" title="{$item.profile_name}">@{$item.profile_name}</a></span>
                    {else}
                        <a href="{$jamroom_url}/{$item.profile_url}" title="{$item.profile_name|htmlentities}">@{$item.profile_name}:</a><br>{$item.action_text|jrCore_convert_at_tags|jrAction_convert_hash_tags|jrCore_string_to_url}
                        {if jrUser_is_logged_in() && $_user._user_id != $item._user_id}
                            {jrCore_module_url module="jrAction" assign="murl"}
                            <br><a href="{$jamroom_url}/{$murl}/share/{$item._item_id}" onclick="if(!confirm('{jrCore_lang module="jrAction" id="9" default="Share this update with your followers?"}')) { return false; }"><span class="action_item_share">{jrCore_lang module="jrAction" id="10" default="Share This"}</span></a>
                        {/if}
                    {/if}
                </span>
            </div>
            <div class="action_item_date">
                {$item._created|jrCore_date_format:"relative"}<br>
                <span style="display:inline-block;margin-top:6px">{jrCore_item_delete_button module="jrAction" profile_id=$item._profile_id item_id=$item._item_id}</span>
            </div>

         </div>

    {* Registered Module Action templates *}
    {elseif isset($item.action_data)}
         <div class="action_item_holder">

             <div class="action_item action_item_content">
                 <div class="action_item_media" style="float:left;padding-right:10px">
                     {jrCore_module_function function="jrImage_display" module="jrUser" type="user_image" item_id=$item._user_id size="small" crop="auto" alt=$item.user_name class="action_item_user_img img_shadow"}
                 </div>
                 {$item.action_data}
             </div>
             <div class="action_item_date">
                 {$item._created|jrCore_date_format:"relative"}<br>
                 <span style="display:inline-block;margin-top:6px">{jrCore_item_delete_button module="jrAction" profile_id=$item._profile_id item_id=$item._item_id}</span>
             </div>

         </div>
    {/if}

    {/foreach}

{/if}
