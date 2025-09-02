<?php
require_once 'includes/layout.php';

$pdo = getDatabaseConnection();

// Get all appointments for calendar
$stmt = $pdo->prepare("
    SELECT a.*, u.first_name, u.last_name, u.email, s.name as stylist_name
    FROM appointments a 
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN stylists s ON a.preferred_stylist = s.name
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute();
$appointments = $stmt->fetchAll();

// Group appointments by date for calendar
$appointmentsByDate = [];
foreach ($appointments as $appointment) {
    $date = $appointment['appointment_date'];
    if (!isset($appointmentsByDate[$date])) {
        $appointmentsByDate[$date] = [];
    }
    $appointmentsByDate[$date][] = $appointment;
}

startAdminLayout('Manage Appointments - Admin Panel', 'manage-appointments');
?>

<style>
    .content {
        height: calc(100vh - 140px);
        overflow-y: auto;
        padding-right: 10px;
    }
    
    .appointments-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        min-height: calc(100vh - 240px);
    }
    
    .calendar-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: visible;
    }
    
    .appointments-details {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow-y: auto;
    }
    
    .calendar-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .calendar {
        width: 100%;
    }

    .calendar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px;
        background: linear-gradient(135deg, var(--admin-primary), #e74c3c);
        color: white;
    }

    .calendar-nav {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .calendar-nav:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }

    .calendar-day-header {
        background: #f8f9fa;
        padding: 15px 10px;
        text-align: center;
        font-weight: 600;
        font-size: 14px;
        color: var(--gray-medium);
        border-bottom: 1px solid #e9ecef;
    }

    .calendar-day {
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f4;
        border-right: 1px solid #f1f3f4;
        transition: all 0.2s;
        min-height: 60px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        position: relative;
    }

    .calendar-day:hover {
        background: #f8f9fa;
        transform: scale(1.02);
    }

    .calendar-day.disabled {
        color: #ccc;
        cursor: not-allowed;
        background: #f9f9f9;
    }

    .calendar-day.today {
        background: #fff3cd;
        color: #856404;
        font-weight: 700;
        border: 2px solid #ffc107;
    }

    .calendar-day.has-appointments {
        background: #e3f2fd;
        cursor: pointer;
    }

    .calendar-day.has-appointments:hover {
        background: #bbdefb;
    }

    .calendar-day.selected {
        background: var(--admin-primary);
        color: white;
        font-weight: 700;
    }

    .appointment-count {
        position: absolute;
        top: 5px;
        right: 5px;
        background: var(--admin-primary);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: bold;
    }

    .appointment-item {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.2s;
    }

    .appointment-item:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .appointment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .appointment-time {
        font-weight: bold;
        color: var(--admin-primary);
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
    }

    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d4edda; color: #155724; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    .status-cancelled { background: #f8d7da; color: #721c24; }

    .no-appointments {
        text-align: center;
        color: #6c757d;
        padding: 40px 20px;
    }

    .appointment-actions {
        margin-top: 10px;
        display: flex;
        gap: 10px;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
        border-radius: 5px;
    }
</style>

<div class="content">
    <?php 
    renderAdminPageHeader(
        'Manage Appointments', 
        'View and manage all appointments in the calendar'
    ); 
    ?>
    
    <div class="appointments-container">
        <!-- Calendar Section -->
        <div class="calendar-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Appointments Calendar
                </h3>
            </div>
            
            <div class="calendar-container">
                <div class="calendar">
                    <div class="calendar-header">
                        <button type="button" class="calendar-nav" id="prevMonth">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h3 id="currentMonth"></h3>
                        <button type="button" class="calendar-nav" id="nextMonth">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <div class="calendar-grid">
                        <div class="calendar-day-header">SUN</div>
                        <div class="calendar-day-header">MON</div>
                        <div class="calendar-day-header">TUE</div>
                        <div class="calendar-day-header">WED</div>
                        <div class="calendar-day-header">THU</div>
                        <div class="calendar-day-header">FRI</div>
                        <div class="calendar-day-header">SAT</div>
                        <div id="calendarDays" style="display: contents;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Details -->
        <div class="appointments-details">
            <h3 id="selectedDateTitle">Select a date to view appointments</h3>
            <div id="appointmentsList">
                <div class="no-appointments">
                    <i class="fas fa-calendar-day" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
                    <p>Click on a date in the calendar to view appointments for that day.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calendar functionality
let currentDate = new Date();
const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'];

// Appointments data from PHP
const appointmentsByDate = <?php echo json_encode($appointmentsByDate); ?>;

function generateCalendar(year, month) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const calendarDays = document.getElementById('calendarDays');
    calendarDays.innerHTML = '';
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Generate 42 days (6 weeks)
    for (let i = 0; i < 42; i++) {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.classList.add('calendar-day');
        
        const dayNumber = document.createElement('div');
        dayNumber.textContent = date.getDate();
        dayElement.appendChild(dayNumber);
        
        const dateStr = date.toISOString().split('T')[0];
        
        // Check if day is in current month
        if (date.getMonth() !== month) {
            dayElement.classList.add('disabled');
        }
        
        // Check if day is today
        if (date.getTime() === today.getTime()) {
            dayElement.classList.add('today');
        }
        
        // Check if day has appointments
        if (appointmentsByDate[dateStr]) {
            dayElement.classList.add('has-appointments');
            const count = appointmentsByDate[dateStr].length;
            const countBadge = document.createElement('div');
            countBadge.classList.add('appointment-count');
            countBadge.textContent = count;
            dayElement.appendChild(countBadge);
        }
        
        // Add click handler
        dayElement.addEventListener('click', () => {
            if (!dayElement.classList.contains('disabled')) {
                selectDate(dateStr, date);
            }
        });
        
        calendarDays.appendChild(dayElement);
    }
}

function selectDate(dateStr, dateObj) {
    // Remove previous selection
    document.querySelectorAll('.calendar-day.selected').forEach(day => {
        day.classList.remove('selected');
    });
    
    // Add selection to clicked day
    event.target.closest('.calendar-day').classList.add('selected');
    
    // Update appointments list
    const appointments = appointmentsByDate[dateStr] || [];
    const dateTitle = dateObj.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    document.getElementById('selectedDateTitle').textContent = `Appointments for ${dateTitle}`;
    
    const appointmentsList = document.getElementById('appointmentsList');
    
    if (appointments.length === 0) {
        appointmentsList.innerHTML = `
            <div class="no-appointments">
                <i class="fas fa-calendar-day" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
                <p>No appointments scheduled for this date.</p>
            </div>
        `;
    } else {
        appointmentsList.innerHTML = appointments.map(apt => `
            <div class="appointment-item">
                <div class="appointment-header">
                    <span class="appointment-time">${apt.appointment_time}</span>
                    <span class="status-badge status-${apt.status}">${apt.status.toUpperCase()}</span>
                </div>
                <div><strong>Client:</strong> ${apt.first_name} ${apt.last_name}</div>
                <div><strong>Email:</strong> ${apt.email}</div>
                <div><strong>Service:</strong> ${apt.service_type}</div>
                ${apt.stylist_name ? `<div><strong>Stylist:</strong> ${apt.stylist_name}</div>` : ''}
                ${apt.phone ? `<div><strong>Phone:</strong> ${apt.phone}</div>` : ''}
                ${apt.notes ? `<div><strong>Notes:</strong> ${apt.notes}</div>` : ''}
                ${apt.status === 'cancelled' && apt.cancellation_reason ? `<div><strong>Cancellation Reason:</strong> <span style="color: #dc3545;">${apt.cancellation_reason}</span></div>` : ''}
                <div class="appointment-actions">
                    ${apt.status === 'pending' ? `
                        <button class="btn btn-sm btn-success" onclick="updateStatus(${apt.id}, 'confirmed')">
                            <i class="fas fa-check"></i> Confirm
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="updateStatus(${apt.id}, 'cancelled')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    ` : ''}
                    ${apt.status === 'confirmed' ? `
                        <button class="btn btn-sm btn-info" onclick="updateStatus(${apt.id}, 'completed')">
                            <i class="fas fa-check-circle"></i> Mark Complete
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="updateStatus(${apt.id}, 'cancelled')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }
}

function updateStatus(appointmentId, newStatus) {
    let cancellationReason = '';
    
    if (newStatus === 'cancelled') {
        // Show cancellation reason dialog with pre-defined options
        const reasons = [
            'Emergency scheduling conflict',
            'Customer request',
            'Staff unavailable',
            'Equipment issue',
            'Weather/safety concerns',
            'No-show policy',
            'Other'
        ];
        
        let reasonsText = reasons.map((reason, index) => `${index + 1}. ${reason}`).join('\n');
        const reason = prompt(`Please provide a reason for cancellation:\n\n${reasonsText}\n\nEnter the number (1-${reasons.length}) or type your own reason:`);
        
        if (!reason) {
            return; // User cancelled the prompt
        }
        
        const reasonNum = parseInt(reason.trim());
        if (reasonNum >= 1 && reasonNum <= reasons.length) {
            cancellationReason = reasons[reasonNum - 1];
        } else {
            cancellationReason = reason.trim();
        }
        
        if (!cancellationReason) {
            alert('Cancellation reason is required');
            return;
        }
    }
    
    const confirmMessage = newStatus === 'cancelled' 
        ? `Are you sure you want to cancel this appointment?\n\nReason: ${cancellationReason}` 
        : `Are you sure you want to mark this appointment as ${newStatus}?`;
        
    if (confirm(confirmMessage)) {
        // Show loading state
        const buttons = document.querySelectorAll(`[onclick*="${appointmentId}"]`);
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        });
        
        // AJAX call to update status
        fetch('update_appointment_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                appointment_id: appointmentId,
                status: newStatus,
                cancellation_reason: cancellationReason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message before reload
                alert(`Appointment ${newStatus} successfully!`);
                location.reload(); // Refresh to update the calendar
            } else {
                alert('Error updating appointment status: ' + (data.error || 'Unknown error'));
                // Re-enable buttons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = btn.getAttribute('data-original-text') || 'Update';
                });
            }
        })
        .catch(error => {
            alert('Network error: ' + error.message);
            // Re-enable buttons
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = btn.getAttribute('data-original-text') || 'Update';
            });
        });
    }
}

function updateCalendarHeader() {
    document.getElementById('currentMonth').textContent = 
        monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
}

function initCalendar() {
    updateCalendarHeader();
    generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    
    document.getElementById('prevMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateCalendarHeader();
        generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    });
    
    document.getElementById('nextMonth').addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateCalendarHeader();
        generateCalendar(currentDate.getFullYear(), currentDate.getMonth());
    });
}

// Initialize calendar when page loads
document.addEventListener('DOMContentLoaded', initCalendar);
</script>

<?php endAdminLayout(); ?>
