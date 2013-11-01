{include file="`$jamroom_dir`/skins/`$_conf.jrCore_active_skin`/header.tpl" page_title="Down for Maintenance"}

<div class="container">

  <div class="row">
    <div class="col12 last">
      <div class="block center">
        <div style="padding:30px;">
        <span style="font-size:20px">
            {jrCore_image module="jrCore" image="maintenance.png" alt="{$system_name} is down for maintenance!" style="vertical-align:middle"}
            &nbsp;<b>{$_conf.jrCore_system_name}</b> is down for maintenance!
            <br><br><br>
            {$_conf.jrCore_maintenance_notice|nl2br}
        </span>
        </div>
      </div>
    </div>
  </div>

</div>

{include file="`$jamroom_dir`/skins/`$_conf.jrCore_active_skin`/footer.tpl"}
