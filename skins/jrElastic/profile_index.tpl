{* default index for profile *}

<div class="col8 last">


{if jrProfile_is_profile_owner($_profile_id)}

    <div class="block">

        <div class="title" style="padding:12px">
            {jrCore_module_url module="jrAction" assign="murl"}

            {if jrCore_module_is_active('jrFeed')}
                {jrCore_module_url module="jrFeed" assign="furl"}
                {jrCore_lang module="jrElastic" id="51" default="activity feed" assign="title"}
                <a href="{$jamroom_url}/{$furl}/{$murl}/{$profile_url}" title="{$title}">{jrCore_icon icon="rss" size="20"}</a>&nbsp;
            {/if}

            {if isset($_post.profile_actions) && $_post.profile_actions == 'mentions'}

            <h2><a href="{$jamroom_url}/{$profile_url}/{$murl}/mentions" title="timeline">{jrCore_lang module="jrAction" id="7" default="Mentions"}</a></h2>
            <div style="float:right">
                {jrCore_lang module="jrAction" id="4" default="Timeline" assign="timeline"}
                <h3><a href="{$jamroom_url}/{$profile_url}/{$murl}/timeline" title="{$timeline}">{$timeline}</a></h3>&nbsp; &bull;

            {else}

            <h2><a href="{$jamroom_url}/{$profile_url}/{$murl}/timeline" title="timeline">{jrCore_lang module="jrAction" id="4" default="Timeline"}</a></h2>
            <div style="float:right">
                {jrCore_lang module="jrAction" id="7" default="Mentions" assign="mention"}
                <h3><a href="{$jamroom_url}/{$profile_url}/{$murl}/mentions" title="{$mention}">{$mention}</a></h3>&nbsp; &bull;

            {/if}

            {jrCore_lang module="jrAction" id="8" default="Search" assign="search"}
            <h3>&nbsp;<a href="#" onclick="$('#action_search').slideToggle(300)">{$search}</a>&nbsp;&nbsp;<a href="#" onclick="$('#action_search').slideToggle(300)" title="{$search}">{jrCore_icon icon="arrow-down" size="20"}</a></h3>
            </div>

        </div>

        <div class="block_content">

            <div id="action_search" class="item left p10" style="display:none">
                {jrCore_lang skin=$_conf.jrCore_active_skin id="27" default="search activity stream" assign="svar"}
                <form name="action_search_form" action="{$jamroom_url}/{$profile_url}/{$murl}/search" method="post" style="margin-bottom:0">
                    <input type="text" name="ss" value="{$svar}" class="form_text" onfocus="if(this.value=='{$svar}'){ldelim} this.value=''; {rdelim}" onblur="if(this.value==''){ldelim} this.value='{$svar}'; {rdelim}">&nbsp;
                    <input type="submit" class="form_button" value="{$search}">
                </form>
            </div>

            {* we only show the new action form to the profile owner *}
            {if jrUser_is_linked_to_profile($_profile_id)}
                <div id="new_action" class="item">
                    <small>{jrCore_lang module="jrAction" id="3" default="Post a new Activity Update"}:</small><br>
                    {jrAction_form}
                </div>
            {/if}


            {* if we are viewing our own profile, include profile updates for profiles we follow *}
            <div class="item">

                <div id="timeline">

                    {assign var="page_num" value="1"}
                    {if isset($_post.p)}
                        {assign var="page_num" value=$_post.p}
                    {/if}

                    {* See what we are loading - time line or mentions *}
                    {if isset($_post.profile_actions) && $_post.profile_actions == 'mentions'}
                        {jrCore_list module="jrAction" search1="_profile_id != `$_profile_id`" search2="action_text regexp @`$profile_url`[[:>:]]" order_by="_item_id numerical_desc" pagebreak="12" page=$page_num pager=true}
                    {elseif isset($_post.profile_actions) && $_post.profile_actions == 'search'}
                        {jrCore_list module="jrAction" search="action_text like %`$_post.ss`%" order_by="_item_id numerical_desc" pagebreak="12" page=$page_num pager=true}
                    {else}
                    {* If we are the site owner, we include action updates for profiles we follow *}
                        {if $_profile_id == $user_home_profile_id}
                            {jrCore_list module="jrAction" profile_id=$_profile_id include_followed=true order_by="_item_id numerical_desc" pagebreak="12" page=$page_num pager=true}
                        {else}
                            {jrCore_list module="jrAction" profile_id=$_profile_id order_by="_item_id numerical_desc" pagebreak="12" page=$page_num pager=true}
                        {/if}
                    {/if}

                </div>

            </div>

        </div>

    </div>

{else}

    <div class="block">

        <div class="title">
            <h2>{jrCore_lang module="jrAction" id="4" default="Profile Updates"}</h2>
            {if jrCore_module_is_active('jrFeed')}
                <div style="float:right">
                    {jrCore_module_url module="jrFeed" assign="furl"}
                    {jrCore_module_url module="jrAction" assign="murl"}
                    <a href="{$jamroom_url}/{$furl}/{$murl}/{$profile_url}">{jrCore_icon icon="rss" size="20"}</a>
                </div>
            {/if}
        </div>

        <div class="block_content">
            <div class="item">
                <div id="timeline">
                    {if isset($_post.p)}
                        {jrCore_list module="jrAction" profile_id=$_profile_id order_by="_item_id numerical_desc" pagebreak="12" page=$_post.p pager=true}
                    {else}
                        {jrCore_list module="jrAction" profile_id=$_profile_id order_by="_item_id numerical_desc" pagebreak="12" page="1" pager=true}
                    {/if}
                </div>
            </div>
        </div>

    </div>

{/if}

