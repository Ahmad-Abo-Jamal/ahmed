<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>إدارة الطلبات - لوحة التحكم</title>

    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>

        /* Similar styles as other admin pages */

        body {

            font-family: 'Tajawal', sans-serif;

            background-color: #f5f5f5;

            direction: rtl;

        }

        .container {

            max-width: 1200px;

            margin: 0 auto;

            padding: 20px;

        }

        .page-header {

            background: #08137b;

            color: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

        }

        .page-header h1 {

            margin: 0;

        }

        .filters {

            background: white;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 20px;

            display: flex;

            gap: 15px;

            flex-wrap: wrap;

        }

        .filter-group {

            display: flex;

            flex-direction: column;

            min-width: 150px;

        }

        .filter-group label {

            font-weight: 600;

            margin-bottom: 5px;

        }

        .filter-group select, .filter-group input {

            padding: 8px 12px;

            border: 1px solid #ddd;

            border-radius: 5px;

        }

        .btn {

            padding: 8px 15px;

            border: none;

            border-radius: 5px;

            cursor: pointer;

            font-weight: 600;

        }

        .btn-primary {

            background: #08137b;

            color: white;

        }

        .table-container {

            background: white;

            border-radius: 10px;

            overflow: hidden;

            box-shadow: 0 2px 10px rgba(0,0,0,0.1);

        }

        table {

            width: 100%;

            border-collapse: collapse;

        }

        th, td {

            padding: 12px;

            text-align: right;

            border-bottom: 1px solid #eee;

        }

        th {

            background: #f8f9fa;

            font-weight: 600;

        }

        .status-badge {

            padding: 4px 8px;

            border-radius: 4px;

            font-size: 0.85rem;

            font-weight: 600;

        }

        .status-pending {

            background: #fff3cd;

            color: #856404;

        }

        .status-processing {

            background: #cce5ff;

            color: #004085;

        }

        .status-shipped {

            background: #d1ecf1;

            color: #0c5460;

        }

        .status-delivered {

            background: #d4edda;

            color: #155724;

        }

        .status-cancelled {

            background: #f8d7da;

            color: #721c24;

        }

        .payment-paid {

            color: #28a745;

        }

        .payment-pending {

            color: #ffc107;

        }

        .payment-failed {

            color: #dc3545;

        }

        .actions {

            display: flex;

            gap: 5px;

        }

        .btn-sm {

            padding: 5px 10px;

            font-size: 0.85rem;

        }

        .pagination {

            display: flex;

            justify-content: center;

            margin-top: 20px;

            gap: 5px;

        }

        .page-btn {

            padding: 8px 12px;

            border: 1px solid #ddd;

            background: white;

            cursor: pointer;

            border-radius: 4px;

        }

        .page-btn.active {

            background: #08137b;

            color: white;

        }

        .modal {

            display: none;

            position: fixed;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            background: rgba(0,0,0,0.5);

            z-index: 1000;

        }

        .modal-content {

            background: white;

            margin: 5% auto;

            padding: 20px;

            border-radius: 10px;

            max-width: 800px;

            max-height: 80vh;

            overflow-y: auto;

        }

        .modal-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;

        }

        .modal-close {

            cursor: pointer;

            font-size: 1.5rem;

        }

    </style>

</head>

<body>

    <div class="container">

        <div class="page-header">

            <h1><i class="fas fa-shopping-cart"></i> إدارة الطلبات</h1>

        </div>

        <div class="filters">

            <div class="filter-group">

                <label for="statusFilter">حالة الطلب</label>

                <select id="statusFilter">

                    <option value="">جميع الحالات</option>

                    <option value="pending">في الانتظار</option>

                    <option value="processing">قيد المعالجة</option>

                    <option value="shipped">تم الشحن</option>

                    <option value="delivered">تم التسليم</option>

                    <option value="cancelled">ملغي</option>

                </select>

            </div>

            <div class="filter-group">

                <label for="paymentFilter">حالة الدفع</label>

                <select id="paymentFilter">

                    <option value="">جميع الحالات</option>

                    <option value="paid">مدفوع</option>

                    <option value="pending">في الانتظار</option>

                    <option value="failed">فاشل</option>

                </select>

            </div>

            <div class="filter-group">

                <label for="searchInput">البحث</label>

                <input type="text" id="searchInput" placeholder="رقم الطلب أو اسم العميل...">

            </div>

            <div class="filter-group">

                <label>&nbsp;</label>

                <button class="btn btn-primary" onclick="loadOrders()">بحث</button>

            </div>

        </div>

        <div class="table-container">

            <table id="ordersTable">

                <thead>

                    <tr>

                        <th>رقم الطلب</th>

                        <th>العميل</th>

                        <th>التاريخ</th>

                        <th>الإجمالي</th>

                        <th>الحالة</th>

                        <th>الدفع</th>

                        <th>الإجراءات</th>

                    </tr>

                </thead>

                <tbody id="ordersBody">

                    <tr>

                        <td colspan="7" style="text-align: center; padding: 40px;">

                            <i class="fas fa-spinner" style="animation: spin 1s linear infinite;"></i> جاري تحميل الطلبات...

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="pagination" id="pagination"></div>

    </div>

    <!-- Order Details Modal -->

    <div class="modal" id="orderModal">

        <div class="modal-content">

            <div class="modal-header">

                <h2 id="modalTitle">تفاصيل الطلب</h2>

                <span class="modal-close" onclick="closeModal()">&times;</span>

            </div>

            <div id="modalBody">

                <!-- Order details will be loaded here -->

            </div>

        </div>

    </div>

    <script>

        let currentPage = 1;

        let currentFilters = {};

        document.addEventListener('DOMContentLoaded', loadOrders);

        async function loadOrders(page = 1) {

            currentPage = page;

            const status = document.getElementById('statusFilter').value;

            const payment = document.getElementById('paymentFilter').value;

            const search = document.getElementById('searchInput').value;

            currentFilters = { status, payment_status: payment, search };

            const params = new URLSearchParams({

                action: 'get_orders',

                page: page,

                limit: 20,

                ...currentFilters

            });

            try {

                const res = await fetch('/admin/ajax/products.php?' + params);

                const data = await res.json();

                if (data.success) {

                    renderOrders(data.data);

                    renderPagination(data.total, data.page, data.limit);

                } else {

                    document.getElementById('ordersBody').innerHTML = '<tr><td colspan="7" style="text-align: center; color: red;">' + data.message + '</td></tr>';

                }

            } catch (e) {

                console.error('Failed to load orders:', e);

                document.getElementById('ordersBody').innerHTML = '<tr><td colspan="7" style="text-align: center; color: red;">خطأ في تحميل الطلبات</td></tr>';

            }

        }

        function renderOrders(orders) {

            const tbody = document.getElementById('ordersBody');

            if (orders.length === 0) {

                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">لا توجد طلبات</td></tr>';

                return;

            }

            tbody.innerHTML = orders.map(order => `

                <tr>

                    <td>${order.order_number}</td>

                    <td>${order.first_name} ${order.last_name}<br><small>${order.email}</small></td>

                    <td>${new Date(order.created_at).toLocaleDateString('ar-SA')}</td>

                    <td>${order.total_amount.toFixed(2)} ر.س</td>

                    <td><span class="status-badge status-${order.status}">${getStatusText(order.status)}</span></td>

                    <td><i class="fas fa-${order.payment_status === 'paid' ? 'check-circle payment-paid' : order.payment_status === 'pending' ? 'clock payment-pending' : 'times-circle payment-failed'}"></i></td>

                    <td class="actions">

                        <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(${order.id})">عرض</button>

                        <select onchange="updateOrderStatus(${order.id}, this.value)" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">

                            <option value="">تغيير الحالة</option>

                            <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>في الانتظار</option>

                            <option value="processing" ${order.status === 'processing' ? 'selected' : ''}>قيد المعالجة</option>

                            <option value="shipped" ${order.status === 'shipped' ? 'selected' : ''}>تم الشحن</option>

                            <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>تم التسليم</option>

                            <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>ملغي</option>

                        </select>

                    </td>

                </tr>

            `).join('');

        }

        function renderPagination(total, page, limit) {

            const totalPages = Math.ceil(total / limit);

            const pagination = document.getElementById('pagination');

            if (totalPages <= 1) {

                pagination.innerHTML = '';

                return;

            }

            let html = '';

            for (let i = 1; i <= totalPages; i++) {

                html += `<button class="page-btn ${i === page ? 'active' : ''}" onclick="loadOrders(${i})">${i}</button>`;

            }

            pagination.innerHTML = html;

        }

        async function viewOrderDetails(orderId) {

            try {

                const res = await fetch('/admin/ajax/products.php?action=get_order_details&order_id=' + orderId);

                const data = await res.json();

                if (data.success) {

                    renderOrderModal(data.data);

                    document.getElementById('orderModal').style.display = 'block';

                } else {

                    alert('خطأ في تحميل تفاصيل الطلب');

                }

            } catch (e) {

                console.error('Failed to load order details:', e);

                alert('خطأ في تحميل تفاصيل الطلب');

            }

        }

        function renderOrderModal(order) {

            const modalBody = document.getElementById('modalBody');

            const itemsHtml = order.items.map(item => `

                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">

                    <div>

                        <strong>${item.name_ar}</strong><br>

                        <small>الكمية: ${item.quantity}</small>

                    </div>

                    <div>${(item.price * item.quantity).toFixed(2)} ر.س</div>

                </div>

            `).join('');

            modalBody.innerHTML = `

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">

                    <div>

                        <h3>بيانات العميل</h3>

                        <p><strong>الاسم:</strong> ${order.first_name} ${order.last_name}</p>

                        <p><strong>البريد الإلكتروني:</strong> ${order.email}</p>

                        <p><strong>الهاتف:</strong> ${order.phone}</p>

                    </div>

                    <div>

                        <h3>عنوان الشحن</h3>

                        <p>${order.shipping_address}</p>

                        <p>${order.shipping_city}, ${order.shipping_region}</p>

                        <p>${order.shipping_country} ${order.shipping_postal}</p>

                    </div>

                </div>

                <div>

                    <h3>منتجات الطلب</h3>

                    ${itemsHtml}

                    <div style="text-align: left; margin-top: 20px; font-weight: bold;">

                        الإجمالي: ${order.total_amount.toFixed(2)} ر.س

                    </div>

                </div>

            `;

        }

        async function updateOrderStatus(orderId, status) {

            if (!status) return;

            try {

                const res = await fetch('/admin/ajax/products.php', {

                    method: 'POST',

                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },

                    body: 'action=update_order_status&order_id=' + orderId + '&status=' + status

                });

                const data = await res.json();

                if (data.success) {

                    loadOrders(currentPage);

                    alert('تم تحديث حالة الطلب بنجاح');

                } else {

                    alert('خطأ في تحديث حالة الطلب');

                }

            } catch (e) {

                console.error('Failed to update order status:', e);

                alert('خطأ في تحديث حالة الطلب');

            }

        }

        function closeModal() {

            document.getElementById('orderModal').style.display = 'none';

        }

        function getStatusText(status) {

            const statuses = {

                'pending': 'في الانتظار',

                'processing': 'قيد المعالجة',

                'shipped': 'تم الشحن',

                'delivered': 'تم التسليم',

                'cancelled': 'ملغي'

            };

            return statuses[status] || status;

        }

    </script>

</body>

</html>