<?php
require 'dbconfig.php';

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($action === 'delete_building' && $id) {
    $conn->query("DELETE FROM buildings WHERE id=$id");
    header("Location: index.php?page=buildings&msg=Building+deleted");

} elseif ($action === 'delete_room' && $id) {
    $conn->query("DELETE FROM rooms WHERE id=$id");
    header("Location: index.php?page=rooms&msg=Room+deleted");

} elseif ($action === 'delete_tenant' && $id) {
    $conn->query("DELETE FROM tenants WHERE id=$id");
    header("Location: index.php?page=tenants&msg=Tenant+deleted");

} elseif ($action === 'delete_payment' && $id) {
    $conn->query("DELETE FROM payments WHERE id=$id");
    header("Location: index.php?page=payments&msg=Payment+deleted");

} else {
    header("Location: index.php");
}
?>
