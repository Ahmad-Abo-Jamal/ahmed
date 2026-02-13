<?php
// Products Management for E-commerce

$productsStmt = $db->prepare("
    SELECT p.id, p.name_ar, p.slug, p.price, p.stock_quantity, p.is_active, p.rating_average, p.rating_count, c.name_ar as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 500
");
$productsStmt->execute();
$products = $productsStmt->fetchAll();

$categoriesStmt = $db->prepare("SELECT id, name_ar FROM categories WHERE status = 'active' ORDER BY display_order");
$categoriesStmt->execute();
$categories = $categoriesStmt->fetchAll();
?>

<style>
.products-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.products-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.btn-add { padding: 12px 25px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
.products-table { width: 100%; border-collapse: collapse; }
.products-table th { background: var(--bg-light); padding: 15px; text-align: right; font-weight: 600; color: var(--primary-blue); border-bottom: 2px solid var(--border-color); }
.products-table td { padding: 15px; border-bottom: 1px solid var(--border-color); }
.products-table tr:hover { background: #f9f9f9; }
.price-badge { padding: 5px 10px; background: #27ae60; color: white; border-radius: 5px; font-weight: 600; }
.stock-badge { padding: 5px 10px; border-radius: 5px; }
.stock-badge.low { background: #e74c3c; color: white; }
.stock-badge.medium { background: #f39c12; color: white; }
.stock-badge.high { background: #27ae60; color: white; }
.rating-badge { color: #f39c12; }
.action-buttons { display: flex; gap: 10px; }
.btn-edit, .btn-delete { padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; }
.btn-edit { background: #3498db; color: white; }
.btn-delete { background: #e74c3c; color: white; }
.product-form { background: var(--bg-light); padding: 25px; border-radius: 10px; margin-bottom: 20px; display: none; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-blue); }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 8px; font-family: 'Cairo', sans-serif; }
.btn-save { padding: 12px 25px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
.btn-cancel { padding: 12px 25px; background: #95a5a6; color: white; border: none; border-radius: 8px; cursor: pointer; }
</style>

<div class="products-container">
    <div class="products-header">
        <h2>إدارة المنتجات</h2>
        <button class="btn-add" onclick="showAddProduct()"><i class="fas fa-plus"></i> إضافة منتج</button>
    </div>

    <div class="product-form" id="productForm">
        <h3>إضافة منتج جديد</h3>
        <form onsubmit="saveProduct(event)">
            <div class="form-group">
                <label>اسم المنتج (عربي) *</label>
                <input type="text" name="name_ar" required>
            </div>
            <div class="form-group">
                <label>الفئة</label>
                <select name="category_id">
                    <option value="">اختر فئة</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name_ar']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>السعر *</label>
                <input type="number" name="price" step="0.01" required>
            </div>
            <div class="form-group">
                <label>سعر التكلفة</label>
                <input type="number" name="cost_price" step="0.01">
            </div>
            <div class="form-group">
                <label>رصيد المخزون</label>
                <input type="number" name="stock_quantity" value="0">
            </div>
            <div class="form-group">
                <label>نسبة الخصم (%)</label>
                <input type="number" name="discount_percent" step="0.01" value="0">
            </div>
            <div class="form-group">
                <label>الوصف</label>
                <textarea name="description_ar"></textarea>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_featured"> عرض مميز</label>
            </div>
            <button type="submit" class="btn-save">حفظ المنتج</button>
            <button type="button" class="btn-cancel" onclick="hideAddProduct()">إلغاء</button>
        </form>
    </div>

    <table class="products-table">
        <thead>
            <tr>
                <th>اسم المنتج</th>
                <th>الفئة</th>
                <th>السعر</th>
                <th>المخزون</th>
                <th>التقييم</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody id="productsTableBody">
            <?php foreach($products as $product): ?>
                <tr>
                    <td><?php echo sanitize($product['name_ar']); ?></td>
                    <td><?php echo $product['category_name'] ? sanitize($product['category_name']) : '-'; ?></td>
                    <td><span class="price-badge">₪<?php echo number_format($product['price'], 2); ?></span></td>
                    <td>
                        <span class="stock-badge <?php 
                            if ($product['stock_quantity'] <= 5) echo 'low';
                            elseif ($product['stock_quantity'] <= 20) echo 'medium';
                            else echo 'high';
                        ?>">
                            <?php echo $product['stock_quantity']; ?>
                        </span>
                    </td>
                    <td>
                        <span class="rating-badge">
                            <i class="fas fa-star"></i> <?php echo number_format($product['rating_average'] ?? 0, 1); ?> 
                            (<?php echo $product['rating_count'] ?? 0; ?>)
                        </span>
                    </td>
                    <td><?php echo $product['is_active'] ? '<span style="color:green;">✓ مفعل</span>' : '<span style="color:red;">✗ معطل</span>'; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function showAddProduct() {
    document.getElementById('productForm').style.display = 'block';
}

function hideAddProduct() {
    document.getElementById('productForm').style.display = 'none';
}

function saveProduct(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'create_product');

    fetch('/ahmed/api/products.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('تم إضافة المنتج بنجاح');
            location.reload();
        } else {
            alert('خطأ: ' + data.error);
        }
    });
}

function editProduct(productId) {
    alert('سيتم إضافة صفحة التعديل قريباً');
}

function deleteProduct(productId) {
    if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
        alert('سيتم حذف المنتج');
    }
}
</script>
