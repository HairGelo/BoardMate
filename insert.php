<?php
require 'dbconfig.php';

$action = $_POST['action'] ?? '';

if ($action === 'add_building') {
    $name    = $conn->real_escape_string($_POST['name']);
    $address = $conn->real_escape_string($_POST['address']);
    $floors  = (int)$_POST['num_floors'];
    $stmt = $conn->prepare("INSERT INTO buildings (name, address, num_floors) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $name, $address, $floors);
    $stmt->execute();
    header("Location: index.php?page=buildings&msg=Building+added");

} elseif ($action === 'add_room') {
    $bid      = (int)$_POST['building_id'];
    $floor    = (int)$_POST['floor_number'];
    $num      = $conn->real_escape_string($_POST['room_number']);
    $type     = $conn->real_escape_string($_POST['room_type']);
    $bath     = $conn->real_escape_string($_POST['bathroom_type']);
    $rate     = (float)$_POST['monthly_rate'];
    $max_occ  = (int)$_POST['max_occupants'];
    $notes    = $conn->real_escape_string($_POST['notes']);
    $stmt = $conn->prepare("INSERT INTO rooms (building_id, floor_number, room_number, room_type, bathroom_type, monthly_rate, max_occupants, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iiisssds", $bid, $floor, $num, $type, $bath, $rate, $max_occ, $notes);
    $stmt->execute();
    header("Location: index.php?page=rooms&msg=Room+added");

} elseif ($action === 'add_tenant') {
    $fn   = $conn->real_escape_string($_POST['first_name']);
    $ln   = $conn->real_escape_string($_POST['last_name']);
    $em   = $conn->real_escape_string($_POST['email']);
    $ph   = $conn->real_escape_string($_POST['phone']);
    $ecn  = $conn->real_escape_string($_POST['emergency_contact_name']);
    $ecp  = $conn->real_escape_string($_POST['emergency_contact_phone']);
    $idt  = $conn->real_escape_string($_POST['id_type']);
    $idn  = $conn->real_escape_string($_POST['id_number']);
    $stmt = $conn->prepare("INSERT INTO tenants (first_name, last_name, email, phone, emergency_contact_name, emergency_contact_phone, id_type, id_number) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssss", $fn, $ln, $em, $ph, $ecn, $ecp, $idt, $idn);
    $stmt->execute();
    header("Location: index.php?page=tenants&msg=Tenant+added");

} elseif ($action === 'assign_room') {
    $tid     = (int)$_POST['tenant_id'];
    $rid     = (int)$_POST['room_id'];
    $movein  = $conn->real_escape_string($_POST['move_in_date']);
    $deposit = (float)$_POST['deposit_amount'];
    $stmt = $conn->prepare("INSERT INTO leases (tenant_id, room_id, move_in_date, deposit_amount) VALUES (?,?,?,?)");
    $stmt->bind_param("iisd", $tid, $rid, $movein, $deposit);
    $stmt->execute();
    $conn->query("UPDATE rooms SET status='occupied' WHERE id=$rid");
    header("Location: index.php?page=leases&msg=Lease+created");

} elseif ($action === 'log_payment') {
    $lid    = (int)$_POST['lease_id'];
    $month  = $conn->real_escape_string($_POST['billing_month']);
    $due    = (float)$_POST['amount_due'];
    $paid   = (float)$_POST['amount_paid'];
    $notes  = $conn->real_escape_string($_POST['notes']);
    $status = ($paid >= $due) ? 'paid' : (($paid > 0) ? 'partial' : 'unpaid');
    $paid_at = ($paid > 0) ? 'NOW()' : 'NULL';
    $stmt = $conn->prepare("INSERT INTO payments (lease_id, billing_month, amount_due, amount_paid, paid_at, status, notes) VALUES (?,?,?,?,IF(?>'0','NOW()',NULL),?,?)");
    $conn->query("INSERT INTO payments (lease_id, billing_month, amount_due, amount_paid, paid_at, status, notes) VALUES ($lid, '$month', $due, $paid, " . ($paid > 0 ? "NOW()" : "NULL") . ", '$status', '$notes')");
    header("Location: index.php?page=payments&msg=Payment+logged");

} else {
    header("Location: index.php");
}
?>
