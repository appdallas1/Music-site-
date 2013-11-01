{jrCore_include template="meta.tpl"}

<body>

<div id="header">
    <div id="header_content">

        {* Logo *}
        <div id="main_logo">
            <a href="{$jamroom_url}">{jrCore_image image="logo.png" width="236" height="55" class="jlogo" alt=$_conf.jrCore_system_name}</a>
        </div>

        <div id="menu_content">
            <nav id="menu-wrap">
                <ul id="menu">

                    {if jrCore_module_is_active('jrSearch')}
                        <li><a onclick="jrSearch_modal_form();" title="Site Search">{jrCore_image image="search24.png" width="24" height="24" alt="search"}</a></li>
                    {/if}

                    {* Add in Cart link if jrFoxyCart module is installed *}
                    {if jrCore_module_is_active('jrFoxyCart') && strlen($_conf.jrFoxyCart_api_key) > 0}
                        <li>
                            <a href="{$_conf.jrFoxyCart_store_domain}/cart?cart=view">{jrCore_image image="cart24.png" width="24" height="24" alt="cart"}</a>
                            <span id="fc_minicart"><span id="fc_quantity"></span></span>
                        </li>
                    {/if}

                    {if jrUser_is_logged_in()}
                        {if jrUser_is_admin()}
                            <li><a href="{$jamroom_url}/{jrCore_module_url module="jrCore"}/dashboard">{jrCore_lang skin=$_conf.jrCore_active_skin id="17" default="dashboard"}</a></li>
                        {/if}
                        <li>
                            <a href="{$jamroom_url}/{jrUser_home_profile_key key="profile_url"}">{jrUser_home_profile_key key="profile_name"}</a>
                            <ul>
                                {jrCore_skin_menu template="menu.tpl" category="user"}
                            </ul>
                        </li>
                    {/if}


                    {* Add additional menu categories here *}

                    {if jrUser_is_logged_in()}
                        {if jrUser_is_master()}
                            {jrCore_module_url module="jrCore" assign="core_url"}
                            {jrCore_get_module_index module="jrCore" assign="url"}
                            <li>
                                <a href="{$jamroom_url}/{$core_url}/admin/global">{jrCore_lang skin=$_conf.jrCore_active_skin id="16" default="ACP"}</a>
                                <ul>
                                    <li>
                                        <a href="{$jamroom_url}/{$core_url}/admin/tools">{jrCore_lang skin=$_conf.jrCore_active_skin id="37" default="System Tools"}</a>
                                        <ul>
                                            <li><a href="{$jamroom_url}/{$core_url}/{$url}">{jrCore_lang skin=$_conf.jrCore_active_skin id="28" default="Activity Logs"}</a></li>
                                            <li><a href="{$jamroom_url}/{$core_url}/cache_reset">{jrCore_lang skin=$_conf.jrCore_active_skin id="29" default="Reset Cache"}</a></li>
                                            <li><a href="{$jamroom_url}/{jrCore_module_url module="jrImage"}/cache_reset">{jrCore_lang skin=$_conf.jrCore_active_skin id="30" default="Reset Image Cache"}</a></li>
                                            <li><a href="{$jamroom_url}/{$core_url}/integrity_check">{jrCore_lang skin=$_conf.jrCore_active_skin id="31" default="Integrity Check"}</a></li>
                                            <li><a href="{$jamroom_url}/{$core_url}/system_check">{jrCore_lang skin=$_conf.jrCore_active_skin id="35" default="System Check"}</a></li>
                                            <li><a href="{$jamroom_url}/{jrCore_module_url module="jrBanned"}/browse">{jrCore_lang skin=$_conf.jrCore_active_skin id="32" default="Banned Items"}</a></li>
                                            <li><a href="{$jamroom_url}/{$core_url}/skin_menu">{jrCore_lang skin=$_conf.jrCore_active_skin id="33" default="Skin Menu Editor"}</a></li>
                                            <li><a href="{$jamroom_url}/{jrCore_module_url module="jrSitemap"}/admin/tools">{jrCore_lang skin=$_conf.jrCore_active_skin id="34" default="Create Sitemap"}</a></li>
                                        </ul>
                                    </li>
                                    <li>
                                        {jrCore_module_url module="jrProfile" assign="purl"}
                                        {jrCore_module_url module="jrUser" assign="uurl"}
                                        <a href="{$jamroom_url}/{$purl}/admin/tools">{jrCore_lang skin=$_conf.jrCore_active_skin id="54" default="Users"}</a>
                                        <ul>
                                            <li><a href="{$jamroom_url}/{$purl}/quota_browser">{jrCore_lang skin=$_conf.jrCore_active_skin id="49" default="Profile Quota Browser"}</a></li>
                                            <li><a href="{$jamroom_url}/{$purl}/browser">{jrCore_lang skin=$_conf.jrCore_active_skin id="52" default="Profile Browser"}</a></li>
                                            <li><a href="{$jamroom_url}/{$uurl}/browser">{jrCore_lang skin=$_conf.jrCore_active_skin id="50" default="User Accounts"}</a></li>
                                            <li><a href="{$jamroom_url}/{$uurl}/online">{jrCore_lang skin=$_conf.jrCore_active_skin id="53" default="Who's Online"}</a></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <a href="{$jamroom_url}/{$core_url}/skin_admin/global/skin=jrElastic">{jrCore_lang skin=$_conf.jrCore_active_skin id="38" default="Skin Settings"}</a>
                                        <ul>
                                            <li><a onclick="popwin('{$jamroom_url}/skins/{$_conf.jrCore_active_skin}/readme.html','readme',600,500,'yes');">skin notes</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>
                        {/if}
                    {else}
                        {jrCore_module_url module="jrUser" assign="uurl"}
                        {if $_conf.jrCore_maintenance_mode != 'on' && $_conf.jrUser_signup_on == 'on'}
                            <li><a href="{$jamroom_url}/{$uurl}/signup">{jrCore_lang skin=$_conf.jrCore_active_skin id="2" default="create"}&nbsp;{jrCore_lang skin=$_conf.jrCore_active_skin id="3" default="account"}</a></li>
                        {/if}
                        <li><a href="{$jamroom_url}/{$uurl}/login">{jrCore_lang skin=$_conf.jrCore_active_skin id="6" default="login"}</a></li>
                    {/if}


                </ul>
            </nav>

            {* This is the search form - shows as a modal window when the search icon is clicked on *}
            <div id="searchform" class="search_box" style="display:none;">
                {jrSearch_form class="form_text" value="Search Site" style="width:70%"}
                <div style="float:right;clear:both;margin-top:3px;">
                    <a class="simplemodal-close">{jrCore_icon icon="close" size="16"}</a>
                </div>
                <div class="clear"></div>
            </div>

        </div>

    </div>
</div>

<div id="wrapper">
    <div id="content">

        <!-- end header.tpl -->
