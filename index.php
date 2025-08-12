<?php
session_start();
include('connection.php');

// Get current system settings
$system_query = "SELECT s.*, ay.year_label 
                FROM system s 
                LEFT JOIN academic_year ay ON s.accademic_year_id = ay.id 
                LIMIT 1";
$system_result = mysqli_query($connection, $system_query);
$system_data = mysqli_fetch_assoc($system_result);

// Get all campuses
$campuses = [];
$res = mysqli_query($connection, "SELECT id, name FROM campus ORDER BY name");
while ($row = mysqli_fetch_assoc($res)) $campuses[] = $row;

// Get academic years
$years = [];
$res = mysqli_query($connection, "SELECT id, year_label FROM academic_year ORDER BY year_label DESC");
while ($row = mysqli_fetch_assoc($res)) $years[] = $row;

$semesters = ['1', '2', '3'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>UR-TIMETABLE</title>
    
    <!-- Favicons -->
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/img/icon1.png" rel="apple-touch-icon">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffff;
            min-height: 100vh;
            color: #333;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 20px 30px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .date-nav {
            display: flex;
            align-items: center;
            gap: 16px;
            font-weight: 600;
            color: #4a5568;
        }

        .date-nav button {
            background: none;
            border: none;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            color: #667eea;
        }

        .date-nav button:hover {
            background: #f7fafc;
        }

        /* Filters */
        .filters-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .filter-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        /* Academic Period Filters */
        .academic-period-filters {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }

        .academic-period-filters .filter-group {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .academic-period-filters .filter-group:hover {
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-color: #667eea;
        }

        /* Step Indicator */
        .step-indicator {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            min-width: 80px;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.5;
        }

        .step.active {
            opacity: 1;
        }

        .step.completed {
            opacity: 0.8;
        }

        .step-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            background: #e2e8f0;
            color: #64748b;
        }

        .step.active .step-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: scale(1.1);
        }

        .step.completed .step-icon {
            background: #10b981;
            color: white;
        }

        .step-label {
            font-size: 0.85rem;
            font-weight: 500;
            text-align: center;
            color: #64748b;
        }

        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }

        /* Filter Steps */
        .filter-steps {
            position: relative;
            overflow: hidden;
        }

        .filter-step {
            display: none;
            animation: slideInRight 0.4s ease-out;
        }

        .filter-step.active {
            display: block;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .filter-group {
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s;
            background: white;
        }

        .form-select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Action Buttons */
        .filter-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Timetable Container */
        .timetable-wrapper {
            display: flex;
            gap: 24px;
            height: calc(100vh - 300px);
            min-height: 600px;
        }

        .timetable-main {
            flex: 1;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .timetable-main.shifted {
            margin-right: 400px;
        }

        /* Timetable Cards */
        .timetable-cards {
            display: grid;
            gap: 16px;
        }

        .timetable-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            border-left: 4px solid transparent;
        }

        .timetable-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .timetable-card.monday { border-left-color: #3b82f6; }
        .timetable-card.tuesday { border-left-color: #10b981; }
        .timetable-card.wednesday { border-left-color: #f59e0b; }
        .timetable-card.thursday { border-left-color: #ef4444; }
        .timetable-card.friday { border-left-color: #8b5cf6; }
        .timetable-card.saturday { border-left-color: #06b6d4; }
        .timetable-card.sunday { border-left-color: #84cc16; }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .session-time {
            font-weight: 600;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 1.1rem;
        }

        .session-day {
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .module-info {
            margin-bottom: 16px;
        }

        .module-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .module-code {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
        }

        .module-credits {
            color: #6b7280;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .lecturer-info, .facility-info {
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .lecturer-info .info-icon {
            background: #dbeafe;
            color: #2563eb;
        }

        .facility-info .info-icon {
            background: #dcfce7;
            color: #16a34a;
        }

        .info-content h6 {
            margin: 0 0 4px 0;
            font-weight: 600;
            color: #374151;
        }

        .info-content p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .groups-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background: #fef3c7;
            border-radius: 10px;
            margin-top: 12px;
        }

        .groups-count {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
            color: #92400e;
        }

        .view-more-btn {
            background: none;
            border: none;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .view-more-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        /* Slide Panel */
        .slide-panel {
            position: fixed;
            top: 0;
            right: -450px;
            width: 420px;
            height: 100vh;
            background: white;
            box-shadow: -8px 0 32px rgba(0, 0, 0, 0.15);
            transition: all 0.4s cubic-bezier(0.23, 1, 0.320, 1);
            z-index: 1000;
            overflow-y: auto;
        }

        .slide-panel.active {
            right: 0;
        }

        .panel-header {
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .panel-title {
            margin: 0 0 8px 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .panel-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .close-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .close-panel:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .panel-content {
            padding: 24px;
        }

        .detail-section {
            margin-bottom: 24px;
        }

        .detail-section h6 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-grid {
            display: grid;
            gap: 8px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #374151;
            font-weight: 500;
            text-align: right;
        }

        .groups-list {
            display: grid;
            gap: 12px;
        }

        .group-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            border-left: 4px solid #667eea;
        }

        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .group-name {
            font-weight: 600;
            color: #374151;
        }

        .group-size {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .group-details {
            display: grid;
            gap: 4px;
            font-size: 0.85rem;
        }

        .group-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #6b7280;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            animation: spin 1s linear infinite;
            margin: 0 auto 12px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* No data message */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Select2 Styling */
        .select2-container--default .select2-selection--single {
            height: 50px !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 46px !important;
            padding-left: 16px !important;
            color: #374151 !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #667eea !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 16px;
            }
            
            .header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .step-indicator {
                justify-content: center;
            }
            
            .step {
                min-width: 60px;
            }
            
            .step-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
            
            .filter-actions {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .timetable-wrapper {
                flex-direction: column;
                height: auto;
            }
            
            .slide-panel {
                width: 100%;
                right: -100%;
            }
            
            .timetable-main.shifted {
                margin-right: 0;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <h1>
                <div class="header-icon">
                    <i class="bi bi-calendar-week"></i>
                </div>
                UR Timetable
            </h1>
            <div class="date-nav">
                <button onclick="previousWeek()">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span id="currentDate">Today</span>
                <button onclick="nextWeek()">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-container">
            <div class="filter-header">
                <div class="filter-title">
                    <i class="bi bi-funnel"></i>
                    Filter Timetable
                </div>
            </div>

            <form id="filterForm">
                <!-- Academic Period Filters -->
                <div class="academic-period-filters">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="filter-group">
                                <label><i class="bi bi-calendar2-week"></i> Academic Year</label>
                                <select class="form-select" id="academic_year_id" name="academic_year_id">
                                    <option value="">All Academic Years</option>
                                    <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year['id']; ?>">
                                            <?php echo $year['year_label']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="filter-group">
                                <label><i class="bi bi-calendar3"></i> Semester</label>
                                <select class="form-select" id="semester" name="semester">
                                    <option value="">All Semesters</option>
                                    <?php foreach ($semesters as $sem): ?>
                                        <option value="<?php echo $sem; ?>">
                                            Semester <?php echo $sem; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" data-step="campus">
                        <div class="step-icon"><i class="bi bi-geo-alt"></i></div>
                        <div class="step-label">Campus</div>
                    </div>
                    <div class="step" data-step="college">
                        <div class="step-icon"><i class="bi bi-building"></i></div>
                        <div class="step-label">College</div>
                    </div>
                    <div class="step" data-step="school">
                        <div class="step-icon"><i class="bi bi-bank"></i></div>
                        <div class="step-label">School</div>
                    </div>
                    <div class="step" data-step="department">
                        <div class="step-icon"><i class="bi bi-diagram-3"></i></div>
                        <div class="step-label">Department</div>
                    </div>
                    <div class="step" data-step="program">
                        <div class="step-icon"><i class="bi bi-mortarboard"></i></div>
                        <div class="step-label">Program</div>
                    </div>
                    <div class="step" data-step="intake">
                        <div class="step-icon"><i class="bi bi-calendar"></i></div>
                        <div class="step-label">Intake</div>
                    </div>
                    <div class="step" data-step="group">
                        <div class="step-icon"><i class="bi bi-people"></i></div>
                        <div class="step-label">Group</div>
                    </div>
                </div>

                <!-- Filter Steps -->
                <div class="filter-steps">
                    <div class="filter-step active" id="campus-step">
                        <div class="filter-group">
                            <label><i class="bi bi-geo-alt"></i> Select Campus</label>
                            <select class="form-select" id="campus_id" name="campus_id">
                                <option value="">All Campuses</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-step" id="college-step">
                        <div class="filter-group">
                            <label><i class="bi bi-building"></i> Select College</label>
                            <select class="form-select" id="college_id" name="college_id">
                                <option value="">All Colleges</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-step" id="school-step">
                        <div class="filter-group">
                            <label><i class="bi bi-bank"></i> Select School</label>
                            <select class="form-select" id="school_id" name="school_id">
                                <option value="">All Schools</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-step" id="department-step">
                        <div class="filter-group">
                            <label><i class="bi bi-diagram-3"></i> Select Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">All Departments</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-step" id="program-step">
                        <div class="filter-group">
                            <label><i class="bi bi-mortarboard"></i> Select Program</label>
                            <select class="form-select" id="program_id" name="program_id">
                                <option value="">All Programs</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-step" id="intake-step">
                        <div class="filter-group">
                            <label><i class="bi bi-calendar"></i> Select Intake</label>
                            <select class="form-select" id="intake_id" name="intake_id">
                                <option value="">All Intakes</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-step" id="group-step">
                        <div class="filter-group">
                            <label><i class="bi bi-people"></i> Select Group</label>
                            <select class="form-select" id="group_id" name="group_id">
                                <option value="">All Groups</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="filter-actions">
                    <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                        <i class="bi bi-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-success" id="applyBtn" style="display: none;">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                    <button type="button" class="btn btn-danger" onclick="resetFilters()">
                        <i class="bi bi-x-circle"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Timetable Wrapper -->
        <div class="timetable-wrapper">
            <div class="timetable-main" id="timetableMain">
                <div class="timetable-cards" id="timetableCards">
                    <!-- Loading State -->
                    <div class="loading" id="loadingIndicator">
                        <div class="spinner"></div>
                        <p>Loading timetable...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide Panel -->
    <div class="slide-panel" id="slidePanel">
        <div class="panel-header">
            <h3 class="panel-title" id="panelTitle">Session Details</h3>
            <p class="panel-subtitle" id="panelSubtitle">Detailed information</p>
            <button class="close-panel" onclick="closePanel()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="panel-content" id="panelContent">
            <!-- Content will be populated dynamically -->
        </div>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="closePanel()"></div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
    class="bi bi-arrow-up-short"></i></a>

<!-- Vendor JS Files -->
<script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chart.js/chart.umd.js"></script>
<script src="assets/vendor/echarts/echarts.min.js"></script>
<script src="assets/vendor/quill/quill.min.js"></script>
<script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
<script src="assets/vendor/tinymce/tinymce.min.js"></script>
<script src="assets/vendor/php-email-form/validate.js"></script>

<!-- Template Main JS File -->
<script src="assets/js/main.js"></script>

<style>
/* Main Container */
.main {
    padding: 15px;
    background: #f8f9fa;
}

/* Card Styling */
.card {
    margin-bottom: 15px;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card-body {
    padding: 15px;
}

/* Filter Title */
.filter-title {
    color: #012970;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.filter-title i {
    margin-right: 0.5rem;
    color: #012970; 
}

/* Step Indicator */
.step-indicator {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    position: relative;
}

.step-indicator::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}

.step {
    position: relative;
    z-index: 2;
    text-align: center;
    width: 80px;
}

.step-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.25rem;
    transition: all 0.3s ease;
}

.step.active .step-icon {
    background: #012970;
    border-color: #012970;
    color: #fff;
}

.step.completed .step-icon {
    background: #28a745;
    border-color: #28a745;
    color: #fff;
}

.step-label {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 500;
}

.step.active .step-label {
    color: #012970;
    font-weight: 600;
}

/* Filter Steps */
.filter-steps {
    position: relative;
    min-height: 120px;
}

.filter-step {
    display: none;
    animation: fadeIn 0.3s ease;
}

.filter-step.active {
    display: block;
}

.filter-group {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    margin-bottom: 0.5rem;
}

.filter-group:hover {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-color: #012970;
}

.filter-group .form-label {
    color: #012970;
    font-weight: 500;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
}

.filter-group .form-label i {
    font-size: 1rem;
}

/* Navigation Buttons */
.filter-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    padding-top: 0.5rem;
    border-top: 1px solid #e9ecef;
    margin-top: 0.5rem;
}

.filter-actions .btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    min-width: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.filter-actions .btn-primary {
    background: #012970;
    border-color: #012970;
}

.filter-actions .btn-primary:hover {
    background: #011f57;
    border-color: #011f57;
}

.filter-actions .btn-secondary {
    background: #6c757d;
    border-color: #6c757d;
}

.filter-actions .btn-secondary:hover {
    background: #5a6268;
    border-color: #5a6268;
}

.filter-actions .btn-success {
    background: #28a745;
    border-color: #28a745;
}

.filter-actions .btn-success:hover {
    background: #218838;
    border-color: #218838;
}

.filter-actions .btn-danger {
    background: #dc3545;
    border-color: #dc3545;
}

.filter-actions .btn-danger:hover {
    background: #c82333;
    border-color: #bd2130;
}

/* Select2 Custom Styling */
.select2-container--default .select2-selection--single {
    height: 32px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 0;
}

.select2-container {
    margin-bottom: 0;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 32px;
    padding-left: 10px;
    font-size: 0.875rem;
    color: #495057;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 30px;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #012970;
}

.select2-dropdown {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Timetable Container */
.timetable-container {
    margin-top: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Table Styling */
.timetable-table {
    font-size: 0.875rem;
    width: 100%;
    margin-bottom: 0;
}

.timetable-table th {
    background-color: #012970;
    color: #fff;
    font-weight: 500;
    padding: 8px;
    border: 1px solid #dee2e6;
}

.timetable-table td {
    padding: 8px;
    border: 1px solid #dee2e6;
    vertical-align: middle;
}

.timetable-table tbody tr:hover {
    background-color: #f8f9fa;
}

.timetable-table .session-header {
    background-color: #f8f9fa;
}

.timetable-table strong {
    color: #012970;
    font-weight: 600;
}

/* Organization Structure and Academic Details */
.header-section {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 10px;
}

.header-section h4 {
    color: #012970;
    font-size: 1rem;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid #dee2e6;
}

.header-content {
    font-size: 0.875rem;
    color: #495057;
}

.header-content div {
    margin-bottom: 5px;
}

.header-content strong {
    color: #012970;
    font-weight: 500;
}

/* No Data Message */
.no-data-message {
    padding: 15px;
}

.no-data-message .alert {
    margin-bottom: 0;
}

.no-data-message h4 {
    color: #012970;
    font-size: 1.1rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.no-data-message h4 i {
    color: #012970;
}

.no-data-message p {
    margin-bottom: 10px;
    color: #495057;
}

.no-data-message h6 {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 5px;
}

.no-data-message div {
    font-size: 0.875rem;
    margin-bottom: 3px;
    color: #495057;
}

.no-data-message strong {
    color: #012970;
    font-weight: 500;
}

/* Loading Indicator */
.loading {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .step-indicator {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .step {
        width: calc(33.333% - 0.5rem);
    }
    
    .step-indicator::before {
        display: none;
    }
    
    .filter-actions {
        flex-wrap: wrap;
    }
    
    .filter-actions .btn {
        width: 100%;
    }
}

/* Add these styles to your existing CSS */
.timetable-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
}

.timetable-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: visible;
    height: fit-content;
}

.timetable-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.timetable-card .card-header {
    background: #012970;
    color: #fff;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.timetable-card .session-time,
.timetable-card .session-day {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.timetable-card .card-body {
    padding: 1.5rem;
    position: relative;
}

.timetable-card .module-info {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.timetable-card .module-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timetable-card .module-main {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timetable-card .module-basic {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.timetable-card .module-code-badge {
    display: flex;
    flex-direction: column;
    background: #012970;
    color: #fff;
    padding: 0.5rem;
    border-radius: 6px;
    min-width: 80px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timetable-card .code-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    opacity: 0.8;
    margin-bottom: 0.25rem;
}

.timetable-card .code-value {
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.timetable-card .module-title-section {
    flex: 1;
}

.timetable-card .module-title {
    color: #2c3e50;
    font-size: 1.2rem;
    margin: 0 0 0.5rem 0;
    font-weight: 600;
    line-height: 1.4;
}

.timetable-card .module-credits {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
    font-size: 0.9rem;
    background: #fff;
    padding: 0.35rem 0.75rem;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.timetable-card .module-credits i {
    color: #012970;
}

.timetable-card .module-period {
    display: flex;
    gap: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.timetable-card .period-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #495057;
    font-size: 0.9rem;
    background: #fff;
    padding: 0.35rem 0.75rem;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.timetable-card .period-item i {
    color: #012970;
    font-size: 1rem;
}

.timetable-card .lecturer-info,
.timetable-card .facility-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.timetable-card .lecturer-info:hover,
.timetable-card .facility-info:hover {
    background: #e9ecef;
}

.timetable-card .info-icon {
    color: #012970;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
}

.timetable-card .info-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.timetable-card .info-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.timetable-card .info-value {
    font-size: 0.95rem;
    color: #2c3e50;
    font-weight: 500;
}

.timetable-card .facility-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.timetable-card .facility-name {
    font-size: 0.95rem;
    color: #2c3e50;
    font-weight: 500;
}

.timetable-card .facility-type {
    font-size: 0.85rem;
    color: #6c757d;
    background: #e9ecef;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    width: fit-content;
}

.timetable-card .facility-capacity {
    font-size: 0.85rem;
    color: #6c757d;
}

.timetable-card .groups-info {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
    margin-top: 1rem;
    position: relative;
}

.timetable-card .groups-info h6 {
    color: #012970;
    font-size: 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.timetable-card .groups-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timetable-card .group-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.timetable-card .group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e9ecef;
}

.timetable-card .group-name {
    font-weight: 600;
    color: #012970;
    font-size: 1rem;
}

.timetable-card .group-size {
    background: #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    color: #495057;
}

.timetable-card .group-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.timetable-card .detail-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #495057;
}

.timetable-card .detail-row i {
    color: #012970;
    font-size: 1rem;
}

@media (max-width: 768px) {
    .timetable-cards {
        grid-template-columns: 1fr;
        padding: 1rem;
    }
    
    .timetable-card .card-body {
        padding: 1rem;
    }
    
    .timetable-card .group-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

/* Add these styles to your existing CSS */
.academic-period-filters {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.academic-period-filters .filter-group {
    background: #fff;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.academic-period-filters .filter-group:hover {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-color: #012970;
}

.academic-period-filters .form-label {
    color: #012970;
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.academic-period-filters .form-label i {
    font-size: 1.1rem;
}

.academic-period-filters .form-select {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 0.5rem;
    font-size: 0.95rem;
    color: #495057;
    transition: border-color 0.3s ease;
}

.academic-period-filters .form-select:focus {
    border-color: #012970;
    box-shadow: 0 0 0 0.2rem rgba(1, 41, 112, 0.1);
}

@media (max-width: 768px) {
    .academic-period-filters {
        padding: 0.75rem;
    }
    
    .academic-period-filters .filter-group {
        padding: 0.75rem;
    }
}

/* Add these styles to your existing CSS */
.groups-header {
    cursor: pointer;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    transition: background-color 0.3s ease;
    margin-bottom: 0;
    user-select: none;
}

.groups-header:hover {
    background: #e9ecef;
}

.groups-header h6 {
    display: flex;
    align-items: center;
    color: #012970;
    margin: 0;
}

.groups-header .bi-chevron-down {
    transition: transform 0.3s ease;
}

.groups-header .bi-chevron-down.rotate-icon {
    transform: rotate(180deg);
}

.groups-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
    background: #fff;
    border-radius: 6px;
    margin-top: 10px;
}

/* Add scrollbar styling */
.groups-list::-webkit-scrollbar {
    width: 6px;
}

.groups-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.groups-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.groups-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Update collapse animation */
.collapse {
    position: absolute;
    width: 100%;
    z-index: 1;
    background: #fff;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-radius: 6px;
    display: none;
    transition: all 0.3s ease;
}

.collapse.show {
    display: block;
    position: relative;
}

.group-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
}

.group-item:last-child {
    margin-bottom: 0;
}

.timetable-card {
    position: relative;
}

/* Update these styles in your existing CSS */
.lecturer-info.compact {
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.lecturer-info.compact .info-icon {
    font-size: 1.2rem;
    color: #012970;
    margin-right: 0.5rem;
}

.lecturer-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-item {
    margin-bottom: 0.25rem;
}

.detail-name {
    font-size: 1rem;
    color: #2c3e50;
    font-weight: 600;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #495057;
}

.contact-item i {
    color: #012970;
    font-size: 1rem;
    width: 1.2rem;
    text-align: center;
}

.contact-link {
    color: #012970;
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-link:hover {
    color: #0056b3;
    text-decoration: underline;
}

@media (max-width: 768px) {
    .lecturer-info.compact {
        padding: 0.5rem;
    }
    
    .contact-item {
        font-size: 0.85rem;
    }
}
</style>
</body>
</html> 