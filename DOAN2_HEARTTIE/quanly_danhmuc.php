<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header {
            background: linear-gradient(135deg, rgb(58, 83, 124) 0%, rgb(45, 65, 95) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .header h1 {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        .content {
            padding: 30px;
        }

        .button-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px;
            padding: 0 5px;
        }

        .add-btn {
            background: linear-gradient(135deg, rgb(58, 83, 124) 0%, rgb(70, 95, 136) 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .add-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(58, 83, 124, 0.4);
        }

        .add-btn::before {
            content: '+';
            font-size: 1.2em;
            font-weight: bold;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            width: 600px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            animation: modalSlideIn 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .modal-title {
            font-size: 1.8rem;
            color: rgb(58, 83, 124);
            font-weight: 700;
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-btn:hover {
            color: #e74c3c;
            background-color: #f8f9fa;
            transform: scale(1.1);
        }

        .form-section {
            display: none;
        }

        .form-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid rgb(58, 83, 124);
            display: inline-block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: rgb(58, 83, 124);
            background: white;
            box-shadow: 0 0 0 3px rgba(58, 83, 124, 0.15);
            transform: translateY(-2px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, rgb(58, 83, 124) 0%, rgb(70, 95, 136) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(58, 83, 124, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8B4513;
        }

        .btn-warning:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(252, 182, 159, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #DC143C;
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 154, 158, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #666;
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(168, 237, 234, 0.4);
        }

        .table-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .table-header {
            background: linear-gradient(135deg, rgb(58, 83, 124) 0%, rgb(45, 65, 95) 100%);
            color: white;
            padding: 20px 25px;
        }

        .table-header h3 {
            font-size: 1.5rem;
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: linear-gradient(135deg, rgba(58, 83, 124, 0.08) 0%, rgba(58, 83, 124, 0.15) 100%);
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(58, 83, 124, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
            min-width: auto;
        }

        .empty-message {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
            font-size: 1.1rem;
        }

        .search-box {
            position: relative;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            font-size: 1rem;
            background: #fafbfc;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: rgb(58, 83, 124);
            background: white;
            box-shadow: 0 0 0 3px rgba(58, 83, 124, 0.15);
        }

        .search-box::after {
            content: '🔍';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            th, td {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }

        .notification {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 10px;
            font-weight: 500;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid rgb(58, 83, 124);
        }

        .notification.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .back-btn {
    position: fixed;
    bottom: 24px;
    left: 24px;
    text-decoration: none;
    background-color: rgb(58, 83, 124);
    color: white;
    font-weight: bold;
    padding: 10px 18px;
    border-radius: 20px;
    z-index: 999;
    transition: background-color 0.3s ease;
}
.back-btn:hover {
    background-color: rgb(40, 60, 100);
    color: white;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>QUẢN LÝ DANH MỤC</h1>
            <a href="quantrihethong.php" class="back-btn">&#8592; Dashboard</a>
        </div>

        <div class="content">
            <div id="notification"></div>

            <div class="button-bar">
                <button class="add-btn" id="openAddModal">Thêm Danh mục</button>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Tìm kiếm theo mã hoặc tên danh mục...">
                </div>
            </div>

            <!-- Modal thêm/sửa danh mục -->
            <div id="categoryModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title" id="modalTitle">Thêm Danh mục Mới</h2>
                        <button class="close-btn" id="closeModal">&times;</button>
                    </div>
                    
                    <form id="categoryForm">
                        <input type="hidden" id="editIndex" value="">
                        
                        <div class="form-group">
                            <label for="categoryCode">Mã Danh mục *</label>
                            <input type="text" id="categoryCode" name="categoryCode" placeholder="Nhập mã danh mục (VD: DM001)" required>
                        </div>

                        <div class="form-group">
                            <label for="categoryName">Tên Danh mục *</label>
                            <input type="text" id="categoryName" name="categoryName" placeholder="Nhập tên danh mục" required>
                        </div>

                        <div class="form-group">
                            <label for="categoryDescription">Mô tả</label>
                            <textarea id="categoryDescription" name="categoryDescription" placeholder="Nhập mô tả cho danh mục (không bắt buộc)"></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                Thêm Danh mục
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelBtn">
                                Hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-section">
                <div class="table-header">
                    <h3>Danh sách Danh mục</h3>
                </div>
                <div style="padding: 25px;">
                    <div class="table-container">
                        <table id="categoryTable">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã Danh mục</th>
                                    <th>Tên Danh mục</th>
                                    <th>Mô tả</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="categoryTableBody">
                                <tr>
                                    <td colspan="5" class="empty-message">
                                        Chưa có danh mục nào. Hãy thêm danh mục đầu tiên!
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        class CategoryManager {
            constructor() {
                this.categories = this.loadCategories();
                this.filteredCategories = [...this.categories];
                this.isEditing = false;
                this.editIndex = -1;
                
                this.initializeEventListeners();
                this.renderTable();
            }

            initializeEventListeners() {
                document.getElementById('categoryForm').addEventListener('submit', (e) => this.handleSubmit(e));
                document.getElementById('openAddModal').addEventListener('click', () => this.openAddModal());
                document.getElementById('closeModal').addEventListener('click', () => this.closeModal());
                document.getElementById('cancelBtn').addEventListener('click', () => this.closeModal());
                document.getElementById('searchInput').addEventListener('input', (e) => this.handleSearch(e));
                
                // Đóng modal khi click outside
                document.getElementById('categoryModal').addEventListener('click', (e) => {
                    if (e.target.id === 'categoryModal') {
                        this.closeModal();
                    }
                });
                
                // Đóng modal bằng phím Escape
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.closeModal();
                    }
                });
            }

            openAddModal() {
                this.isEditing = false;
                this.editIndex = -1;
                
                document.getElementById('modalTitle').textContent = 'Thêm Danh mục Mới';
                document.getElementById('submitBtn').textContent = 'Thêm Danh mục';
                document.getElementById('editIndex').value = '';
                
                this.clearForm();
                document.getElementById('categoryModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Focus vào ô đầu tiên
                setTimeout(() => {
                    document.getElementById('categoryCode').focus();
                }, 100);
            }

            openEditModal(index) {
                const category = this.filteredCategories[index];
                const originalIndex = this.categories.findIndex(cat => 
                    cat.MA_DM === category.MA_DM && cat.TEN_DM === category.TEN_DM
                );
                
                this.isEditing = true;
                this.editIndex = originalIndex;
                
                document.getElementById('modalTitle').textContent = 'Sửa Danh mục';
                document.getElementById('submitBtn').textContent = 'Cập nhật';
                document.getElementById('editIndex').value = originalIndex;
                
                document.getElementById('categoryCode').value = category.MA_DM;
                document.getElementById('categoryName').value = category.TEN_DM;
                document.getElementById('categoryDescription').value = category.MO_TA;
                
                document.getElementById('categoryModal').style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Focus vào ô tên
                setTimeout(() => {
                    document.getElementById('categoryName').focus();
                }, 100);
            }

            closeModal() {
                document.getElementById('categoryModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                this.clearForm();
                this.isEditing = false;
                this.editIndex = -1;
            }

            loadCategories() {
                const saved = localStorage.getItem('categories');
                if (saved) {
                    return JSON.parse(saved);
                }
                // Dữ liệu mẫu
                return [
                    { MA_DM: 'DM001', TEN_DM: 'Dây chuyền', MO_TA: 'Dây chuyền bạc nữ cao cấp, đính đá CZ và kim cương' },
                    { MA_DM: 'DM002', TEN_DM: 'Lắc', MO_TA: 'Lắc tay, lắc chân bạc nữ sang trọng' },
                    { MA_DM: 'DM003', TEN_DM: 'Nhẫn', MO_TA: 'Nhẫn bạc nữ đính đá, nhẫn đôi cặp' },
                    { MA_DM: 'DM004', TEN_DM: 'Bông tai', MO_TA: 'Bông tai bạc nữ thời trang, đính đá CZ' }
                ];
            }

            saveCategories() {
                localStorage.setItem('categories', JSON.stringify(this.categories));
            }

            showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                notification.innerHTML = `<div class="notification ${type}">${message}</div>`;
                
                setTimeout(() => {
                    notification.innerHTML = '';
                }, 5000);
            }

            validateForm(categoryData) {
                if (!categoryData.MA_DM.trim()) {
                    throw new Error('Mã danh mục không được để trống');
                }
                
                if (!categoryData.TEN_DM.trim()) {
                    throw new Error('Tên danh mục không được để trống');
                }

                // Kiểm tra mã danh mục trùng lặp
                const existingIndex = this.categories.findIndex(cat => 
                    cat.MA_DM.toLowerCase() === categoryData.MA_DM.toLowerCase()
                );
                
                if (existingIndex !== -1 && (!this.isEditing || existingIndex !== this.editIndex)) {
                    throw new Error('Mã danh mục đã tồn tại');
                }

                return true;
            }

            handleSubmit(e) {
                e.preventDefault();
                
                try {
                    const formData = new FormData(e.target);
                    const categoryData = {
                        MA_DM: formData.get('categoryCode').trim(),
                        TEN_DM: formData.get('categoryName').trim(),
                        MO_TA: formData.get('categoryDescription').trim()
                    };

                    this.validateForm(categoryData);

                    if (this.isEditing) {
                        this.categories[this.editIndex] = categoryData;
                        this.showNotification('Danh mục đã được cập nhật thành công!');
                    } else {
                        this.categories.push(categoryData);
                        this.showNotification('Danh mục đã được thêm thành công!');
                    }

                    this.saveCategories();
                    this.renderTable();
                    this.closeModal();
                    
                } catch (error) {
                    this.showNotification(error.message, 'error');
                }
            }

            editCategory(index) {
                this.openEditModal(index);
            }

            deleteCategory(index) {
                if (confirm('Bạn có chắc chắn muốn xóa danh mục này không?')) {
                    const category = this.filteredCategories[index];
                    const originalIndex = this.categories.findIndex(cat => 
                        cat.MA_DM === category.MA_DM && cat.TEN_DM === category.TEN_DM
                    );
                    
                    this.categories.splice(originalIndex, 1);
                    this.saveCategories();
                    this.renderTable();
                    this.showNotification('Danh mục đã được xóa thành công!');
                }
            }

            cancelEdit() {
                this.closeModal();
            }

            clearForm() {
                document.getElementById('categoryForm').reset();
            }

            handleSearch(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                if (searchTerm === '') {
                    this.filteredCategories = [...this.categories];
                } else {
                    this.filteredCategories = this.categories.filter(category => 
                        category.MA_DM.toLowerCase().includes(searchTerm) ||
                        category.TEN_DM.toLowerCase().includes(searchTerm)
                    );
                }
                
                this.renderTable();
            }

            renderTable() {
                const tbody = document.getElementById('categoryTableBody');
                
                if (this.filteredCategories.length === 0) {
                    const searchTerm = document.getElementById('searchInput').value.trim();
                    const message = searchTerm ? 
                        'Không tìm thấy danh mục nào phù hợp với từ khóa tìm kiếm.' :
                        'Chưa có danh mục nào. Hãy thêm danh mục đầu tiên!';
                    
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="empty-message">${message}</td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = this.filteredCategories.map((category, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${category.MA_DM}</strong></td>
                        <td>${category.TEN_DM}</td>
                        <td>${category.MO_TA || '<em>Không có mô tả</em>'}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="categoryManager.editCategory(${index})">
                                    Sửa
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="categoryManager.deleteCategory(${index})">
                                    Xóa
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        }

        // Khởi tạo ứng dụng
        const categoryManager = new CategoryManager();

        // Focus vào ô đầu tiên khi trang được tải
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('categoryCode').focus();
        });
    </script>
</body>
</html>