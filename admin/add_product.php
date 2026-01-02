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

if(isset($_POST['product_name']))
{
 $upDir = "../panel-assets/uploads/";
 $pname = trim($_POST['product_name']);
 $pslug = trim($_POST['slug']);
 $price = intval($_POST['price']);
 $description = trim($_POST['description']);
 $ldescription = trim($_POST['ldescription']);
 $product_type = intval($_POST['product_type']);
 $is_require_data = intval($_POST['is_require_data']);
 $text_data = trim($_POST['text-data']);
 $require_data = $_POST['inputs'] ?? [];
 $duration_in = trim($_POST['duration_in']);
 $duration = trim(intval($_POST['duration']));

 // Automation fields
 $activation_type = trim($_POST['activation_type'] ?? 'manual');
 $is_free = intval($_POST['is_free'] ?? 0);
 $auto_approve = intval($_POST['auto_approve'] ?? 0);
 $activation_api_url = trim($_POST['activation_api_url'] ?? '');
 $activation_api_method = trim($_POST['activation_api_method'] ?? 'GET');
 $activation_api_key = trim($_POST['activation_api_key'] ?? '');
 $activation_api_params = trim($_POST['activation_api_params'] ?? '');
 $activation_response_mapping = trim($_POST['activation_response_mapping'] ?? '');
 $activation_instructions_template = trim($_POST['activation_instructions_template'] ?? '');

 // Email validation fields
 $requires_email_validation = intval($_POST['requires_email_validation'] ?? 0);
 $email_validation_api_url = trim($_POST['email_validation_api_url'] ?? '');
 $email_validation_api_params = trim($_POST['email_validation_api_params'] ?? '');
 $email_validation_success_key = trim($_POST['email_validation_success_key'] ?? '');
 $email_validation_success_value = trim($_POST['email_validation_success_value'] ?? '');

 // Renewal fields
 $is_renewable = intval($_POST['is_renewable'] ?? 0);
 $renewal_api_url = trim($_POST['renewal_api_url'] ?? '');
 $renewal_api_params = trim($_POST['renewal_api_params'] ?? '');
 $renewal_plans = trim($_POST['renewal_plans'] ?? '');

 if(empty($pname))
 {
   http_response_code(400);
   echo json_encode(array("Product Name Is Requeired." ));
   exit();
 }
 else if(empty($description))
 {
   http_response_code(400);
   echo json_encode(array("Product Description Is Requeired." ));
   exit();
 }
 $db->query("SELECT `id` FROM `products` WHERE `product_name` = '%s'", $pname);
 $num = $db->num_rows();
 if($num > 0)
 {
   http_response_code(400);
   echo json_encode(array("Same Name Product Already Exist." ));
   exit();
 }
 else
 {
  $rdata = "";
  if($is_require_data == 1 && !empty($require_data))
  {
    foreach($require_data as $rqdata)
    {
      $label = $rqdata['label'] ?? '';
      $name = strtolower(str_replace(' ', '_', $label));
      $type = $rqdata['type'] ?? 'text';
      if(empty($label))
      {
       $is_require_data = 0;
      }
      $rdata .= ''.$name.'<|>'.$label.'<|>'.$type.'&&';
    }
  }
  if($product_type == 2)
  {
   $product = $text_data;
  }
  elseif($product_type == 1)
  {
   $file = $_FILES["product"];
   $filename = mt_rand(1111111111, 9999999999) . "-" . $file["name"];
   $product = $filename;
   move_uploaded_file($file["tmp_name"], $upDir . $filename);
  }
  else
  {
   http_response_code(400);
   echo json_encode(array("Invalid Product Type Provided." ));
   exit();
  }
   $coverfile = $_FILES["cover_local"];
   $cfilename = mt_rand(1111111111, 9999999999) . "-" . $coverfile["name"];
   move_uploaded_file($coverfile["tmp_name"], $upDir . $cfilename);

   // Insert product with all new automation fields
   $db->query("INSERT INTO `products` (
       `product_name`, `product_image`, `product_type`, `product`, `price`,
       `is_require_data`, `require_data`, `product_description`, `long_product_description`,
       `date_add`, `slug`,
       `activation_type`, `is_free`, `auto_approve`,
       `activation_api_url`, `activation_api_method`, `activation_api_key`,
       `activation_api_params`, `activation_response_mapping`, `activation_instructions_template`,
       `requires_email_validation`, `email_validation_api_url`, `email_validation_api_params`,
       `email_validation_success_key`, `email_validation_success_value`,
       `is_renewable`, `renewal_api_url`, `renewal_api_params`, `renewal_plans`
   ) VALUES(
       '%s','%s','%d','%s','%d',
       '%d','%s','%s','%s',
       '%d','%s',
       '%s','%d','%d',
       '%s','%s','%s',
       '%s','%s','%s',
       '%d','%s','%s',
       '%s','%s',
       '%d','%s','%s','%s'
   )",
       $pname, $cfilename, $product_type, $product, $price,
       $is_require_data, $rdata, $description, $ldescription,
       time(), $pslug,
       $activation_type, $is_free, $auto_approve,
       $activation_api_url, $activation_api_method, $activation_api_key,
       $activation_api_params, $activation_response_mapping, $activation_instructions_template,
       $requires_email_validation, $email_validation_api_url, $email_validation_api_params,
       $email_validation_success_key, $email_validation_success_value,
       $is_renewable, $renewal_api_url, $renewal_api_params, $renewal_plans
   );

   $insid = $db->inserted_id();
   if(!empty($duration_in) && !empty($duration))
   {
    $db->query("UPDATE `products` SET `expiry_duration` = '%d', `expiry_duration_in` = '%s' WHERE `id` = '%d'", $duration, $duration_in, $insid);
   }
   echo json_encode(array("message" => "New Product Successfully Added", "redirect" => "products.php" ));
   exit();
 }
 exit();
}

include "header.php";
?>
<style>
.automation-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.automation-section h5 {
    color: #495057;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #007bff;
}
.automation-section .form-group label {
    font-weight: 600;
    color: #495057;
}
.automation-section .form-text {
    font-size: 12px;
    color: #6c757d;
}
.json-help {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
    padding: 10px;
    margin-top: 5px;
    font-size: 12px;
}
.json-help code {
    background: #f8f9fa;
    padding: 2px 5px;
    border-radius: 3px;
}
.nav-tabs .nav-link {
    font-weight: 600;
}
.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}
.tab-content {
    padding: 20px 0;
}
.renewal-plan-row {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
}
</style>
<div class="main-content  main-wrapper-1">
<section class="section">
<div class="section-header">
<div class="section-header-back">
<a href="products.php" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
</div>
<h1>Add New Product</h1>
<div class="section-header-breadcrumb">
<div class="breadcrumb-item">admin</div>
<div class="breadcrumb-item">products</div>
<div class="breadcrumb-item">create</div>
</div>
</div>
</section>
<form action="add_product.php" method="post" enctype="multipart/form-data" class="ajaxform_with_redirect">
<div class="col-lg-12">
<div class="card">
<div class="card-body">

<!-- Navigation Tabs -->
<ul class="nav nav-tabs" id="productTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">
            <i class="fas fa-info-circle"></i> Basic Info
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="automation-tab" data-toggle="tab" href="#automation" role="tab">
            <i class="fas fa-cogs"></i> Automation
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="email-validation-tab" data-toggle="tab" href="#email-validation" role="tab">
            <i class="fas fa-envelope-open-text"></i> Email Validation
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="renewal-tab" data-toggle="tab" href="#renewal" role="tab">
            <i class="fas fa-sync-alt"></i> Renewal
        </a>
    </li>
</ul>

<div class="tab-content" id="productTabsContent">

<!-- Basic Info Tab -->
<div class="tab-pane fade show active" id="basic" role="tabpanel">
<div class="form-row">
<div class="col-md-6">
<div class="form-group">
<label>Product Name</label>
<input type="text" class="form-control" placeholder="Enter Product Name" required="" name="product_name">
</div>
</div>
<div class="transaction_fixed col-lg-6 col-md-6">
<div class="form-group">
<label>Price</label>
<input type="number" class="form-control" name="price" id="price" placeholder="Enter Product Price" required="">
</div>
</div>
</div>

<div class="form-row">
<div class="col-md-12">
<div class="form-group">
<label>Product Slug (SEO friendly)</label>
<input type="text" class="form-control" placeholder="Enter slug" name="slug">
</div>
</div>
</div>


<div class="form-row">
<div class="col-lg-6 col-md-6 col-sm-6">
<div class="justify-content-center text-center">
<img src="../panel-assets/img/img/placeholder.png" alt="" id="cover_photo_parent_preview" class="rounded-0" width="200">
</div>
<div class="form-group">
<label for="cover_local" class="btn btn-primary text-white d-block rounded-pill" style="cursor: pointer">
<i class="fas fa-image"></i> Chose Cover
</label>
<input type="file" name="cover_local" id="cover_local" hidden accept="image/*">
<input type="hidden" name="store_logo" id="cover" class="form-control" required>
</div>
</div>

<div class="col-lg-6 col-md-6 col-sm-6">
<div class="form-group">
<label>Product Have A Expiry? (<font color="blue">If Not Have Any Expiry Leave It</font>)</label>
<div class="row">
<div class="col-lg-6 col-md-6 col-sm-6">
<input type="number" class="form-control" name="duration" id="duration" placeholder="1">
</div>
<div class="col-lg-6 col-md-6 col-sm-6">
<select name="duration_in" class="form-control">
<option value="">No Expiry</option>
<option value="days">Days</option>
<option value="months">Months</option>
<option value="years">Years</option>
</select>
</div>
</div>
</div>
</div>

</div>

<div class="form-row">
<div class="col-lg-4 col-md-4 col-sm-4">
<div class="form-group">
<label>Product Short Description</label>
<textarea name="description" class="form-control" style="height: 324px;" id="description" placeholder="Enter Product Short Description"></textarea>
</div>
</div>

<div class="col-lg-8 col-md-8 col-sm-8">
<div class="form-group">
<label>Product Long Description</label>
<textarea name="ldescription" id="ldescription" class="summernote" placeholder="Enter Product Long Description"></textarea>
</div>
</div>
</div>

<div class="form-row">
<div class="col-md-6">
<div class="form-group">
<label for="currency_id">Select Type</label>
<select name="product_type" id="product_type" class="form-control" required>
<option value="" selected>-Select-</option>
<option value="1">File (Downlaodable File)</option>
<option value="2">Text/ License</option>
</select>
</div>
</div>
<div class="transaction_fixed col-lg-6 col-md-6">
<div class="form-group">
<label>Require Some Data?</label>
<select name="is_require_data" id="is_require_data" class="form-control">
<option value="0" selected>No</option>
<option value="1">Yes</option>

</select>
</div>
</div>

<div class="transaction_fixed col-lg-6 col-md-6 fileselect hide">
<div class="form-group">
<label>Select File</label>
<input type="file" class="form-control" name="product" id="product">
</div>
</div>

<div class="transaction_fixed col-lg-6 col-md-6 text textadd hide">
<div class="form-group">
<label>Text Data (<font color="red">If a license key and require some data before grant then leave empty and select require data yes.</font>)</label>
<textarea class="form-control" id="text-data" name="text-data"></textarea>
</div>
</div>



<div class="form-group field_wrapper requiredata hide">
<div class="row">
<div class="col-md-5">
<label for="">Label</label> <br>
</div>
<div class="col-md-6">
<label for="">Input Type</label><br>
</div>
<div class="col-md-1">
<a href="javascript:" class="add_button text-xxs mr-2 btn btn-primary mb-0 btn-sm  text-xxs ">
<i class="fas fa-plus-circle"></i>
</a>
</div>
</div>
 <div class="row">
<div class="col-md-5"><br>
<input type="text" data-key="0" class="form-control" name="inputs[0][label]" placeholder="Label here">
</div>
<div class="col-md-6"><br>
<select class="form-control" name="inputs[0][type]" id="">
<option value="text">Text</option>
<option value="number">Number</option>
<option value="email">Email</option>
</select>
</div>
<div class="col-md-1">
<a href="javascript:void(0);" class="remove_button text-xxs mr-2 btn btn-danger mb-0 btn-sm  text-xxs mt-4" title="Remove">
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
</svg>
</a>
</div>
</div>
</div>
</div>
</div>
<!-- End Basic Info Tab -->

<!-- Automation Tab -->
<div class="tab-pane fade" id="automation" role="tabpanel">
<div class="automation-section">
    <h5><i class="fas fa-magic"></i> Activation Settings</h5>

    <div class="form-row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Activation Type</label>
                <select name="activation_type" id="activation_type" class="form-control">
                    <option value="manual">Manual (Admin Approval Required)</option>
                    <option value="instant">Instant (Immediate Access)</option>
                    <option value="api">API (External API Activation)</option>
                    <option value="none">None (No Activation Needed)</option>
                </select>
                <small class="form-text">How should this product be activated after payment?</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Is Free Product?</label>
                <select name="is_free" id="is_free" class="form-control">
                    <option value="0">No</option>
                    <option value="1">Yes (No Payment Required)</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Auto-Approve on Payment?</label>
                <select name="auto_approve" id="auto_approve" class="form-control">
                    <option value="0">No (Wait for Admin)</option>
                    <option value="1">Yes (Instant Approval)</option>
                </select>
            </div>
        </div>
    </div>

    <div id="api-config" class="api-config-section" style="display: none;">
        <hr>
        <h6><i class="fas fa-plug"></i> API Configuration</h6>

        <div class="form-row">
            <div class="col-md-8">
                <div class="form-group">
                    <label>Activation API URL</label>
                    <input type="text" class="form-control" name="activation_api_url" placeholder="https://api.example.com/activate">
                    <small class="form-text">The endpoint to call for product activation</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>API Method</label>
                    <select name="activation_api_method" class="form-control">
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>API Key/Secret</label>
                    <input type="text" class="form-control" name="activation_api_key" placeholder="your-api-key-here">
                    <small class="form-text">Authentication key for the API (will be used as {api_key} placeholder)</small>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>API Parameters (JSON)</label>
                    <textarea class="form-control" name="activation_api_params" rows="5" placeholder='{"key": "{api_key}", "email": "{activation_email}"}'></textarea>
                    <div class="json-help">
                        <strong>Available placeholders:</strong><br>
                        <code>{api_key}</code> - API key above<br>
                        <code>{activation_email}</code> - User's activation email<br>
                        <code>{user_email}</code> - User's account email<br>
                        <code>{user_id}</code> - User ID<br>
                        <code>{field_name}</code> - Any required data field (e.g., {license_domain})
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Response Mapping (JSON)</label>
                    <textarea class="form-control" name="activation_response_mapping" rows="5" placeholder='{"username": "data.username", "password": "data.password"}'></textarea>
                    <div class="json-help">
                        <strong>Map API response to variables:</strong><br>
                        <code>"varName": "path.to.value"</code><br>
                        <code>"license_key": "_raw_response"</code> for raw text response<br>
                        Example: <code>{"username": "data.username"}</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Instructions Template</label>
                    <textarea class="form-control" name="activation_instructions_template" rows="8" placeholder="Here is your account details:

URL: https://example.com
Username: {username}
Password: {password}

Activation Date: {activation_date}"></textarea>
                    <div class="json-help">
                        Use <code>{variable_name}</code> placeholders from response mapping above.<br>
                        Available: <code>{activation_date}</code>, <code>{activation_email}</code>, <code>{user_email}</code>, and any mapped response variables.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- End Automation Tab -->

<!-- Email Validation Tab -->
<div class="tab-pane fade" id="email-validation" role="tabpanel">
<div class="automation-section">
    <h5><i class="fas fa-envelope-open-text"></i> Email Validation Settings</h5>
    <p class="text-muted">Configure email validation if this product requires checking email availability before activation (e.g., for panel account registration).</p>

    <div class="form-row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Requires Email Validation?</label>
                <select name="requires_email_validation" id="requires_email_validation" class="form-control">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
        </div>
    </div>

    <div id="email-validation-config" style="display: none;">
        <hr>
        <div class="form-row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Email Validation API URL</label>
                    <input type="text" class="form-control" name="email_validation_api_url" placeholder="https://api.example.com/check-email">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Email Validation API Parameters (JSON)</label>
                    <textarea class="form-control" name="email_validation_api_params" rows="3" placeholder='{"action": "check_email", "key": "{api_key}", "email": "{email}"}'></textarea>
                    <small class="form-text">Use <code>{email}</code> for the email to validate, <code>{api_key}</code> for the API key from Automation tab</small>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Success Response Key</label>
                    <input type="text" class="form-control" name="email_validation_success_key" placeholder="isfree">
                    <small class="form-text">The key in API response to check (e.g., "isfree", "status", "available")</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Success Response Value</label>
                    <input type="text" class="form-control" name="email_validation_success_value" placeholder="1">
                    <small class="form-text">The value that indicates success (e.g., "1", "success", "true")</small>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- End Email Validation Tab -->

<!-- Renewal Tab -->
<div class="tab-pane fade" id="renewal" role="tabpanel">
<div class="automation-section">
    <h5><i class="fas fa-sync-alt"></i> Renewal / Extension Settings</h5>
    <p class="text-muted">Configure renewal options for subscription-based products.</p>

    <div class="form-row">
        <div class="col-md-4">
            <div class="form-group">
                <label>Is Product Renewable?</label>
                <select name="is_renewable" id="is_renewable" class="form-control">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
        </div>
    </div>

    <div id="renewal-config" style="display: none;">
        <hr>
        <div class="form-row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Renewal API URL</label>
                    <input type="text" class="form-control" name="renewal_api_url" placeholder="https://api.example.com/extend">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Renewal API Parameters (JSON)</label>
                    <textarea class="form-control" name="renewal_api_params" rows="4" placeholder='{
    "action": "extend",
    "key": "{api_key}",
    "email": "{activation_email}",
    "plan": "{plan_id}",
    "amount": "{plan_price}"
}'></textarea>
                    <div class="json-help">
                        <strong>Available placeholders:</strong><br>
                        <code>{api_key}</code>, <code>{activation_email}</code>, <code>{user_email}</code>, <code>{plan_id}</code>, <code>{plan_price}</code>, <code>{plan_name}</code>, <code>{plan_duration}</code>, <code>{payment_type}</code>, <code>{payment_info}</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Renewal Plans (JSON Array)</label>
                    <textarea class="form-control" name="renewal_plans" rows="6" placeholder='[
    {"id": 1, "name": "1 Month", "price": 40, "duration_days": 30},
    {"id": 2, "name": "3 Months", "price": 100, "duration_days": 90},
    {"id": 3, "name": "6 Months", "price": 180, "duration_days": 180}
]'></textarea>
                    <div class="json-help">
                        Define renewal plans with: <code>id</code> (unique), <code>name</code> (display name), <code>price</code> (cost in credits), <code>duration_days</code> (extension period)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- End Renewal Tab -->

</div>
<!-- End Tab Content -->

<div class="btn-publish">
<button type="submit" class="btn btn-primary col-12 basicbtn"><i class="fa fa-save"></i>
Save
</button>
</div>

</div>
</div>

</div>
</form>
</div>
<div class="loading"></div>

<div class="modal fade" tabindex="-1" role="dialog" id="photo_cropper_modal" data-backdrop="false">
<div class="modal-dialog modal-lg" role="document">
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
<?php
include "footer.php";
?>
<script>
function genSlug(productName) {
    var slug = productName.toLowerCase();
    slug = slug.replace(/[\'"!@#$%^&*()+=]/g, '');
    slug = slug.replace(/\s+/g, '-');
    slug = slug.replace(/-+/g, '-');
    slug = slug.replace(/^-+|-+$/g, '');
    return slug;
}
$('input[name="product_name"]').on('input', function() {
    var productName = $(this).val();
    var slug = genSlug(productName);
    $('input[name="slug"]').val(slug);
});

// Toggle API config section based on activation type
$('#activation_type').on('change', function() {
    if ($(this).val() === 'api') {
        $('#api-config').slideDown();
    } else {
        $('#api-config').slideUp();
    }
});

// Toggle email validation config
$('#requires_email_validation').on('change', function() {
    if ($(this).val() === '1') {
        $('#email-validation-config').slideDown();
    } else {
        $('#email-validation-config').slideUp();
    }
});

// Toggle renewal config
$('#is_renewable').on('change', function() {
    if ($(this).val() === '1') {
        $('#renewal-config').slideDown();
    } else {
        $('#renewal-config').slideUp();
    }
});

// Auto-set auto_approve when activation_type is 'api' or 'instant'
$('#activation_type').on('change', function() {
    var val = $(this).val();
    if (val === 'api' || val === 'instant') {
        $('#auto_approve').val('1');
    }
});
</script>