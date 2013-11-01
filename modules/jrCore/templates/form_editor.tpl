tinyMCE.init({
    mode : "exact",
    elements: "{$field_name}",
    theme: "{$theme}",
    width: "100%",
    height: "100%",
    media: "strict",
    apply_source_formatting: false,
    remove_linebreaks: true,
    relative_urls: false,
    convert_urls: false,
    entity_encoding : "raw",
    plugins:'pagebreak,autoresize,{if $jrembed}jrembed,emotions,media{/if}',
    theme_advanced_statusbar_location: "none",
    theme_advanced_blockformats: "p,{if $pre}pre,{/if}{if $h1}h1,{/if}{if $h2}h2,{/if}{if $h3}h3,{/if}{if $h4}h4,{/if}",
    theme_advanced_buttons1: "{if $strong}bold,{/if}{if $em}italic,{/if}{if $span}underline,{/if}{if $span}strikethrough,{/if},|,{if $span}justifyleft,{/if}{if $span}justifycenter,{/if}{if $span}justifyright,{/if}{if $span}justifyfull,{/if}|,formatselect,{if $ul && $li}bullist,{/if}{if $ol && $li}numlist,{/if}|,{if $span}outdent,{/if}{if $span}indent,{/if}|,undo,redo,|,link,unlink,anchor,image,pagebreak,{if $hr}hr,{/if}{if $sub && $sub}|,sub,sup,{/if}{if $jrembed}|,emotions,jrembed,{/if}code"
});