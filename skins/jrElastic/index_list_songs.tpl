{if isset($_items)}
  {foreach from=$_items item="item"}
  <div style="display:table">
      <div style="display:table-cell">
          <a href="{$jamroom_url}/{$item.profile_url}/{$_params.module_url}/{$item._item_id}/{$item.audio_title_url}">{jrCore_module_function function="jrImage_display" module="jrAudio" type="audio_image" item_id=$item._item_id size="small" crop="auto" alt=$item.audio_title title=$item.audio_title class="iloutline" width=false height=false}</a>
      </div>
      <div class="p5" style="display:table-cell;vertical-align:middle">
          <a href="{$jamroom_url}/{$item.profile_url}/{$_params.module_url}/{$item._item_id}/{$item.audio_title_url}" class="media_title">{$item.audio_title}</a>
      </div>
  </div>
  {/foreach}
{/if}