{if isset($_items)}
{foreach from=$_items key="module" item="entry"}
    {if $entry.active == '1'}
    <a href="{$entry.target}"><div class="profile_menu_entry profile_menu_entry_active">{$entry.label}</div></a>
    {else}
    <a href="{$entry.target}"><div class="profile_menu_entry">{$entry.label}</div></a>
    {/if}
{/foreach}
{/if}

