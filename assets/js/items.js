function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function viewItem(item) {
    alert(`Detail Barang:\n\nKode: ${item.item_code}\nNama: ${item.item_name}\nDeskripsi: ${item.description}\nStok: ${item.quantity} ${item.unit}\nLokasi: ${item.location}`);
}

function editItem(item) {
    document.getElementById('edit_id').value = item.id;
    document.getElementById('edit_item_name').value = item.item_name;
    document.getElementById('edit_description').value = item.description;
    document.getElementById('edit_category_id').value = item.category_id;
    document.getElementById('edit_quantity').value = item.quantity;
    document.getElementById('edit_min_stock').value = item.min_stock;
    document.getElementById('edit_unit').value = item.unit;
    document.getElementById('edit_price').value = item.price;
    document.getElementById('edit_location').value = item.location;
    
    openModal('editModal');
}

function deleteItem(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus barang "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let i = 0; i < modals.length; i++) {
        if (event.target == modals[i]) {
            modals[i].style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
}
