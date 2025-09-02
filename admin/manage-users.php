<?php
require_once 'includes/layout.php';

$pdo = getDatabaseConnection();

// Handle user actions
if ($_POST['action'] ?? false) {
    $userId = (int)$_POST['user_id'];
    
    switch ($_POST['action']) {
        case 'toggle_status':
            $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$userId]);
            break;
        case 'delete_user':
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            break;
    }
    
    header('Location: manage-users.php');
    exit();
}

// Get all users with stats
$stmt = $pdo->prepare("
    SELECT u.*, 
        (SELECT COUNT(*) FROM appointments WHERE user_id = u.id) as total_appointments,
        (SELECT COUNT(*) FROM user_saved_haircuts WHERE user_id = u.id) as saved_haircuts,
        (SELECT MAX(appointment_date) FROM appointments WHERE user_id = u.id) as last_appointment
    FROM users u 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

startAdminLayout('Manage Users - Admin Panel', 'manage-users');
?>

<style>
    .users-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .users-table th,
    .users-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }
    
    .users-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    
    .users-table tr:hover {
        background: #f8f9fa;
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
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-status {
        padding: 4px 8px;
        border-radius: 15px;
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
    
    .user-stats {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #6c757d;
    }
    
    .actions-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        border-radius: 5px;
        z-index: 1;
    }
    
    .dropdown-content.show {
        display: block;
    }
    
    .dropdown-content form {
        margin: 0;
    }
    
    .dropdown-content button {
        background: none;
        border: none;
        padding: 10px;
        width: 100%;
        text-align: left;
        cursor: pointer;
    }
    
    .dropdown-content button:hover {
        background: #f8f9fa;
    }
    
    .filters {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        align-items: center;
    }
    
    .filter-input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
</style>

<div class="content">
    <?php 
    renderAdminPageHeader(
        'Manage Users', 
        'View and manage all registered users',
        '<button class="btn btn-primary" onclick="exportUsers()"><i class="fas fa-download"></i> Export Users</button>'
    ); 
    ?>
    
    <div class="users-container">
        <div class="filters">
            <input type="text" class="filter-input" id="searchUsers" placeholder="Search users..." onkeyup="filterUsers()">
            <select class="filter-input" id="statusFilter" onchange="filterUsers()">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select class="filter-input" id="dateFilter" onchange="filterUsers()">
                <option value="">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
            </select>
        </div>
        
        <table class="users-table" id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Statistics</th>
                    <th>Last Activity</th>
                
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr data-user-id="<?php echo $user['id']; ?>">
                    <td>
                        <div class="user-info">
                            <div class="user-avatar-small">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600;">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </div>
                                <div style="font-size: 12px; color: #6c757d;">
                                    ID: <?php echo $user['id']; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="user-status <?php echo ($user['is_active'] ?? 1) ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo ($user['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <div class="user-stats">
                            <span><i class="fas fa-calendar"></i> <?php echo $user['total_appointments']; ?> appointments</span>
                        </div>
                    </td>
                    <td>
                        <?php if ($user['last_appointment']): ?>
                            <?php echo date('M j, Y', strtotime($user['last_appointment'])); ?>
                        <?php else: ?>
                            <span style="color: #6c757d;">No appointments</span>
                        <?php endif; ?>
                    </td>
                   
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterUsers() {
    const searchTerm = document.getElementById('searchUsers').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const userName = row.querySelector('.user-info div div').textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        const status = row.querySelector('.user-status').textContent.toLowerCase();
        
        let showRow = true;
        
        // Search filter
        if (searchTerm && !userName.includes(searchTerm) && !email.includes(searchTerm)) {
            showRow = false;
        }
        
        // Status filter
        if (statusFilter && !status.includes(statusFilter)) {
            showRow = false;
        }
        
        // Date filter (simplified - you can enhance this)
        if (dateFilter) {
            // Add date filtering logic here
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function toggleDropdown(userId) {
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
        if (dropdown.id !== `dropdown-${userId}`) {
            dropdown.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    const dropdown = document.getElementById(`dropdown-${userId}`);
    dropdown.classList.toggle('show');
}

function viewUserDetails(userId) {
    // Implement user details modal or redirect
    alert(`View details for user ID: ${userId}`);
}

function exportUsers() {
    // Implement user export functionality
    window.location.href = 'export_users.php';
}

// Close dropdown when clicking outside
window.onclick = function(event) {
    if (!event.target.matches('.btn')) {
        document.querySelectorAll('.dropdown-content').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
}
</script>

<?php endAdminLayout(); ?>
