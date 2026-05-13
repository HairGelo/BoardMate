// Modal helpers
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Close modal on backdrop click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});

// Populate edit modal fields from data attributes
function editBuilding(btn) {
    const d = btn.dataset;
    const m = document.getElementById('editBuildingModal');
    m.querySelector('[name=id]').value       = d.id;
    m.querySelector('[name=name]').value     = d.name;
    m.querySelector('[name=address]').value  = d.address;
    m.querySelector('[name=num_floors]').value = d.floors;
    openModal('editBuildingModal');
}

function editRoom(btn) {
    const d = btn.dataset;
    const m = document.getElementById('editRoomModal');
    m.querySelector('[name=id]').value           = d.id;
    m.querySelector('[name=room_type]').value     = d.type;
    m.querySelector('[name=bathroom_type]').value = d.bath;
    m.querySelector('[name=monthly_rate]').value  = d.rate;
    m.querySelector('[name=max_occupants]').value = d.max;
    m.querySelector('[name=status]').value        = d.status;
    m.querySelector('[name=notes]').value         = d.notes;
    m.querySelector('.edit-room-label').textContent = 'Room ' + d.num;
    openModal('editRoomModal');
}

function editTenant(btn) {
    const d = btn.dataset;
    const m = document.getElementById('editTenantModal');
    m.querySelector('[name=id]').value                        = d.id;
    m.querySelector('[name=first_name]').value                = d.fn;
    m.querySelector('[name=last_name]').value                 = d.ln;
    m.querySelector('[name=email]').value                     = d.email;
    m.querySelector('[name=phone]').value                     = d.phone;
    m.querySelector('[name=emergency_contact_name]').value    = d.ecn;
    m.querySelector('[name=emergency_contact_phone]').value   = d.ecp;
    openModal('editTenantModal');
}

function endLease(btn) {
    const m = document.getElementById('endLeaseModal');
    m.querySelector('[name=lease_id]').value = btn.dataset.lid;
    m.querySelector('[name=room_id]').value  = btn.dataset.rid;
    openModal('endLeaseModal');
}

function editPayment(btn) {
    const d = btn.dataset;
    const m = document.getElementById('editPaymentModal');
    m.querySelector('[name=id]').value           = d.id;
    m.querySelector('[name=amount_due]').value   = d.due;
    m.querySelector('[name=amount_paid]').value  = d.paid;
    m.querySelector('[name=notes]').value        = d.notes;
    openModal('editPaymentModal');
}

// Confirm delete
function confirmDelete(url, label) {
    if (confirm('Delete ' + label + '? This cannot be undone.')) {
        window.location.href = url;
    }
}

// Auto-set monthly rate when assigning room
const roomSelect = document.getElementById('room_select');
const rateInput  = document.getElementById('rate_display');
if (roomSelect && rateInput) {
    roomSelect.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        rateInput.value = opt.dataset.rate || '';
    });
}
