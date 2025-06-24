function editCategory(category) {
    const modal = `
        <div id="editModal" class="modal" style="display: block;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Kategori</h3>
                    <span class="close" onclick="closeModal('editModal')">&times;</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="${category.id}">
                    <div class="form-group">
                        <label>Nama Kategori</label>
                        <input type="text" name="category_name" value="${category.category_name}" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" rows="3">${category.description || ''}</textarea>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
    document.body.style.overflow = 'hidden';
}

function deleteCategory(id, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus kategori "${name}"?`)) {
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
