<?php
session_start();
include "../init.php";
if(!isLoggedIn())
{
  redirect("../login.php");
  exit();
}
if(isLoggedIn())
{
$admin = getUserData()['is_admin'];
if($admin != 1)
{
  redirect("../user");
  exit();
}
}

if(isset($_POST) && !empty($_POST['SITE_TITLE']))
{
 foreach($_POST as $key => $val)
 {
  $db->query("UPDATE `settings` SET `setting_value` = '%s' WHERE `setting_key` = '%s'", $val, $key);
 }
    echo json_encode(array("message" => "Site Settings Successfully Updated" ));
 exit();
}
include "header.php";
?>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<h1>Update Site Setting</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">user</div>
<div class="breadcrumb-item">settings</div>
</div>
</div>
</section>
<div class="row">
<div class="col-12">
<form action="settings.php" method="post" class="ajaxform_with_reload">
<div class="card">
<div class="card-body">
<div class="row">
<div class="col-sm-6">
<div class="mb-3">
<label class="mb-0">Site Tile</label>
<input type="text" class="form-control" name="SITE_TITLE" placeholder="Welcome to" value="<?= settingsInfo("SITE_TITLE"); ?>">
</div>
</div>
<div class="col-sm-6">
<div class="mb-3">
<label class="mb-0">Site Description</label>
<input type="text" class="form-control" name="SITE_DESCRIPTION" placeholder="Short Description..." value="<?= settingsInfo("SITE_DESCRIPTION"); ?>">
</div>
</div>
<div class="col-sm-6">
<div class="mb-3">
<label class="mb-0">Referral Commission (% <font color="red">0 for disable referral system</font>)</label>
<input type="text" class="form-control" name="REFERRAL_COMMISSION" placeholder="eg.. 10" value="<?= settingsInfo("REFERRAL_COMMISSION"); ?>">
</div>
</div>

</div>
<div class="row text-right">
<div class="col">
<button class="btn btn-primary basicbtn"><i class="fas fa-save"></i> SAVE CHANGES</button>
</div>
</div>
</div>
</div>
</form>
</div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" id="photo_cropper_modal" data-backdrop="false">
<div class="modal-dialog" role="document">
<div class="modal-content">
<div class="modal-body">
<div class="form-group overflow-auto">
<img src="" alt="" id="previewImage" style="max-width: 100%">
</div>
<div class="form-group">
<button type="button" id="btnCrop" class="btn btn-primary btn-lg btn-block">Crop</button>
</div>
</div>
</div>
</div>
</div>
</div>

</div>
<?php
include "footer.php";
?>