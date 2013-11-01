<div class="item">
    <div id="disqus_thread"></div>
    <script type="text/javascript">
        var disqus_shortname = '{$_conf.jrDisqus_site_name|addslashes}';
        var disqus_developer = 1;
        {if strlen($disqus_identifier) > 0}
        var disqus_identifier = '{$disqus_identifier}';
        {/if}
        (function() {
            var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
            dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
            (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
        })();
    </script>
    <noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
    <a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>
</div>
