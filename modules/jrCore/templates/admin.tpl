{jrCore_module_url module="jrCore" assign="core_url"}

<div id="admin_container" class="container">
  <div class="row">

    <div class="col3">
      <div class="item-list">
        <table>
          <tr>
            <td class="page_tab_bar_holder">
              <ul class="page_tab_bar">
                {if isset($active_tab) && $active_tab == 'skins'}
                  <li id="mtab" class="page_tab page_tab_first"><a href="{$jamroom_url}/{$core_url}/admin">modules</a></li>
                  <li id="stab" class="page_tab page_tab_last page_tab_active"><a href="{$jamroom_url}/{$core_url}/skin_admin">skins</a></li>
                {else}
                  <li id="mtab" class="page_tab page_tab_first page_tab_active"><a href="{$jamroom_url}/{$core_url}/admin">modules</a></li>
                  <li id="stab" class="page_tab page_tab_last"><a href="{$jamroom_url}/{$core_url}/skin_admin">skins</a></li>
                {/if}
              </ul>
            </td>
          </tr>
        </table>
        <div id="item-holder">

        {if isset($_modules)}

        {* CATEGORIES *}
        <dl class="accordion">
        {foreach name="loop" from=$_modules key="category" item="_mods"}

        <dt class="page_section_header"><a href="">{$category}</a></dt>
        <dd id="c{$category}">

            {* MODULES *}
            {foreach from=$_mods key="mod_dir" item="_mod"}
            {jrCore_get_module_index module=$mod_dir assign="url"}
            <a href="{$jamroom_url}/{$_mod.module_url}/{$url}">
            {if isset($_post.module) && $_post.module == $mod_dir}
            <div class="item-row item-row-active">
            {else}
            <div class="item-row">
            {/if}
            <div class="item-icon"><img src="{$jamroom_url}/modules/{$mod_dir}/icon.png" width="48" height="48" alt="{$_mod.module_name}"></div>
            <div class="item-entry">{$_mod.module_name}</div>
            <div class="item-enabled">
            {if $_mod.module_active != '1'}
                <span class="item-disabled" title="module is currently disabled">D</span>
            {/if}
            </div>
            </div></a>
            {/foreach}

        </dd>

        {/foreach}
        </dl>

        {else}

        <div class="accordion">
        <dt class="page_section_header">skins</dt>
        <div style="padding:3px 0">
        {* SKINS *}
        {foreach from=$_skins key="skin_dir" item="_skin"}
        <a href="{$jamroom_url}/{$core_url}/skin_admin/info/skin={$skin_dir}">
        {if (isset($_post.skin) && $_post.skin == $skin_dir) || (!isset($_post.skin) && $skin_dir == $_conf.jrCore_active_skin) }
        <div class="item-row item-row-active">
        {else}
        <div class="item-row">
        {/if}
          <div class="item-icon"><img src="{$jamroom_url}/skins/{$skin_dir}/icon.png" width="48" height="48" alt="{$_skin.name}"></div>
          <div class="item-entry">{$_skin.name}</div>
          <div class="item-enabled">
          </div>
        </div></a>
        {/foreach}
        </div>
        </div>

        {/if}

        </div>
      </div>
    </div>
          
    <div class="col9 last">
      <div id="item-work">
        {$admin_page_content}
      </div>
    </div>

  </div>
</div>
