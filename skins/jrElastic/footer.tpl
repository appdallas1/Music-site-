</div>

<div id="footer">
    <div id="footer_content">
        <div class="container">

            <div class="row">
            {* Logo *}
                <div class="col6">
                    <div id="footer_logo">
                    {jrCore_image image="logo.png" width="150" height="38" alt="Elastic Skin &copy; 2012 The Jamroom Network" title="Elastic Skin &copy; 2012 The Jamroom Network"}
                    </div>
                </div>

            {* Text *}
                <div class="col6 last">
                    <div id="footer_text">
                        &copy;{$smarty.now|date_format:"%Y"} <a href="{$jamroom_url}">{$_conf.jrCore_system_name}</a><br>
                        <span style="font-size:9px;color:#CCC;">Powered by</span> <a href="http://www.jamroom.net"><span style="font-size:9px;">Jamroom</span></a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</div>

{if isset($javascript_footer_href)}
    {foreach from=$javascript_footer_href item="_js"}
    <script type="{$_js.type|default:"text/javascript"}" src="{$_js.source}"></script>
    {/foreach}
{/if}
{if isset($javascript_footer_function)}
<script type="text/javascript">
    {$javascript_footer_function}
</script>
{/if}

{* do not remove this hidden div *}
<div id="jr_temp_work_div" style="display:none"></div>

{* Responsive Menu *}
<script type="text/javascript">

    $(function() {ldelim}
        if ($.browser.msie && $.browser.version.substr(0,1)<7)
        {ldelim}
            $('li').has('ul').mouseover(function(){ldelim}
                $(this).children('ul').css('visibility','visible');
            {rdelim}).mouseout(function(){ldelim}
                        $(this).children('ul').css('visibility','hidden');
                {rdelim})
        {rdelim}

        /* Mobile */
        $('#menu-wrap').prepend('<div id="menu-trigger">{jrCore_lang skin=$_conf.jrCore_active_skin id="20" default="menu"}</div>');
        $("#menu-trigger").on("click", function(){ldelim}
            $("#menu").slideToggle();
        {rdelim});

        // iPad
        var isiPad = navigator.userAgent.match(/iPad/i) != null;
        if (isiPad) $('#menu ul').addClass('no-transition');
    {rdelim});
</script>

</body>
</html>
