<style>
    .sectors-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
    .sectors-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    
    .tabs-container { 
        display: flex; 
        gap: 10px; 
        margin-bottom: 20px; 
        border-bottom: 2px solid var(--border-color);
        overflow-x: auto;
    }
    
    .tab-button {
        padding: 12px 20px;
        background: transparent;
        border: none;
        color: var(--text-dark);
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    
    .tab-button.active {
        color: var(--primary-blue);
        border-bottom-color: var(--primary-blue);
    }
    
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    
    .sectors-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    
    .sector-card { 
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        background: linear-gradient(135deg, rgba(8, 19, 123, 0.05), rgba(79, 9, 167, 0.05));
        transition: all 0.3s;
    }
    
    .sector-card:hover {
        border-color: var(--primary-blue);
        box-shadow: 0 8px 20px rgba(8, 19, 123, 0.15);
    }
    
    .sector-card-header { 
        display: flex; 
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }
    
    .sector-card-icon {
        font-size: 28px;
        color: var(--primary-blue);
        width: 50px;
        height: 50px;
        background: var(--bg-light);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sector-card-title {
        flex: 1;
    }
    
    .sector-card-title h3 {
        margin: 0;
        color: var(--primary-blue);
        font-size: 16px;
    }
    
    .sector-card-title small {
        color: var(--text-dark);
        opacity: 0.7;
    }
    
    .sector-actions {
        display: flex;
        gap: 8px;
    }
    
    .brands-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .brands-table th { background: var(--bg-light); padding: 15px; text-align: right; color: var(--primary-blue); font-weight: 600; border-bottom: 2px solid var(--border-color); }
    .brands-table td { padding: 15px; border-bottom: 1px solid var(--border-color); }
    
    .brand-logo-preview {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        font-weight: bold;
    }
    
    .status-badge { 
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-active { 
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive { 
        background: #f8d7da;
        color: #721c24;
    }
    
    .btn-small {
        padding: 6px 12px;
        font-size: 12px;
        margin: 0 3px;
    }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        overflow-y: auto;
    }
    
    .modal.active { display: block; }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover { color: black; }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-dark);
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .color-picker-group {
        display: flex;
        gap: 10px;
    }
    
    .color-picker-group input[type="color"] {
        width: 50px;
        height: 40px;
        padding: 2px;
        cursor: pointer;
    }
    
    .modal-footer {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid var(--border-color);
    }
</style>

<?php
$sectors = $db->query("SELECT * FROM sectors ORDER BY display_order ASC")->fetchAll();
?>

<div class="sectors-container">
    <div class="sectors-header">
        <h2>إدارة القطاعات والعلامات التجارية</h2>
        <div>
            <button class="btn-add" onclick="showAddSectorForm()"><i class="fas fa-plus"></i> إضافة قطاع</button>
            <button class="btn-add" onclick="showAddBrandForm()" style="margin-right: 10px;"><i class="fas fa-plus"></i> إضافة علامة</button>
        </div>
    </div>

    <div class="tabs-container" id="sectorTabs">
        <button class="tab-button active" onclick="switchTab('overview', this)">
            <i class="fas fa-th"></i> نظرة عامة
        </button>
        <?php foreach ($sectors as $sector): ?>
            <button class="tab-button" onclick="switchTab('sector-<?php echo $sector['id']; ?>', this)">
                <i class="fas <?php echo $sector['icon']; ?>"></i> <?php echo sanitize($sector['name_ar']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Overview Tab -->
    <div class="tab-content active" id="overview">
        <div class="sectors-grid">
            <?php foreach ($sectors as $sector): 
                $brandCount = $db->query("SELECT COUNT(*) as count FROM brands WHERE sector_id = ?", [$sector['id']])->fetch()['count'];
            ?>
                <div class="sector-card">
                    <div class="sector-card-header">
                        <div class="sector-card-icon">
                            <i class="fas <?php echo $sector['icon']; ?>"></i>
                        </div>
                        <div class="sector-card-title">
                            <h3><?php echo sanitize($sector['name_ar']); ?></h3>
                            <small><?php echo $brandCount; ?> علامات تجارية</small>
                        </div>
                    </div>
                    <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                        <span class="status-badge status-<?php echo $sector['status']; ?>">
                            <?php echo $sector['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                        </span>
                    </div>
                    <div class="sector-actions">
                        <button class="btn-edit btn-small" onclick="editSector(<?php echo $sector['id']; ?>)"><i class="fas fa-edit"></i> تعديل</button>
                        <button class="btn-delete btn-small" onclick="deleteSector(<?php echo $sector['id']; ?>)"><i class="fas fa-trash"></i> حذف</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sector Details Tabs -->
    <?php foreach ($sectors as $sector): 
        $brands = $db->query("SELECT * FROM brands WHERE sector_id = ? ORDER BY display_order ASC", [$sector['id']])->fetchAll();
    ?>
        <div class="tab-content" id="sector-<?php echo $sector['id']; ?>">
            <div style="margin-bottom: 20px;">
                <h3><?php echo sanitize($sector['name_ar']); ?> - العلامات التجارية</h3>
                <button class="btn-add" onclick="showAddBrandForm(<?php echo $sector['id']; ?>)"><i class="fas fa-plus"></i> إضافة علامة جديدة</button>
            </div>
            
            <table class="brands-table">
                <thead>
                    <tr>
                        <th>الشعار</th>
                        <th>الاسم</th>
                        <th>الفئة</th>
                        <th>الوصف</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brands as $brand): ?>
                        <tr>
                            <td>
                                <div class="brand-logo-preview" style="background: linear-gradient(135deg, <?php echo $brand['logo_color']; ?>, <?php echo $brand['logo_color_secondary']; ?>);">
                                    <i class="fas <?php echo $brand['icon']; ?>"></i>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo sanitize($brand['name_ar']); ?></strong><br>
                                <small style="color: #999;"><?php echo sanitize($brand['name']); ?></small>
                            </td>
                            <td><?php echo sanitize($brand['category_ar']); ?></td>
                            <td><?php echo sanitize(mb_substr($brand['description_ar'], 0, 50)); ?>...</td>
                            <td><span class="status-badge status-<?php echo $brand['status']; ?>"><?php echo $brand['status'] === 'active' ? 'نشط' : 'غير نشط'; ?></span></td>
                            <td>
                                <button class="btn-edit btn-small" onclick="editBrand(<?php echo $brand['id']; ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn-delete btn-small" onclick="deleteBrand(<?php echo $brand['id']; ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Sector Modal -->
<div id="sectorModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSectorModal()">&times;</span>
        <h2 id="sectorModalTitle">إضافة قطاع جديد</h2>
        <form id="sectorForm">
            <input type="hidden" id="sectorId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sectorNameAr">الاسم بالعربية *</label>
                    <input type="text" id="sectorNameAr" required>
                </div>
                <div class="form-group">
                    <label for="sectorNameEn">الاسم بالإنجليزية *</label>
                    <input type="text" id="sectorNameEn" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="sectorIcon">الأيقونة *</label>
                <input type="text" id="sectorIcon" placeholder="مثال: fa-university" required>
            </div>
            
            <div class="form-group">
                <label for="sectorDesc">الوصف</label>
                <textarea id="sectorDesc" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="sectorStatus">الحالة</label>
                <select id="sectorStatus">
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeSectorModal()">إلغاء</button>
                <button type="submit" class="btn-save">حفظ</button>
            </div>
        </form>
    </div>
</div>

<!-- Add/Edit Brand Modal -->
<div id="brandModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeBrandModal()">&times;</span>
        <h2 id="brandModalTitle">إضافة علامة تجارية جديدة</h2>
        <form id="brandForm">
            <input type="hidden" id="brandId">
            
            <div class="form-group">
                <label for="brandSectorId">القطاع *</label>
                <select id="brandSectorId" required>
                    <option value="">اختر القطاع</option>
                    <?php foreach ($sectors as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo sanitize($s['name_ar']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="brandNameAr">الاسم بالعربية *</label>
                    <input type="text" id="brandNameAr" required>
                </div>
                <div class="form-group">
                    <label for="brandNameEn">الاسم بالإنجليزية *</label>
                    <input type="text" id="brandNameEn" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="brandCategoryAr">الفئة بالعربية</label>
                    <input type="text" id="brandCategoryAr">
                </div>
                <div class="form-group">
                    <label for="brandCategoryEn">الفئة بالإنجليزية</label>
                    <input type="text" id="brandCategoryEn">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="brandDescAr">الوصف بالعربية</label>
                    <textarea id="brandDescAr" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="brandDescEn">الوصف بالإنجليزية</label>
                    <textarea id="brandDescEn" rows="2"></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="brandIcon">الأيقونة *</label>
                    <input type="text" id="brandIcon" placeholder="مثال: fa-landmark" required>
                </div>
                <div class="form-group">
                    <label for="brandLogoUrl">رابط الشعار (اختياري)</label>
                    <input type="url" id="brandLogoUrl">
                </div>
            </div>
            
            <div class="form-group">
                <label>ألوان الشعار</label>
                <div class="color-picker-group">
                    <div>
                        <span style="display: block; font-size: 12px; margin-bottom: 5px;">اللون الأساسي</span>
                        <input type="color" id="brandColor1" value="#08137b">
                    </div>
                    <div>
                        <span style="display: block; font-size: 12px; margin-bottom: 5px;">اللون الثانوي</span>
                        <input type="color" id="brandColor2" value="#4f09a7">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="brandStatus">الحالة</label>
                <select id="brandStatus">
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeBrandModal()">إلغاء</button>
                <button type="submit" class="btn-save">حفظ</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tabId, button) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-button').forEach(el => el.classList.remove('active'));
        
        // Show selected tab
        document.getElementById(tabId).classList.add('active');
        button.classList.add('active');
    }

    // Sector Functions
    function showAddSectorForm() {
        document.getElementById('sectorId').value = '';
        document.getElementById('sectorForm').reset();
        document.getElementById('sectorModalTitle').textContent = 'إضافة قطاع جديد';
        document.getElementById('sectorModal').classList.add('active');
    }

    function editSector(id) {
        // Fetch sector data and populate form
        // This would need an AJAX call to your admin API
        alert('جاري تطوير هذه الميزة');
    }

    function deleteSector(id) {
        if (confirm('هل تريد حذف هذا القطاع وجميع العلامات المرتبطة به؟')) {
            // Call delete API
            alert('جاري تطوير هذه الميزة');
        }
    }

    function closeSectorModal() {
        document.getElementById('sectorModal').classList.remove('active');
    }

    // Brand Functions
    function showAddBrandForm(sectorId = null) {
        document.getElementById('brandId').value = '';
        document.getElementById('brandForm').reset();
        if (sectorId) {
            document.getElementById('brandSectorId').value = sectorId;
        }
        document.getElementById('brandModalTitle').textContent = 'إضافة علامة تجارية جديدة';
        document.getElementById('brandModal').classList.add('active');
    }

    function editBrand(id) {
        // Fetch brand data and populate form
        alert('جاري تطوير هذه الميزة');
    }

    function deleteBrand(id) {
        if (confirm('هل تريد حذف هذه العلامة التجارية؟')) {
            // Call delete API
            alert('جاري تطوير هذه الميزة');
        }
    }

    function closeBrandModal() {
        document.getElementById('brandModal').classList.remove('active');
    }

    // Form Submissions
    document.getElementById('sectorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Submit sector data
        alert('جاري تطوير هذه الميزة');
    });

    document.getElementById('brandForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Submit brand data
        alert('جاري تطوير هذه الميزة');
    });
</script>
