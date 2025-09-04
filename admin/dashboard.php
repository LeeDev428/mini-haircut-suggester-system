<?php
require_once 'includes/layout.php';

$pdo = getDatabaseConnection();

// Get dashboard statistics
$stmt = $pdo->prepare("
    SELECT 
    COALESCE((SELECT COUNT(*) FROM users WHERE role = 'user'), 0) as total_users,
        COALESCE((SELECT COUNT(*) FROM appointments), 0) as total_appointments,
        COALESCE((SELECT COUNT(*) FROM appointments WHERE status = 'confirmed'), 0) as confirmed_appointments,
        COALESCE((SELECT COUNT(*) FROM appointments WHERE status = 'cancelled'), 0) as cancelled_appointments,
        COALESCE((SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()), 0) as today_appointments,
        COALESCE((SELECT COUNT(*) FROM haircuts), 0) as total_haircuts,
        COALESCE((SELECT COUNT(*) FROM user_saved_haircuts), 0) as total_saved,
        COALESCE((SELECT COUNT(*) FROM user_quiz_results), 0) as total_quizzes,
        COALESCE((SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)), 0) as new_users_month,
        COALESCE((SELECT COUNT(*) FROM haircuts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)), 0) as new_haircuts_week,
        COALESCE((SELECT SUM(CASE WHEN a.status = 'completed' THEN 50 ELSE 0 END) FROM appointments a WHERE MONTH(a.appointment_date) = MONTH(CURDATE()) AND YEAR(a.appointment_date) = YEAR(CURDATE())), 0) as monthly_revenue
");
$stmt->execute();
$stats = $stmt->fetch();

// Get monthly sales data for chart (last 6 months)
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(appointment_date, '%Y-%m') as month,
        COUNT(*) as appointments,
        SUM(CASE WHEN status = 'completed' THEN 50 ELSE 0 END) as revenue
    FROM appointments 
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month ASC
");
$stmt->execute();
$monthlyData = $stmt->fetchAll();

// Get top haircuts data
$stmt = $pdo->prepare("
    SELECT 
        h.name,
        h.style_category,
        COUNT(ush.id) as saves,
        (SELECT COUNT(*) FROM appointments WHERE notes LIKE CONCAT('%', h.name, '%')) as bookings
    FROM haircuts h
    LEFT JOIN user_saved_haircuts ush ON h.id = ush.haircut_id
    GROUP BY h.id, h.name, h.style_category
    ORDER BY (COUNT(ush.id) + (SELECT COUNT(*) FROM appointments WHERE notes LIKE CONCAT('%', h.name, '%'))) DESC
    LIMIT 5
");
$stmt->execute();
$topHaircuts = $stmt->fetchAll();

// Get recent users (last 5)
$stmt = $pdo->prepare("
    SELECT first_name, last_name, email, created_at 
    FROM users 
    WHERE role = 'user' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recentUsers = $stmt->fetchAll();

// Get recent appointments
$stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recentAppointments = $stmt->fetchAll();

startAdminLayout('Admin Dashboard', 'dashboard');
?>

<!-- Include ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border-left: 4px solid var(--admin-primary);
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-card h3 {
        margin: 0 0 10px 0;
        color: #495057;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: var(--admin-primary);
        margin-bottom: 10px;
    }
    
    .stat-change {
        font-size: 12px;
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .chart-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .chart-title {
        margin: 0;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .content-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .section-title {
        margin: 0;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .quick-action {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 20px;
        text-decoration: none;
        color: #495057;
        text-align: center;
        transition: all 0.2s ease;
    }
    
    .quick-action:hover {
        border-color: var(--admin-primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        color: var(--admin-primary);
        text-decoration: none;
    }
    
    .quick-action i {
        font-size: 24px;
        margin-bottom: 10px;
        display: block;
    }
    
    .recent-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .recent-item:last-child {
        border-bottom: none;
    }
    
    .user-avatar-small {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--admin-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
    }
    
    .item-info h5 {
        margin: 0 0 5px 0;
        color: #495057;
    }
    
    .item-info p {
        margin: 0;
        font-size: 12px;
        color: #6c757d;
    }
    
    .status-badge {
        padding: 4px 8px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 600;
        margin-left: auto;
    }
    
    .top-haircut-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .top-haircut-item:last-child {
        border-bottom: none;
    }
    
    .haircut-info h5 {
        margin: 0 0 5px 0;
        color: #495057;
        font-weight: 600;
    }
    
    .haircut-info p {
        margin: 0;
        font-size: 12px;
        color: #6c757d;
    }
    
    .haircut-stats {
        text-align: right;
    }
    
    .haircut-count {
        font-size: 18px;
        font-weight: bold;
        color: var(--admin-primary);
    }
    
    .haircut-label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
    }
</style>

<div class="content">
    <?php 
    renderAdminPageHeader(
        'Admin Dashboard', 
        'Manage your haircut suggestion platform'
    ); 
    ?>
    
    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <h3>Total Users</h3>
            <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
            <div class="stat-change">
                <i class="fas fa-arrow-up"></i>
                +<?php echo $stats['new_users_month']; ?> this month
            </div>
        </div>
        
        <div class="stat-card">
            <h3>Total Appointments</h3>
            <div class="stat-number"><?php echo number_format($stats['total_appointments']); ?></div>
            <div class="stat-change">
                <i class="fas fa-calendar"></i>
                <?php echo $stats['today_appointments']; ?> today
            </div>
        </div>
        
        <div class="stat-card">
            <h3>Confirmed Appointments</h3>
            <div class="stat-number"><?php echo number_format($stats['confirmed_appointments']); ?></div>
            <div class="stat-change">
                <i class="fas fa-check-circle"></i>
                Active bookings
            </div>
        </div>
        
        <div class="stat-card">
            <h3>Monthly Revenue</h3>
            <div class="stat-number">$<?php echo number_format($stats['monthly_revenue'] ?? 0); ?></div>
            <div class="stat-change">
                <i class="fas fa-dollar-sign"></i>
                This month
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="charts-grid">
        <!-- Monthly Sales Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Monthly Sales & Revenue
                </h3>
                <span style="color: #6c757d; font-size: 14px;">Last 6 months</span>
            </div>
            <div id="monthlySalesChart" style="height: 300px;"></div>
        </div>
        
        <!-- Top Haircuts Chart -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">
                    <i class="fas fa-star"></i>
                    Top Haircuts
                </h3>
                <a href="haircut-management.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($topHaircuts)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 40px 20px;">No haircut data available yet</p>
                <?php else: ?>
                    <?php foreach ($topHaircuts as $index => $haircut): ?>
                    <div class="top-haircut-item">
                        <div class="haircut-info">
                            <h5><?php echo htmlspecialchars($haircut['name']); ?></h5>
                            <p><?php echo htmlspecialchars($haircut['style_category'] ?? 'General'); ?></p>
                        </div>
                        <div class="haircut-stats">
                            <div class="haircut-count"><?php echo $haircut['saves'] + $haircut['bookings']; ?></div>
                            <div class="haircut-label">Interactions</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="manage-appointments.php" class="quick-action">
            <i class="fas fa-calendar-check"></i>
            Manage Appointments
        </a>
        <a href="manage-users.php" class="quick-action">
            <i class="fas fa-users"></i>
            Manage Users
        </a>
    <a href="haircut-management.php" class="quick-action">
            <i class="fas fa-cut"></i>
            Manage Haircuts
        </a>
        <a href="profile.php" class="quick-action">
            <i class="fas fa-cog"></i>
            System Settings
        </a>
    </div>
    
    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Recent Users -->
        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-users"></i>
                    Recent Users
                </h3>
                <a href="manage-users.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            
            <?php if (empty($recentUsers)): ?>
                <p style="text-align: center; color: #6c757d; padding: 20px;">No users registered yet</p>
            <?php else: ?>
                <?php foreach ($recentUsers as $user): ?>
                <div class="recent-item">
                    <div class="user-avatar-small">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    <div class="item-info">
                        <h5><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <p>Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Recent Appointments -->
        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-calendar"></i>
                    Recent Appointments
                </h3>
                <a href="manage-appointments.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            
            <?php if (empty($recentAppointments)): ?>
                <p style="text-align: center; color: #6c757d; padding: 20px;">No appointments yet</p>
            <?php else: ?>
                <?php foreach ($recentAppointments as $appointment): ?>
                <div class="recent-item">
                    <div class="user-avatar-small">
                        <?php echo strtoupper(substr($appointment['first_name'], 0, 1) . substr($appointment['last_name'], 0, 1)); ?>
                    </div>
                    <div class="item-info">
                        <h5><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></h5>
                        <p><?php echo ucfirst($appointment['service_type']); ?></p>
                        <p><?php echo date('M j, Y \a\t g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?></p>
                    </div>
                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                        <?php echo ucfirst($appointment['status']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Monthly Sales Chart
    const monthlyData = <?php echo json_encode($monthlyData); ?>;
    
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const chartData = monthlyData.map(item => {
        const [year, month] = item.month.split('-');
        return {
            x: monthNames[parseInt(month) - 1] + ' ' + year,
            appointments: parseInt(item.appointments),
            revenue: parseFloat(item.revenue)
        };
    });
    
    const salesChartOptions = {
        series: [
            {
                name: 'Appointments',
                type: 'column',
                data: chartData.map(item => item.appointments)
            },
            {
                name: 'Revenue ($)',
                type: 'line',
                data: chartData.map(item => item.revenue)
            }
        ],
        chart: {
            height: 300,
            type: 'line',
            toolbar: {
                show: false
            }
        },
    colors: ['#0ea5e9', '#22d3ee'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: [0, 3],
            curve: 'smooth'
        },
        plotOptions: {
            bar: {
                columnWidth: '50%',
                borderRadius: 4
            }
        },
        xaxis: {
            categories: chartData.map(item => item.x),
            labels: {
                style: {
                    colors: '#6c757d'
                }
            }
        },
        yaxis: [
            {
                title: {
                    text: 'Appointments',
                    style: {
                        color: '#0ea5e9'
                    }
                },
                labels: {
                    style: {
                        colors: '#6c757d'
                    }
                }
            },
            {
                opposite: true,
                title: {
                    text: 'Revenue ($)',
                    style: {
                        color: '#f093fb'
                    }
                },
                labels: {
                    style: {
                        colors: '#6c757d'
                    }
                }
            }
        ],
        grid: {
            borderColor: '#f1f3f4'
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        }
    };
    
    const salesChart = new ApexCharts(document.querySelector("#monthlySalesChart"), salesChartOptions);
    salesChart.render();
</script>

<?php endAdminLayout(); ?>
