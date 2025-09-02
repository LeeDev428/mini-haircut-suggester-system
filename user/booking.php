<?php
require_once 'includes/layout.php';

// Ensure user is logged in and get user ID
if (!isset($_SESSION['user']['id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = (int)$_SESSION['user']['id'];
$pdo = getDatabaseConnection();

// Get user info for pre-filling form
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get available stylists
$stmt = $pdo->prepare("SELECT * FROM stylists WHERE is_available = 1 ORDER BY rating DESC");
$stmt->execute();
$stylists = $stmt->fetchAll();

// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $service_type = $_POST['service_type'];
    $preferred_stylist = $_POST['preferred_stylist'];
    $notes = $_POST['notes'] ?? '';
    $phone = $_POST['phone'];
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    
    // Validation
    $today = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $appointmentDateTime = new DateTime($appointment_date . ' ' . $appointment_time, new DateTimeZone('Asia/Manila'));
    
    if ($appointmentDateTime <= $today) {
        $error = 'Cannot book appointments in the past.';
    } else {
        // Check if time slot is available
        $stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
        $stmt->execute([$appointment_date, $appointment_time]);
        
        if ($stmt->fetch()) {
            $error = 'This time slot is already booked. Please choose another time.';
        } else {
            // Insert appointment with auto-confirmed status
            $stmt = $pdo->prepare("
                INSERT INTO appointments (user_id, appointment_date, appointment_time, service_type, preferred_stylist, notes, phone, emergency_contact, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
            ");
            
            if ($stmt->execute([$userId, $appointment_date, $appointment_time, $service_type, $preferred_stylist, $notes, $phone, $emergency_contact])) {
                $success = 'Appointment confirmed! Your booking has been automatically approved and scheduled.';
            } else {
                $error = 'Failed to book appointment. Please try again.';
            }
        }
    }
}

// Get user's upcoming appointments
$stmt = $pdo->prepare("
    SELECT a.*, s.name as stylist_name 
    FROM appointments a 
    LEFT JOIN stylists s ON a.preferred_stylist = s.name
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE() 
    ORDER BY a.appointment_date, a.appointment_time
");
$stmt->execute([$userId]);
$upcomingAppointments = $stmt->fetchAll();
?>

<?php startLayout('Book Appointment', 'booking'); ?>

<style>
    .booking-content {
        padding: 20px 0;
    }
    
    .booking-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-top: 20px;
    }
    
    .booking-form {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .form-header {
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .form-header h2 {
        color: var(--dark-color);
        margin: 0 0 5px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .calendar-container {
        margin: 20px 0;
        max-width: 100%;
    }
    
    .calendar {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
        background: white;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    
    .calendar-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .calendar-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .calendar-nav {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        cursor: pointer;
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .calendar-nav:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        border: none;
    }
    
    .calendar-day-header {
        background: #f8f9fa;
        padding: 15px 10px;
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        color: #495057;
        border-bottom: 2px solid #e9ecef;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .calendar-day {
        padding: 15px 10px;
        text-align: center;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f4;
        border-right: 1px solid #f1f3f4;
        transition: all 0.2s ease;
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        font-size: 14px;
        position: relative;
    }
    
    .calendar-day:hover:not(.disabled) {
        background: #e3f2fd;
        transform: scale(1.05);
        z-index: 2;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .calendar-day.disabled {
        color: #ccc;
        cursor: not-allowed;
        background: #f9f9f9;
        opacity: 0.5;
    }
    
    .calendar-day.selected {
        background: #667eea;
        color: white;
        font-weight: 700;
        transform: scale(1.1);
        z-index: 3;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .calendar-day.today {
        background: #fff3cd;
        color: #856404;
        font-weight: 700;
        border: 2px solid #ffc107;
    }
    
    /* Remove the last column border */
    .calendar-day:nth-child(7n) {
        border-right: none;
    }
    
    /* Remove the last row border */
    .calendar-day:nth-last-child(-n+7) {
        border-bottom: none;
    }
    
    .time-slots {
        margin: 20px 0;
    }
    
    .time-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
    
    .time-slot {
        padding: 10px;
        text-align: center;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
    }
    
    .time-slot:hover {
        border-color: var(--primary-color);
        background: #f8f9fa;
    }
    
    .time-slot.selected {
        border-color: var(--primary-color);
        background: var(--primary-color);
        color: white;
    }
    
    .time-slot.disabled {
        background: #f8f9fa;
        color: #ccc;
        cursor: not-allowed;
        border-color: #f1f3f4;
    }
    
    .form-section {
        margin: 25px 0;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .stylist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }
    
    .stylist-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    
    .stylist-card:hover {
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }
    
    .stylist-card.selected {
        border-color: var(--primary-color);
        background: #f8f9fa;
    }
    
    .stylist-name {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .stylist-rating {
        color: #ffc107;
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .stylist-specialties {
        font-size: 12px;
        color: var(--gray-medium);
    }
    
    .upcoming-appointments {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        height: fit-content;
    }
    
    .appointment-item {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        background: #f8f9fa;
    }
    
    .appointment-date {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 5px;
    }
    
    .appointment-details {
        font-size: 14px;
        color: var(--gray-medium);
        line-height: 1.5;
    }
    
    .appointment-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        margin-top: 8px;
    }
    
    .appointment-status.pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .appointment-status.confirmed {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-success {
        background: #ecfdf5;
        color: #10b981;
        border: 1px solid #a7f3d0;
    }
    
    .alert-error {
        background: #fef2f2;
        color: #ef4444;
        border: 1px solid #fecaca;
    }
    
    @media (max-width: 768px) {
        .booking-grid {
            grid-template-columns: 1fr;
        }
        
        .time-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .stylist-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="booking-content">
    <?php 
    renderPageHeader(
        'Book Appointment', 
        'Schedule your haircut appointment with our professional stylists',
    ); 
    ?>
    
    <div class="booking-grid">
        <div class="booking-form">
            <div class="form-header">
                <h2><i class="fas fa-calendar-plus"></i> Schedule Your Appointment</h2>
                <p>Choose your preferred date, time, and service type</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="bookingForm">
                <!-- Calendar Section -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-calendar"></i> Select Date
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
                                <!-- Calendar days will be populated by JavaScript -->
                                <div id="calendarDays" style="display: contents;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="appointment_date" id="selectedDate" required>
                </div>
                
                <!-- Time Selection -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-clock"></i> Select Time
                    </div>
                    
                    <div class="time-slots">
                        <div class="time-grid" id="timeSlots">
                            <!-- Time slots will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <input type="hidden" name="appointment_time" id="selectedTime" required>
                </div>
                
                <!-- Service Type -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-cut"></i> Service Details
                    </div>
                    
                    <div class="form-group">
                        <label for="service_type">Service Type *</label>
                        <select name="service_type" id="service_type" required>
                            <option value="">Choose a service</option>
                            <option value="consultation">Consultation (Free)</option>
                            <option value="haircut">Haircut</option>
                            <option value="styling">Hair Styling</option>
                            <option value="coloring">Hair Coloring</option>
                            <option value="treatment">Hair Treatment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Preferred Stylist (Optional)</label>
                        <div class="stylist-grid">
                            <?php foreach ($stylists as $stylist): ?>
                                <div class="stylist-card" onclick="selectStylist('<?php echo htmlspecialchars($stylist['name']); ?>')">
                                    <div class="stylist-name"><?php echo htmlspecialchars($stylist['name']); ?></div>
                                    <div class="stylist-rating">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i < floor($stylist['rating']) ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                        <?php echo $stylist['rating']; ?>
                                    </div>
                                    <div class="stylist-specialties"><?php echo htmlspecialchars($stylist['specialties']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="preferred_stylist" id="selectedStylist">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Special Requests or Notes</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Any special requests, preferences, or notes for your stylist..."></textarea>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-phone"></i> Contact Information
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required placeholder="+63 9XX XXX XXXX">
                    </div>
                    
                    <div class="form-group">
                        <label for="emergency_contact">Emergency Contact (Optional)</label>
                        <input type="text" name="emergency_contact" id="emergency_contact" placeholder="Name and phone number">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border: none;">
                    <i class="fas fa-calendar-check"></i>
                    Book Appointment</button></button>
                </button>
            </form>
        </div>
        
        <div class="upcoming-appointments">
            <h3><i class="fas fa-calendar-alt"></i> Your Upcoming Appointments</h3>
            
            <?php if (empty($upcomingAppointments)): ?>
                <p style="text-align: center; color: var(--gray-medium); margin: 30px 0;">
                    <i class="fas fa-calendar" style="font-size: 2rem; display: block; margin-bottom: 10px;"></i>
                    No upcoming appointments
                </p>
            <?php else: ?>
                <?php foreach ($upcomingAppointments as $appointment): ?>
                    <div class="appointment-item">
                        <div class="appointment-date">
                            <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?> at 
                            <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                        </div>
                        <div class="appointment-details">
                            <strong>Service:</strong> <?php echo ucfirst($appointment['service_type']); ?><br>
                            <?php if ($appointment['stylist_name']): ?>
                                <strong>Stylist:</strong> <?php echo htmlspecialchars($appointment['stylist_name']); ?><br>
                            <?php endif; ?>
                            <?php if ($appointment['notes']): ?>
                                <strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?>
                            <?php endif; ?>
                        </div>
                        <span class="appointment-status <?php echo $appointment['status']; ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    let currentDate = new Date(today);
    let selectedDate = null;
    let selectedTime = null;
    
    // Time slots (9 AM to 6 PM)
    const timeSlots = [
        '09:00', '10:00', '11:00', '12:00', 
        '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'
    ];
    
    function initCalendar() {
        updateCalendarDisplay();
        generateTimeSlots();
    }
    
    function updateCalendarDisplay() {
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        document.getElementById('currentMonth').textContent = 
            monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
        
        generateCalendarDays();
    }
    
    function generateCalendarDays() {
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        const calendarDays = document.getElementById('calendarDays');
        calendarDays.innerHTML = '';
        
        for (let i = 0; i < 42; i++) {
            const day = new Date(startDate);
            day.setDate(startDate.getDate() + i);
            
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day.getDate();
            
            // Check if day is in current month
            if (day.getMonth() !== currentDate.getMonth()) {
                dayElement.classList.add('disabled');
            }
            // Check if day is in the past
            else if (day < today) {
                dayElement.classList.add('disabled');
            }
            // Check if day is today
            else if (day.getTime() === today.getTime()) {
                dayElement.classList.add('today');
            }
            
            // Add click handler for selectable days
            if (!dayElement.classList.contains('disabled')) {
                dayElement.addEventListener('click', function() {
                    selectDate(day);
                });
            }
            
            calendarDays.appendChild(dayElement);
        }
    }
    
    function selectDate(date) {
        // Remove previous selection
        document.querySelectorAll('.calendar-day.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Add selection to clicked day
        event.target.classList.add('selected');
        
        selectedDate = date;
        document.getElementById('selectedDate').value = formatDate(date);
        
        // Reset time selection
        selectedTime = null;
        document.getElementById('selectedTime').value = '';
        document.querySelectorAll('.time-slot.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        console.log('Selected date:', formatDate(date));
    }
    
    function generateTimeSlots() {
        const timeSlotsContainer = document.getElementById('timeSlots');
        timeSlotsContainer.innerHTML = '';
        
        timeSlots.forEach(time => {
            const slot = document.createElement('div');
            slot.className = 'time-slot';
            slot.textContent = formatTime(time);
            slot.addEventListener('click', function() {
                selectTime(time);
            });
            timeSlotsContainer.appendChild(slot);
        });
    }
    
    function selectTime(time) {
        if (!selectedDate) {
            alert('Please select a date first');
            return;
        }
        
        // Remove previous selection
        document.querySelectorAll('.time-slot.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Add selection to clicked time
        event.target.classList.add('selected');
        
        selectedTime = time;
        document.getElementById('selectedTime').value = time;
        
        console.log('Selected time:', time);
    }
    
    function selectStylist(stylistName) {
        // Remove previous selection
        document.querySelectorAll('.stylist-card.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Add selection to clicked stylist
        event.target.closest('.stylist-card').classList.add('selected');
        
        document.getElementById('selectedStylist').value = stylistName;
        
        console.log('Selected stylist:', stylistName);
    }
    
    function formatDate(date) {
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0');
    }
    
    function formatTime(time) {
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour;
        return displayHour + ':' + minutes + ' ' + ampm;
    }
    
    // Calendar navigation
    document.getElementById('prevMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        updateCalendarDisplay();
    });
    
    document.getElementById('nextMonth').addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        updateCalendarDisplay();
    });
    
    // Form validation
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (!selectedDate || !selectedTime) {
            e.preventDefault();
            alert('Please select both date and time for your appointment.');
            return false;
        }
    });
    
    // Make selectStylist globally accessible
    window.selectStylist = selectStylist;
    
    // Initialize calendar
    initCalendar();
});
</script>

<?php endLayout(); ?>
