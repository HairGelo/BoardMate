<?php
require 'dbconfig.php';

$page = $_GET['page'] ?? 'dashboard';
$msg  = $_GET['msg'] ?? '';

// ── Fetch data helpers ──────────────────────────────────────────────
function fetchAll($conn, $sql) {
    $r = $conn->query($sql);
    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

$buildings = fetchAll($conn, "SELECT * FROM buildings ORDER BY name");

// ── Active nav helper ───────────────────────────────────────────────
function navClass($p, $current) {
    return $p === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BoardMate — Boarding House Management</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ── Sidebar ─────────────────────────────────────────────────── -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <h1>BOARD<br>MATE</h1>
        <span>Boarding House Manager</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Overview</div>
        <a href="index.php?page=dashboard" class="<?= navClass('dashboard',$page) ?>">
            &#9632; Dashboard
        </a>
        <div class="nav-section">Property</div>
        <a href="index.php?page=buildings" class="<?= navClass('buildings',$page) ?>">
            &#9632; Buildings
        </a>
        <a href="index.php?page=rooms" class="<?= navClass('rooms',$page) ?>">
            &#9632; Rooms
        </a>
        <a href="index.php?page=floor_map" class="<?= navClass('floor_map',$page) ?>">
            &#9632; Floor Map
        </a>
        <div class="nav-section">Tenants</div>
        <a href="index.php?page=tenants" class="<?= navClass('tenants',$page) ?>">
            &#9632; Tenants
        </a>
        <a href="index.php?page=leases" class="<?= navClass('leases',$page) ?>">
            &#9632; Leases
        </a>
        <div class="nav-section">Finance</div>
        <a href="index.php?page=payments" class="<?= navClass('payments',$page) ?>">
            &#9632; Payments
        </a>
    </nav>
</aside>

<!-- ── Main ────────────────────────────────────────────────────── -->
<main class="main">

<?php if ($msg): ?>
<div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     DASHBOARD
═══════════════════════════════════════════════════════ -->
<?php if ($page === 'dashboard'):
    $total_rooms    = fetchAll($conn, "SELECT COUNT(*) c FROM rooms")[0]['c'];
    $vacant_rooms   = fetchAll($conn, "SELECT COUNT(*) c FROM rooms WHERE status='vacant'")[0]['c'];
    $occupied_rooms = fetchAll($conn, "SELECT COUNT(*) c FROM rooms WHERE status='occupied'")[0]['c'];
    $total_tenants  = fetchAll($conn, "SELECT COUNT(*) c FROM tenants")[0]['c'];
    $active_leases  = fetchAll($conn, "SELECT COUNT(*) c FROM leases WHERE status='active'")[0]['c'];
    $monthly_rev    = fetchAll($conn, "SELECT IFNULL(SUM(r.monthly_rate),0) total FROM leases l JOIN rooms r ON l.room_id=r.id WHERE l.status='active'")[0]['total'];
    $unpaid_count   = fetchAll($conn, "SELECT COUNT(*) c FROM payments WHERE status IN ('unpaid','partial')")[0]['c'];
    $recent_leases  = fetchAll($conn, "SELECT l.*, t.first_name, t.last_name, r.room_number, b.name building FROM leases l JOIN tenants t ON l.tenant_id=t.id JOIN rooms r ON l.room_id=r.id JOIN buildings b ON r.building_id=b.id WHERE l.status='active' ORDER BY l.id DESC LIMIT 5");
?>
<div class="page-header">
    <div>
        <h2>Dashboard</h2>
        <p>Overview of your property</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card"><div class="label">Total Rooms</div><div class="value"><?= $total_rooms ?></div></div>
    <div class="stat-card"><div class="label">Vacant</div><div class="value success"><?= $vacant_rooms ?></div></div>
    <div class="stat-card"><div class="label">Occupied</div><div class="value purple"><?= $occupied_rooms ?></div></div>
    <div class="stat-card"><div class="label">Active Tenants</div><div class="value"><?= $total_tenants ?></div></div>
    <div class="stat-card"><div class="label">Monthly Revenue</div><div class="value purple" style="font-size:20px">&#8369;<?= number_format($monthly_rev,0) ?></div></div>
    <div class="stat-card"><div class="label">Unpaid Bills</div><div class="value warning"><?= $unpaid_count ?></div></div>
</div>

<div class="table-card">
    <table>
        <thead><tr><th>Tenant</th><th>Room</th><th>Building</th><th>Move-in</th></tr></thead>
        <tbody>
        <?php if (empty($recent_leases)): ?>
            <tr><td colspan="4" class="empty-state">No active leases yet.</td></tr>
        <?php else: foreach($recent_leases as $l): ?>
            <tr>
                <td><?= htmlspecialchars($l['first_name'].' '.$l['last_name']) ?></td>
                <td><?= htmlspecialchars($l['room_number']) ?></td>
                <td><?= htmlspecialchars($l['building']) ?></td>
                <td><?= $l['move_in_date'] ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- ═══════════════════════════════════════════════════════
     BUILDINGS
═══════════════════════════════════════════════════════ -->
<?php elseif ($page === 'buildings'): ?>
<div class="page-header">
    <div><h2>Buildings</h2><p>Manage your properties</p></div>
    <button class="btn btn-primary" onclick="openModal('addBuildingModal')">+ Add Building</button>
</div>

<div class="table-card">
    <table>
        <thead><tr><th>Name</th><th>Address</th><th>Floors</th><th>Rooms</th><th>Actions</th></tr></thead>
        <tbody>
        <?php
        $rows = fetchAll($conn, "SELECT b.*, COUNT(r.id) room_count FROM buildings b LEFT JOIN rooms r ON r.building_id=b.id GROUP BY b.id ORDER BY b.name");
        if (empty($rows)): ?>
            <tr><td colspan="5" class="empty-state">No buildings yet. Add one!</td></tr>
        <?php else: foreach($rows as $row): ?>
            <tr>
                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                <td class="text-muted"><?= htmlspecialchars($row['address']) ?></td>
                <td><?= $row['num_floors'] ?></td>
                <td><?= $row['room_count'] ?></td>
                <td><div class="action-group">
                    <button class="btn btn-ghost btn-sm" onclick="editBuilding(this)"
                        data-id="<?= $row['id'] ?>"
                        data-name="<?= htmlspecialchars($row['name'],ENT_QUOTES) ?>"
                        data-address="<?= htmlspecialchars($row['address'],ENT_QUOTES) ?>"
                        data-floors="<?= $row['num_floors'] ?>">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete('delete.php?action=delete_building&id=<?= $row['id'] ?>','<?= htmlspecialchars($row['name'],ENT_QUOTES) ?>')">Delete</button>
                </div></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Building Modal -->
<div class="modal-overlay" id="addBuildingModal">
<div class="modal">
    <h3>Add Building</h3>
    <form action="insert.php" method="POST">
        <input type="hidden" name="action" value="add_building">
        <div class="form-group"><label>Building Name</label><input name="name" required placeholder="e.g. Block A"></div>
        <div class="form-group"><label>Address</label><input name="address" placeholder="Street, City"></div>
        <div class="form-group"><label>Number of Floors</label><input type="number" name="num_floors" value="1" min="1" required></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('addBuildingModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div></div>

<!-- Edit Building Modal -->
<div class="modal-overlay" id="editBuildingModal">
<div class="modal">
    <h3>Edit Building</h3>
    <form action="update.php" method="POST">
        <input type="hidden" name="action" value="update_building">
        <input type="hidden" name="id">
        <div class="form-group"><label>Building Name</label><input name="name" required></div>
        <div class="form-group"><label>Address</label><input name="address"></div>
        <div class="form-group"><label>Number of Floors</label><input type="number" name="num_floors" min="1" required></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('editBuildingModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div></div>

<!-- ═══════════════════════════════════════════════════════
     ROOMS
═══════════════════════════════════════════════════════ -->
<?php elseif ($page === 'rooms'): ?>
<div class="page-header">
    <div><h2>Rooms</h2><p>All rooms across buildings</p></div>
    <button class="btn btn-primary" onclick="openModal('addRoomModal')">+ Add Room</button>
</div>

<div class="table-card">
    <table>
        <thead><tr><th>Room</th><th>Building / Floor</th><th>Type</th><th>Bathroom</th><th>Rate / mo</th><th>Max</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php
        $rooms = fetchAll($conn, "SELECT r.*, b.name building FROM rooms r JOIN buildings b ON r.building_id=b.id ORDER BY b.name, r.floor_number, r.room_number");
        if (empty($rooms)): ?>
            <tr><td colspan="8" class="empty-state">No rooms yet.</td></tr>
        <?php else: foreach($rooms as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['room_number']) ?></strong></td>
                <td><?= htmlspecialchars($r['building']) ?> &bull; Floor <?= $r['floor_number'] ?></td>
                <td><?= ucfirst($r['room_type']) ?></td>
                <td><span class="badge badge-<?= $r['bathroom_type'] ?>"><?= ucfirst($r['bathroom_type']) ?></span></td>
                <td>&#8369;<?= number_format($r['monthly_rate'],0) ?></td>
                <td><?= $r['max_occupants'] ?></td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td><div class="action-group">
                    <button class="btn btn-ghost btn-sm" onclick="editRoom(this)"
                        data-id="<?= $r['id'] ?>"
                        data-num="<?= htmlspecialchars($r['room_number'],ENT_QUOTES) ?>"
                        data-type="<?= $r['room_type'] ?>"
                        data-bath="<?= $r['bathroom_type'] ?>"
                        data-rate="<?= $r['monthly_rate'] ?>"
                        data-max="<?= $r['max_occupants'] ?>"
                        data-status="<?= $r['status'] ?>"
                        data-notes="<?= htmlspecialchars($r['notes'],ENT_QUOTES) ?>">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete('delete.php?action=delete_room&id=<?= $r['id'] ?>','room <?= htmlspecialchars($r['room_number'],ENT_QUOTES) ?>')">Del</button>
                </div></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Room Modal -->
<div class="modal-overlay" id="addRoomModal">
<div class="modal">
    <h3>Add Room</h3>
    <form action="insert.php" method="POST">
        <input type="hidden" name="action" value="add_room">
        <div class="form-row">
            <div class="form-group"><label>Building</label>
                <select name="building_id" required>
                    <option value="">Select building</option>
                    <?php foreach($buildings as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Floor Number</label><input type="number" name="floor_number" value="1" min="1" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Room Number / Label</label><input name="room_number" required placeholder="e.g. 101, 2A"></div>
            <div class="form-group"><label>Max Occupants</label><input type="number" name="max_occupants" value="1" min="1"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Room Type</label>
                <select name="room_type">
                    <option value="single">Single</option>
                    <option value="double">Double</option>
                    <option value="studio">Studio</option>
                    <option value="suite">Suite</option>
                </select>
            </div>
            <div class="form-group"><label>Bathroom</label>
                <select name="bathroom_type">
                    <option value="communal">Communal</option>
                    <option value="private">Private (own CR)</option>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Monthly Rate (PHP)</label><input type="number" name="monthly_rate" required placeholder="e.g. 3500" min="0" step="0.01"></div>
        <div class="form-group"><label>Notes</label><textarea name="notes" placeholder="Aircon, WiFi, balcony, etc."></textarea></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('addRoomModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Room</button>
        </div>
    </form>
</div></div>

<!-- Edit Room Modal -->
<div class="modal-overlay" id="editRoomModal">
<div class="modal">
    <h3>Edit Room <span class="edit-room-label text-muted"></span></h3>
    <form action="update.php" method="POST">
        <input type="hidden" name="action" value="update_room">
        <input type="hidden" name="id">
        <div class="form-row">
            <div class="form-group"><label>Room Type</label>
                <select name="room_type">
                    <option value="single">Single</option>
                    <option value="double">Double</option>
                    <option value="studio">Studio</option>
                    <option value="suite">Suite</option>
                </select>
            </div>
            <div class="form-group"><label>Bathroom</label>
                <select name="bathroom_type">
                    <option value="communal">Communal</option>
                    <option value="private">Private</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Monthly Rate</label><input type="number" name="monthly_rate" step="0.01"></div>
            <div class="form-group"><label>Max Occupants</label><input type="number" name="max_occupants" min="1"></div>
        </div>
        <div class="form-group"><label>Status</label>
            <select name="status">
                <option value="vacant">Vacant</option>
                <option value="occupied">Occupied</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('editRoomModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div></div>

<!-- ═══════════════════════════════════════════════════════
     FLOOR MAP
═══════════════════════════════════════════════════════ -->
<?php elseif ($page === 'floor_map'):
    $sel_building = (int)($_GET['bid'] ?? ($buildings[0]['id'] ?? 0));
    $floors_data  = fetchAll($conn, "SELECT DISTINCT floor_number FROM rooms WHERE building_id=$sel_building ORDER BY floor_number");
?>
<div class="page-header">
    <div><h2>Floor Map</h2><p>Visual room occupancy</p></div>
    <form method="GET" style="display:flex;gap:10px;align-items:center">
        <input type="hidden" name="page" value="floor_map">
        <select name="bid" onchange="this.form.submit()" class="btn btn-ghost" style="cursor:pointer">
            <?php foreach($buildings as $b): ?>
            <option value="<?= $b['id'] ?>" <?= $b['id']==$sel_building?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="floor-map">
    <?php if (empty($floors_data)): ?>
        <div class="empty-state">No rooms in this building yet.</div>
    <?php else: foreach(array_reverse($floors_data) as $fl):
        $rooms_in_floor = fetchAll($conn, "SELECT * FROM rooms WHERE building_id=$sel_building AND floor_number={$fl['floor_number']} ORDER BY room_number");
    ?>
    <div class="floor-row">
        <div class="floor-label">Floor <?= $fl['floor_number'] ?></div>
        <div class="room-grid">
        <?php foreach($rooms_in_floor as $r): ?>
            <div class="room-tile <?= $r['status'] ?>" title="<?= htmlspecialchars($r['notes']) ?>">
                <div class="room-num"><?= htmlspecialchars($r['room_number']) ?></div>
                <div class="room-rate">&#8369;<?= number_format($r['monthly_rate'],0) ?></div>
                <div class="room-bath"><?= $r['bathroom_type']==='private' ? '&#128701; Private' : '&#128697; Shared' ?></div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- Legend -->
<div style="display:flex;gap:16px;margin-top:24px;font-size:12px">
    <span><span class="badge badge-vacant">Vacant</span></span>
    <span><span class="badge badge-occupied">Occupied</span></span>
    <span><span class="badge badge-maintenance">Maintenance</span></span>
</div>

<!-- ═══════════════════════════════════════════════════════
     TENANTS
═══════════════════════════════════════════════════════ -->
<?php elseif ($page === 'tenants'): ?>
<div class="page-header">
    <div><h2>Tenants</h2><p>Registered boarders</p></div>
    <button class="btn btn-primary" onclick="openModal('addTenantModal')">+ Add Tenant</button>
</div>

<div class="table-card">
    <table>
        <thead><tr><th>Name</th><th>Contact</th><th>Email</th><th>ID</th><th>Emergency Contact</th><th>Actions</th></tr></thead>
        <tbody>
        <?php
        $tenants = fetchAll($conn, "SELECT * FROM tenants ORDER BY last_name, first_name");
        if (empty($tenants)): ?>
            <tr><td colspan="6" class="empty-state">No tenants registered yet.</td></tr>
        <?php else: foreach($tenants as $t): ?>
            <tr>
                <td><strong><?= htmlspecialchars($t['first_name'].' '.$t['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($t['phone']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($t['email']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($t['id_type'].' '.$t['id_number']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($t['emergency_contact_name']) ?> &bull; <?= htmlspecialchars($t['emergency_contact_phone']) ?></td>
                <td><div class="action-group">
                    <button class="btn btn-ghost btn-sm" onclick="editTenant(this)"
                        data-id="<?= $t['id'] ?>"
                        data-fn="<?= htmlspecialchars($t['first_name'],ENT_QUOTES) ?>"
                        data-ln="<?= htmlspecialchars($t['last_name'],ENT_QUOTES) ?>"
                        data-email="<?= htmlspecialchars($t['email'],ENT_QUOTES) ?>"
                        data-phone="<?= htmlspecialchars($t['phone'],ENT_QUOTES) ?>"
                        data-ecn="<?= htmlspecialchars($t['emergency_contact_name'],ENT_QUOTES) ?>"
                        data-ecp="<?= htmlspecialchars($t['emergency_contact_phone'],ENT_QUOTES) ?>">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete('delete.php?action=delete_tenant&id=<?= $t['id'] ?>','<?= htmlspecialchars($t['first_name'].' '.$t['last_name'],ENT_QUOTES) ?>')">Del</button>
                </div></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Tenant Modal -->
<div class="modal-overlay" id="addTenantModal">
<div class="modal">
    <h3>Add Tenant</h3>
    <form action="insert.php" method="POST">
        <input type="hidden" name="action" value="add_tenant">
        <div class="form-section-title">Personal Info</div>
        <div class="form-row">
            <div class="form-group"><label>First Name</label><input name="first_name" required></div>
            <div class="form-group"><label>Last Name</label><input name="last_name" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Phone</label><input name="phone" placeholder="09xxxxxxxxx"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        </div>
        <div class="form-section-title">ID</div>
        <div class="form-row">
            <div class="form-group"><label>ID Type</label>
                <select name="id_type">
                    <option>School ID</option><option>National ID</option><option>Driver's License</option><option>Passport</option><option>Barangay ID</option><option>Other</option>
                </select>
            </div>
            <div class="form-group"><label>ID Number</label><input name="id_number"></div>
        </div>
        <div class="form-section-title">Emergency Contact</div>
        <div class="form-row">
            <div class="form-group"><label>Name</label><input name="emergency_contact_name"></div>
            <div class="form-group"><label>Phone</label><input name="emergency_contact_phone"></div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('addTenantModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Tenant</button>
        </div>
    </form>
</div></div>

<!-- Edit Tenant Modal -->
<div class="modal-overlay" id="editTenantModal">
<div class="modal">
    <h3>Edit Tenant</h3>
    <form action="update.php" method="POST">
        <input type="hidden" name="action" value="update_tenant">
        <input type="hidden" name="id">
        <div class="form-row">
            <div class="form-group"><label>First Name</label><input name="first_name" required></div>
            <div class="form-group"><label>Last Name</label><input name="last_name" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Phone</label><input name="phone"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Emergency Contact</label><input name="emergency_contact_name"></div>
            <div class="form-group"><label>Emergency Phone</label><input name="emergency_contact_phone"></div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('editTenantModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div></div>

<!-- ═══════════════════════════════════════════════════════
     LEASES
═══════════════════════════════════════════════════════ -->
<?php elseif ($page === 'leases'): ?>
<div class="page-header">
    <div><h2>Leases</h2><p>Room assignments and tenancies</p></div>
    <button class="btn btn-primary" onclick="openModal('addLeaseModal')">+ Assign Room</button>
</div>

<div class="table-card">
    <table>
        <thead><tr><th>Tenant</th><th>Room</th><th>Building</th><th>Move-in</th><th>Move-out</th><th>Deposit</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php
        $leases = fetchAll($conn, "SELECT l.*, t.first_name, t.last_name, r.room_number, r.monthly_rate, b.name building FROM leases l JOIN tenants t ON l.tenant_id=t.id JOIN rooms r ON l.room_id=r.id JOIN buildings b ON r.building_id=b.id ORDER BY l.status, l.id DESC");
        if (empty($leases)): ?>
            <tr><td colspan="8" class="empty-state">No leases yet.</td></tr>
        <?php else: foreach($leases as $l): ?>
            <tr>
                <td><strong><?= htmlspecialchars($l['first_name'].' '.$l['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($l['room_number']) ?></td>
                <td><?= htmlspecialchars($l['building']) ?></td>
                <td><?= $l['move_in_date'] ?></td>
                <td class="text-muted"><?= $l['move_out_date'] ?: '—' ?></td>
                <td>&#8369;<?= number_format($l['deposit_amount'],0) ?></td>
                <td><span class="badge badge-<?= $l['status'] ?>"><?= ucfirst($l['status']) ?></span></td>
                <td><?php if ($l['status']==='active'): ?>
                    <button class="btn btn-ghost btn-sm" onclick="endLease(this)" data-lid="<?= $l['id'] ?>" data-rid="<?= $l['room_id'] ?>">End Lease</button>
                <?php endif; ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Assign Room Modal -->
<?php
$unassigned_tenants = fetchAll($conn, "SELECT * FROM tenants WHERE id NOT IN (SELECT tenant_id FROM leases WHERE status='active') ORDER BY last_name");
$vacant_rooms_list  = fetchAll($conn, "SELECT r.*, b.name building FROM rooms r JOIN buildings b ON r.building_id=b.id WHERE r.status='vacant' ORDER BY b.name, r.floor_number, r.room_number");
?>
<div class="modal-overlay" id="addLeaseModal">
<div class="modal">
    <h3>Assign Room to Tenant</h3>
    <form action="insert.php" method="POST">
        <input type="hidden" name="action" value="assign_room">
        <div class="form-group"><label>Tenant</label>
            <select name="tenant_id" required>
                <option value="">Select tenant</option>
                <?php foreach($unassigned_tenants as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'].' '.$t['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Room</label>
            <select name="room_id" id="room_select" required>
                <option value="">Select vacant room</option>
                <?php foreach($vacant_rooms_list as $r): ?>
                <option value="<?= $r['id'] ?>" data-rate="<?= $r['monthly_rate'] ?>">
                    <?= htmlspecialchars($r['building']) ?> — Room <?= htmlspecialchars($r['room_number']) ?> (Fl.<?= $r['floor_number'] ?>) &#8369;<?= number_format($r['monthly_rate'],0) ?>/mo</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Move-in Date</label><input type="date" name="move_in_date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label>Deposit (PHP)</label><input type="number" name="deposit_amount" id="rate_display" min="0" step="0.01" value="0"></div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('addLeaseModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Assign</button>
        </div>
    </form>
</div></div>

<!-- End Lease Modal -->
<div class="modal-overlay" id="endLeaseModal">
<div class="modal">
    <h3>End Lease</h3>
    <form action="update.php" method="POST">
        <input type="hidden" name="action" value="end_lease">
        <input type="hidden" name="lease_id">
        <input type="hidden" name="room_id">
        <div class="form-group"><label>Move-out Date</label><input type="date" name="move_out_date" required value="<?= date('Y-m-d') ?>"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('endLeaseModal')">Cancel</button>
            <button type="submit" class="btn btn-danger">End Lease</button>
        </div>
    </form>
</div></div>

<!-- ═══════════════════════════════════════════════════════
     PAYMENTS
═══════════════════════════════════════════════════════ -->
<?php elseif ($page === 'payments'): ?>
<div class="page-header">
    <div><h2>Payments</h2><p>Monthly billing tracker</p></div>
    <button class="btn btn-primary" onclick="openModal('addPaymentModal')">+ Log Payment</button>
</div>

<?php
$total_collected = fetchAll($conn,"SELECT IFNULL(SUM(amount_paid),0) t FROM payments WHERE status='paid'")[0]['t'];
$total_overdue   = fetchAll($conn,"SELECT COUNT(*) c FROM payments WHERE status='unpaid' AND billing_month < CURDATE()")[0]['c'];
?>
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <div class="stat-card"><div class="label">Total Collected</div><div class="value success" style="font-size:20px">&#8369;<?= number_format($total_collected,0) ?></div></div>
    <div class="stat-card"><div class="label">Unpaid / Partial</div><div class="value warning"><?= fetchAll($conn,"SELECT COUNT(*) c FROM payments WHERE status IN ('unpaid','partial')")[0]['c'] ?></div></div>
    <div class="stat-card"><div class="label">Overdue</div><div class="value" style="color:var(--danger)"><?= $total_overdue ?></div></div>
</div>

<div class="table-card">
    <table>
        <thead><tr><th>Tenant</th><th>Room</th><th>Month</th><th>Due</th><th>Paid</th><th>Status</th><th>Paid On</th><th>Actions</th></tr></thead>
        <tbody>
        <?php
        $payments = fetchAll($conn, "SELECT p.*, l.tenant_id, t.first_name, t.last_name, r.room_number FROM payments p JOIN leases l ON p.lease_id=l.id JOIN tenants t ON l.tenant_id=t.id JOIN rooms r ON l.room_id=r.id ORDER BY p.billing_month DESC, p.id DESC");
        if (empty($payments)): ?>
            <tr><td colspan="8" class="empty-state">No payments logged yet.</td></tr>
        <?php else: foreach($payments as $p): ?>
            <tr>
                <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($p['room_number']) ?></td>
                <td><?= date('M Y', strtotime($p['billing_month'])) ?></td>
                <td>&#8369;<?= number_format($p['amount_due'],0) ?></td>
                <td>&#8369;<?= number_format($p['amount_paid'],0) ?></td>
                <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                <td class="text-muted"><?= $p['paid_at'] ? date('M d', strtotime($p['paid_at'])) : '—' ?></td>
                <td><div class="action-group">
                    <button class="btn btn-ghost btn-sm" onclick="editPayment(this)"
                        data-id="<?= $p['id'] ?>"
                        data-due="<?= $p['amount_due'] ?>"
                        data-paid="<?= $p['amount_paid'] ?>"
                        data-notes="<?= htmlspecialchars($p['notes'],ENT_QUOTES) ?>">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete('delete.php?action=delete_payment&id=<?= $p['id'] ?>','this payment')">Del</button>
                </div></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Log Payment Modal -->
<?php $active_leases = fetchAll($conn,"SELECT l.id, t.first_name, t.last_name, r.room_number, r.monthly_rate FROM leases l JOIN tenants t ON l.tenant_id=t.id JOIN rooms r ON l.room_id=r.id WHERE l.status='active' ORDER BY t.last_name"); ?>
<div class="modal-overlay" id="addPaymentModal">
<div class="modal">
    <h3>Log Payment</h3>
    <form action="insert.php" method="POST">
        <input type="hidden" name="action" value="log_payment">
        <div class="form-group"><label>Tenant / Lease</label>
            <select name="lease_id" required>
                <option value="">Select active tenant</option>
                <?php foreach($active_leases as $l): ?>
                <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['first_name'].' '.$l['last_name']) ?> — Room <?= htmlspecialchars($l['room_number']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Billing Month</label><input type="month" name="billing_month" required value="<?= date('Y-m') ?>"></div>
            <div class="form-group"><label>Amount Due</label><input type="number" name="amount_due" step="0.01" min="0" required></div>
        </div>
        <div class="form-group"><label>Amount Paid</label><input type="number" name="amount_paid" step="0.01" min="0" value="0"></div>
        <div class="form-group"><label>Notes</label><input name="notes" placeholder="Cash / GCash ref, etc."></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('addPaymentModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div></div>

<!-- Edit Payment Modal -->
<div class="modal-overlay" id="editPaymentModal">
<div class="modal">
    <h3>Update Payment</h3>
    <form action="update.php" method="POST">
        <input type="hidden" name="action" value="update_payment">
        <input type="hidden" name="id">
        <div class="form-row">
            <div class="form-group"><label>Amount Due</label><input type="number" name="amount_due" step="0.01" min="0"></div>
            <div class="form-group"><label>Amount Paid</label><input type="number" name="amount_paid" step="0.01" min="0"></div>
        </div>
        <div class="form-group"><label>Notes</label><input name="notes"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-ghost" onclick="closeModal('editPaymentModal')">Cancel</button>
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div></div>

<?php endif; ?>

</main>

<script src="script.js"></script>
</body>
</html>
