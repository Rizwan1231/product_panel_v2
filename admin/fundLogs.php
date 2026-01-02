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

$userid = intval($_GET['id']);
$db->query("SELECT * FROM `payment_logs` WHERE `user_id` = '%d'", $userid);
$histories = $db->getall();

$db->query("SELECT `id`, `email` FROM `users`");
$users = $db->getall();

include "header.php";
?>
<style>
        #userSelect {
            width: 298px !important;
        }
        .select2-container {
            width: 298px !important;
        }
</style>
<div class="main-content main-wrapper-1">
    <section class="section">
        <div class="section-header">
            <h1>Users Fund Logs</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item">dashboard</div>
                <div class="breadcrumb-item">fund logs</div>
            </div>
        </div>
    </section>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 style="margin-bottom: 0;">Fund Logs</h4>
            <div class="form-group mb-0">
                <select id="userSelect" class="form-control" onchange="redirectToUser(this.value)" style="width: 298px;">
                    <option value="">Choose a user</option>
					<?php foreach($users as $user) { ?>
					<option value="<?= $user['id']; ?>"><?= $user['email']; ?></option>
					<?php } ?>
                </select>
            </div>
            <div></div>
        </div>
        <div class="card-body">
            <div class="table-responsive product-table">
                <table class="table table-stripped datatables">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Detail</th>
                            <th>Charge</th>
                            <th>Balance</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($histories as $idd => $history) { ?>
                        <tr>
                            <td><?= $idd+1; ?></td>
                            <td><?= $history['detail']; ?></td>
                            <td><?= $history['charge']; ?>$</td>
                            <td><?= $history['balance']; ?>$</td>
                            <td><?= date("h:i A / d-m-Y", ($history['date'])); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
include "footer.php";
?>
<script>
        $(document).ready(function() {
            $('#userSelect').select2({
                width: '298px'
            }).on('select2:open', function() {
                $('.select2-dropdown').css('width', '298px');
            });
        });
		
function redirectToUser(userId) {
    if (userId) {
        window.location.href = window.location.pathname + '?id=' + userId;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentUserId = urlParams.get('id');
    if (currentUserId) {
        document.getElementById('userSelect').value = currentUserId;
    }
});
</script>