{if isset($_items)}
{foreach from=$_items item="item"}
  <div class="item">

      <div class="container">
          <div class="row">
              <div class="col2">
                  <div class="block_image">
                      <a href="{jrProfile_item_url module="jrPage" profile_url=$item.profile_url item_id=$item._item_id title=$item.page_title}">{jrCore_module_function function="jrImage_display" module="jrUser" type="user_image" class="iloutline" item_id=$item._user_id size="small" crop="auto" alt=$item.user_name}</a>
                  </div>
              </div>
              <div class="col10 last">
                  <div class="p5">
                      <h3><a href="{jrProfile_item_url module="jrPage" profile_url=$item.profile_url item_id=$item._item_id title=$item.page_title}">{$item.page_title}</a></h3><br>
                      <span class="normal">{$item.page_body|jrEmbed_embed|strip_tags|truncate:180}</span>
                  </div>
              </div>
          </div>

      </div>

  </div>
{/foreach}
{/if}
