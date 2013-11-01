{if isset($_items)}
  {foreach from=$_items item="row"}
  <div style="display:table">
      <div style="display:table-cell">
          <a href="{$jamroom_url}/{$row.profile_url}">{jrCore_module_function function="jrImage_display" module="jrProfile" type="profile_image" item_id=$row._profile_id size="small" crop="auto" alt=$row.profile_name title=$row.profile_name class="iloutline"}</a>
      </div>
      <div class="p5" style="display:table-cell;vertical-align:middle">
          <a href="{$jamroom_url}/{$row.profile_url}" class="media_title">{$row.profile_name}</a>
      </div>
  </div>
  {/foreach}
{/if}
