<?php
require 'dbconfig.php';

$action = $_POST['action'] ?? '';

if ($action === 'update_building') {
    $id      = (int)$_POST['id'];
    $name    = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']);
    $floors  = (int)$_POST['num_floors'];
    $conn->query("UPDATE buildings SET name='$name', address='$address', num_floors=$floors WHERE id=$id");
    header("Location: index.php?page=buildings&msg=Building+updated");

} elseif ($action === 'update_room') {
    $id      = (int)$_POST['id'];
    $type    = $conn->real_escape_string($_POST['room_type']);
    $bath    = $conn->real_escape_string($_POST['bathroom_type']);
    $rate    = (float)$_POST['monthly_rate'];
    $max_occ = (int)$_POST['max_occupants'];
    $status  = $conn->real_escape_string($_POST['status']);
    $notes   = $conn->real_escape_string($_POST['notes']);
    $conn->query("UPDATE rooms SET room_type='$type', bathroom_type='$bath', monthly_rate=$rate, max_occupants=$max_occ, status='$status', notes='$notes' WHERE id=$id");
    header("Location: index.php?page=rooms&msg=Room+updated");

} elseif ($action === 'update_tenant') {
    $id  = (int)$_POST['id'];
    $fn  = $conn->real_escape_string($_POST['first_name']);
    $ln  = $conn->real_escape_string($_POST['last_name']);
    $em  = $conn->real_escape_string($_POST['email']);
    $ph  = $conn->real_escape_string($_POST['phone']);
    $ecn = $conn->real_escape_string($_POST['emergency_contact_name']);
    $ecp = $conn->real_escape_string($_POST['emergency_contact_phone']);
    $conn->query("UPDATE tenants SET first_name='$fn', last_name='$ln', email='$em', phone='$ph', emergency_contact_name='$ecn', emergency_contact_phone='$ecp' WHERE id=$id");
    header("Location: index.php?page=tenants&msg=Tenant+updated");

} elseif ($action === 'end_lease') {
    $lid = (int)$_POST['lease_id'];
    $rid = (int)$_POST['room_id'];
    $moveout = $conn->real_escape_string($_POST['move_out_date']);
    $conn->query("UPDATE leases SET status='ended', move_out_date='$moveout' WHERE id=$lid");
    $conn->query("UPDATE rooms SET status='vacant' WHERE id=$rid");
    header("Location: index.php?page=leases&msg=Lease+ended");

} elseif ($action === 'update_payment') {
    $id    = (int)$_POST['id'];
    $paid  = (float)$_POST['amount_paid'];
    $due   = (float)$_POST['amount_due'];
    $notes = $conn->real_escape_string($_POST['notes']);
    $status = ($paid >= $due) ? 'paid' : (($paid > 0) ? 'partial' : 'unpaid');
    $paid_at_sql = ($paid > 0) ? "paid_at=NOW()," : "";
    $conn->query("UPDATE payments SET amount_paid=$paid, {$paid_at_sql} status='$status', notes='$notes' WHERE id=$id");
    header("Location: index.php?page=payments&msg=Payment+updated");

} else {
    header("Location: index.php");
}
?>
