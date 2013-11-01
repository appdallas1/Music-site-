<table class="page_section_header jrcore_file_detail">
    <tr>
        <td class="jrcore_file_detail_left">
            <a href="{$jamroom_url}/{$item.module_url}/download/{$item.field_name}/{$item._item_id}"><img src="{$_conf.jrCore_base_url}/modules/jrFile/img/{$item.extension}.png" width="32" height="32"></a>
        </td>
        <td class="jrcore_file_detail_right">
            <span class="jrcore_file_title">{jrCore_lang module="jrCore" id="74" default="name"}:&nbsp;</span> {$item.name}<br>
            <span class="jrcore_file_title">{jrCore_lang module="jrCore" id="75" default="size"}:&nbsp;</span> {$item.size|jrCore_format_size}<br>
            <span class="jrcore_file_title">{jrCore_lang module="jrCore" id="76" default="date"}:&nbsp;</span> {$item.time|jrCore_format_time}
        </td>
    </tr>
</table>