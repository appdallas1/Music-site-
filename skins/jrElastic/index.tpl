{jrCore_include template="header.tpl"}

<div class="container">

    <div class="row">

        <div class="col3">

            <div class="block">
                <div class="title"><h2>{jrCore_lang skin=$_conf.jrCore_active_skin id="11" default="newest"}&nbsp;{jrCore_lang skin=$_conf.jrCore_active_skin id="12" default="profiles"}</h2></div>
                <div class="block_content">
                    <div class="item">
                        {jrCore_list module="jrProfile" order_by="_created desc" search1="profile_active = 1" template="index_list_profiles.tpl" limit="5" require_image="profile_image"}
                    </div>
                    <div class="normal right"><a href="{$jamroom_url}/profile">{jrCore_lang skin=$_conf.jrCore_active_skin id="23" default="see all"}&nbsp;&raquo;</a></div>
                </div>
            </div>

            <div class="block">
                <div class="title"><h2><a href="{$jamroom_url}/blog" title="Blogs">{jrCore_lang skin=$_conf.jrCore_active_skin id="8" default="site"}&nbsp;{jrCore_lang skin=$_conf.jrCore_active_skin id="42" default="Blogs"}</a></h2></div>
            </div>

            <div class="block">
                <div class="title"><h2>{jrCore_lang skin=$_conf.jrCore_active_skin id="19" default="events"}</h2></div>
                <div class="block_content">
                    <div class="item">
                        {jrCore_list module="jrEvent" order_by="_created desc" template="index_list_events.tpl" limit="5" require_image="event_image"}
                    </div>
                    <div class="normal right"><a href="{$jamroom_url}/event">{jrCore_lang skin=$_conf.jrCore_active_skin id="23" default="see all"}&nbsp;&raquo;</a></div>
                </div>
            </div>

        </div>

        <div class="col6">

            <script>
                $(function () {ldelim}

                    // Slideshow 1
                    $("#slider1").responsiveSlides({ldelim}
                        auto: true,          // Boolean: Animate automatically, true or false
                        speed: 400,          // Integer: Speed of the transition, in milliseconds
                        timeout: 4000,       // Integer: Time between slide transitions, in milliseconds
                        pager: true,         // Boolean: Show pager, true or false
                        random: true,        // Boolean: Randomize the order of the slides, true or false
                        pause: true,         // Boolean: Pause on hover, true or false
                        maxwidth: 512,       // Integer: Max-width of the slideshow, in pixels
                        namespace: "rslides" // String: change the default namespace used
                        {rdelim});

                    {rdelim});
            </script>
            <div class="block">
                <div class="title"><h2>{jrCore_lang skin=$_conf.jrCore_active_skin id="21" default="featured"}&nbsp;{jrCore_lang skin=$_conf.jrCore_active_skin id="12" default="profiles"}</h2></div>
                <div class="block_content">
                    <div id="swrapper" style="padding-top:10px;">
                        <div class="callbacks_container">
                            <div class="ioutline">
                                <ul id="slider1" class="rslides callbacks">
                                    {if isset($_conf.jrElastic_profile_ids) && strlen($_conf.jrElastic_profile_ids) > 0}
                                        {jrCore_list module="jrProfile" order_by="_created desc" limit="10" search1="_profile_id in `$_conf.jrElastic_profile_ids`" search2="profile_active = 1" template="index_featured_slider.tpl" require_image="profile_image"}
                                    {else}
                                        {jrCore_list module="jrProfile" order_by="_created desc" limit="10" search1="profile_active = 1" template="index_featured_slider.tpl" require_image="profile_image"}
                                    {/if}
                                </ul>
                            </div>
                        </div>
                        <div class="clear"></div>
                    </div>
                </div>

                <div style="margin-top:7px;">
                    {if jrCore_module_is_active('jrFeed')}
                        {if jrCore_module_is_active('jrDisqus')}
                            {jrFeed_list name="all disqus site comments"}
                        {else}
                            {jrFeed_list name="jamroom facebook page"}
                        {/if}
                    {elseif jrCore_module_is_active('jrComment')}
                        <div class="title"><h2>{jrCore_lang skin=$_conf.jrCore_active_skin id="48" default="Latest Comments"}</h2></div>
                        <div class="block_content">
                            {jrCore_list module="jrComment" order_by="_created desc" limit="5"}
                        </div>
                    {/if}
                </div>


                {* sitewide tag cloud*}
                {jrTags_cloud height="300" assign="tag_cloud"}
                {if strlen($tag_cloud) > 0}
                    <div class="title"><h2>{jrCore_lang module="jrTags" id="6" default="Tag Cloud"}</h2></div>
                    <div class="block_content">
                        {$tag_cloud}
                    </div>
                {/if}

            </div>

        </div>

        <div class="col3 last">

            <div class="block">
                <div class="title"><h2>{jrCore_lang skin=$_conf.jrCore_active_skin id="11" default="newest"}&nbsp;{jrCore_lang skin=$_conf.jrCore_active_skin id="13" default="songs"}</h2></div>
                <div class="block_content">
                    <div class="item">
                        {jrCore_list module="jrAudio" order_by="_created desc" template="index_list_songs.tpl" limit="5" require_image="audio_image"}
                    </div>
                    <div class="normal right"><a href="{$jamroom_url}/audio">{jrCore_lang skin=$_conf.jrCore_active_skin id="23" default="see all"}&nbsp;&raquo;</a></div>
                </div>
            </div>

            {if isset($_mods.jrRecommend.module_active) && $_mods.jrRecommend.module_active == 1}
                <div class="block">
                    <div class="title"><h2><a onclick="jrRecommend_modal_form();" title="Find New Music">{jrCore_lang skin=$_conf.jrCore_active_skin id="26" default="find new music"}</a></h2></div>
                    {* This is the recommend form - shows as a modal window when the recommend icon is clicked on *}
                    <div id="recommendform" class="recommend_box" style="display:none;">
                        <div style="float:right;"><input type="button" class="simplemodal-close form_button" value="x"></div>
                        <h3>{$_conf.jrCore_system_name}<br>{jrCore_lang skin=$_conf.jrCore_active_skin id="26" default="find new music"}</h3><br><br>
                        {jrRecommend_form class="form_text" style="width:70%"}
                        <div class="clear"></div>
                    </div>
                </div>
            {/if}

            <div class="block">
                <div class="title"><h2>{jrCore_lang skin=$_conf.jrCore_active_skin id="11" default="newest"}&nbsp;{jrCore_lang skin=$_conf.jrCore_active_skin id="14" default="videos"}</h2></div>
                <div class="block_content">
                    <div class="item">
                        {jrCore_list module="jrVideo" order_by="_created desc" template="index_list_videos.tpl" limit="5" require_image="video_image"}
                    </div>
                    <div class="normal right"><a href="{$jamroom_url}/video">{jrCore_lang skin=$_conf.jrCore_active_skin id="23" default="see all"}&nbsp;&raquo;</a></div>
                </div>
            </div>

        </div>

    </div>

</div>

{jrCore_include template="footer.tpl"}

