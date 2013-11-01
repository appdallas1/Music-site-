{* page_notice shows notices, warnings, errors and success messages under a page_banner *}
{* NOTE: This is NOT SHOWN for pages submitted via AJAX *}
{* $notice_label will contain the actual notice level - i.e. "error, "success", "warning", "notice" *}
<tr>
  <td colspan="2" class="page_notice_drop"><div id="page_notice" class="page_notice {$notice_type}">{$notice_text}</div></td>
</tr>
