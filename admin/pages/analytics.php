<?php
// Analytics Dashboard
?>

<style>
.analytics-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
.analytics-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-card { background: linear-gradient(135deg, var(--primary-blue), var(--secondary-purple)); padding: 25px; border-radius: 10px; color: white; }
.stat-label { font-size: 14px; opacity: 0.9; margin-bottom: 10px; }
.stat-value { font-size: 32px; font-weight: 700; margin-bottom: 5px; }
.stat-change { font-size: 12px; opacity: 0.8; }
.stat-change.positive { color: #27ae60; }
.stat-change.negative { color: #e74c3c; }
.charts-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px; }
.chart-card { background: var(--bg-light); padding: 20px; border-radius: 10px; }
.chart-title { font-size: 16px; font-weight: 600; color: var(--primary-blue); margin-bottom: 15px; }
.table-card { background: var(--bg-light); padding: 20px; border-radius: 10px; }
.analytics-table { width: 100%; border-collapse: collapse; }
.analytics-table th { background: white; padding: 15px; text-align: right; font-weight: 600; color: var(--primary-blue); border-bottom: 2px solid var(--border-color); }
.analytics-table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); }
.analytics-table tr:hover { background: white; }
.filter-group { display: flex; gap: 10px; margin-bottom: 20px; }
.filter-select { padding: 10px 15px; border: 2px solid var(--border-color); border-radius: 8px; }
.btn-refresh { padding: 10px 20px; background: var(--primary-blue); color: white; border: none; border-radius: 8px; cursor: pointer; }
</style>

<div class="analytics-container">
    <div class="analytics-header">
        <h2>لوحة التحليلات</h2>
        <div class="filter-group">
            <select class="filter-select" id="daysFilter">
                <option value="7">آخر 7 أيام</option>
                <option value="30" selected>آخر 30 يوم</option>
                <option value="90">آخر 90 يوم</option>
                <option value="365">آخر سنة</option>
            </select>
            <button class="btn-refresh" onclick="loadStats()">تحديث</button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
            <div class="stat-label">إجمالي الزيارات</div>
            <div class="stat-value" id="totalVisits">-</div>
            <div class="stat-change positive">↑ 12% من الأسبوع الماضي</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">عدد المستخدمين الفريدين</div>
            <div class="stat-value" id="uniqueUsers">-</div>
            <div class="stat-change positive">↑ 8% من الأسبوع الماضي</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">مرات عرض الصفحات</div>
            <div class="stat-value" id="totalPageViews">-</div>
            <div class="stat-change negative">↓ 5% من الأسبوع الماضي</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">متوسط مدة الجلسة</div>
            <div class="stat-value" id="avgSessionDuration">-</div>
            <div class="stat-change positive">↑ 15% من الأسبوع الماضي</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-container">
        <div class="chart-card">
            <div class="chart-title">أكثر الصفحات زيارة</div>
            <table class="analytics-table" id="topPagesTable">
                <thead>
                    <tr>
                        <th>اسم الصفحة</th>
                        <th>عدد الزيارات</th>
                    </tr>
                </thead>
                <tbody id="topPagesBody">
                    <tr><td colspan="2" style="text-align: center;">جاري التحميل...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="chart-card">
            <div class="chart-title">أكثر الأحداث حدوثاً</div>
            <table class="analytics-table" id="topEventsTable">
                <thead>
                    <tr>
                        <th>اسم الحدث</th>
                        <th>عدد المرات</th>
                    </tr>
                </thead>
                <tbody id="topEventsBody">
                    <tr><td colspan="2" style="text-align: center;">جاري التحميل...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Statistics -->
    <div style="margin-top: 30px;">
        <h3 style="color: var(--primary-blue); margin-bottom: 15px;">الإحصائيات اليومية</h3>
        <div class="table-card">
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الزيارات</th>
                        <th>المستخدمون الفريدون</th>
                        <th>مرات العرض</th>
                        <th>متوسط المدة</th>
                        <th>معدل الارتداد</th>
                    </tr>
                </thead>
                <tbody id="dailyStatsBody">
                    <tr><td colspan="6" style="text-align: center;">جاري التحميل...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function loadStats() {
    const days = document.getElementById('daysFilter').value;
    
    fetch('/ahmed/api/analytics.php?action=get_stats&days=' + days)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                
                // Update stat cards
                document.getElementById('totalVisits').textContent = stats.total_visits || 0;
                document.getElementById('totalPageViews').textContent = stats.total_page_views || 0;
                
                // Update top pages
                let topPagesHtml = '';
                (stats.top_pages || []).forEach(page => {
                    topPagesHtml += `<tr>
                        <td>${page.page_title || page.page_type}</td>
                        <td>${page.views}</td>
                    </tr>`;
                });
                document.getElementById('topPagesBody').innerHTML = topPagesHtml || '<tr><td colspan="2">لا توجد بيانات</td></tr>';
                
                // Update top events
                let topEventsHtml = '';
                (stats.top_events || []).forEach(event => {
                    topEventsHtml += `<tr>
                        <td>${event.event_name}</td>
                        <td>${event.count}</td>
                    </tr>`;
                });
                document.getElementById('topEventsBody').innerHTML = topEventsHtml || '<tr><td colspan="2">لا توجد بيانات</td></tr>';
                
                // Update daily stats
                let dailyHtml = '';
                (stats.daily_stats || []).forEach(day => {
                    dailyHtml += `<tr>
                        <td>${day.stat_date}</td>
                        <td>${day.total_visits}</td>
                        <td>${day.unique_users}</td>
                        <td>${day.total_page_views}</td>
                        <td>${day.avg_session_duration}s</td>
                        <td>${Number(day.bounce_rate).toFixed(1)}%</td>
                    </tr>`;
                });
                document.getElementById('dailyStatsBody').innerHTML = dailyHtml || '<tr><td colspan="6">لا توجد بيانات</td></tr>';
            }
        });
}

// Load stats on page load
document.addEventListener('DOMContentLoaded', loadStats);

// Auto refresh every 5 minutes
setInterval(loadStats, 5 * 60 * 1000);
</script>
