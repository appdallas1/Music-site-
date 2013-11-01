<!-- user_login.tpl  start -->
{jrCore_form_create_session module="jrUser" option="login" assign="token"}
<div id="jrUser_login_msg"></div>
<form id="jrUser_login" name="jrUser_login" action="{$jamroom_url}/user/login_save" method="post" accept-charset="utf-8" enctype="multipart/form-data">
<input type="hidden" id="jr_html_form_token" name="jr_html_form_token" value="{$token}">
<input type="hidden" name="user_remember" value="off">
user login <input type="text" id="user_email_or_name" class="form_text" name="user_email_or_name" value=""><br>
password <input type="password" id="user_password" class="form_text" name="user_password" value=""><br>
<input type="button" id="jrUser_login_submit" class="form_button" value="login" tabindex="3" onclick="jrFormSubmit('#jrUser_login','{$token}','ajax');">
</form>
<img id="form_submit_indicator" src="http://127.0.0.1/~brianj/Jamroom5/skins/jrElastic/img/submit.gif" width="24" height="24" alt="working...">
<!-- user_login.tpl end -->
