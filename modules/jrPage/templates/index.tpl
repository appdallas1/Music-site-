{jrCore_include template="header.tpl"}

<div class="block">

    <div class="title">
        <h1>{jrCore_lang module="jrPage" id="19" default="Pages"}</h1>
    </div>

    <div class="block_content">

        {jrCore_list module="jrPage" profile_id=$_profile_id search="page_location = 1" order_by="_created desc" pagebreak="6" page=$_post.p}

    </div>

</div>

{jrCore_include template="footer.tpl"}
