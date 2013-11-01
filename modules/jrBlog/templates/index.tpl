{jrCore_include template="header.tpl"}

<div class="block">

    <div class="title">
        <h1>{jrCore_lang module="jrBlog" id="29" default="Blogs"}</h1>
    </div>

    <div class="block_content">

        {jrCore_list module="jrBlog" search1="blog_publish_date <= `$smarty.now`" order_by="blog_publish_date ASC" pagebreak="10" page=$_post.p pager=true}

    </div>

</div>

{jrCore_include template="footer.tpl"}