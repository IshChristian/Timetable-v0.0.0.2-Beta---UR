<?php
session_start();
include("connection.php");

// Check if user is logged in and has campus set
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];
// Get user's campus
$stmt = $connection->prepare("SELECT campus FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$campus = $result->fetch_assoc();
$campus_id = $campus['campus'];
$message = '';
$editing_site = null;
$current_site_id = null;

// Get all colleges and schools for the campus
function getCampusCollegesAndSchools($connection, $campus_id) {
    $data = ['colleges' => [], 'schools' => []];
    
    // Get colleges
    $college_query = "SELECT * FROM college WHERE campus_id = '$campus_id' ORDER BY name";
    $college_result = mysqli_query($connection, $college_query);
    if ($college_result) {
        while ($row = mysqli_fetch_assoc($college_result)) {
            $data['colleges'][$row['id']] = $row;
        }
    }

    // Get schools for these colleges
    if (!empty($data['colleges'])) {
        $college_ids = implode(",", array_keys($data['colleges']));
        $school_query = "SELECT * FROM school WHERE college_id IN ($college_ids) ORDER BY name";
        $school_result = mysqli_query($connection, $school_query);
        if ($school_result) {
            while ($row = mysqli_fetch_assoc($school_result)) {
                $data['schools'][$row['college_id']][] = $row;
            }
        }
    }
    
    return $data;
}

// Get assigned schools for a site
function getAssignedSchools($connection, $site_id) {
    $assigned = [];
    $query = "SELECT school_id FROM site_school WHERE site_id = $site_id";
    $result = mysqli_query($connection, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $assigned[$row['school_id']] = true;
        }
    }
    return $assigned;
}

// Add new site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_site'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    
    if (!empty($name)) {
        mysqli_begin_transaction($connection);
        try {
            $query = "INSERT INTO site (name, campus) VALUES ('$name', '$campus_id')";
            if (mysqli_query($connection, $query)) {
                $site_id = mysqli_insert_id($connection);
                
                // Process school assignments if any
                if (isset($_POST['schools']) && is_array($_POST['schools'])) {
                    foreach ($_POST['schools'] as $school_id) {
                        $school_id = (int)$school_id;
                        $query = "INSERT INTO site_school (site_id, school_id) VALUES ($site_id, $school_id)";
                        mysqli_query($connection, $query);
                    }
                }
                
                mysqli_commit($connection);
                $message = '<div class="alert alert-success">Site added successfully!</div>';
            } else {
                throw new Exception(mysqli_error($connection));
            }
        } catch (Exception $e) {
            mysqli_rollback($connection);
            $message = '<div class="alert alert-danger">Error adding site: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Please enter a site name</div>';
    }
}

// Update site
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_site'])) {
    $id = (int)$_POST['id'];
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    
    if (!empty($name) && $id > 0) {
        $query = "UPDATE site SET name = '$name' WHERE id = $id AND campus = '$campus_id'";
        if (mysqli_query($connection, $query)) {
            $message = '<div class="alert alert-success">Site updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Error updating site: ' . mysqli_error($connection) . '</div>';
        }
    }
}

// Update school assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schools'])) {
    $site_id = (int)$_POST['site_id'];
    $schools = isset($_POST['schools']) ? $_POST['schools'] : [];
    
    if ($site_id > 0) {
        mysqli_begin_transaction($connection);
        try {
            // Remove all current assignments
            mysqli_query($connection, "DELETE FROM site_school WHERE site_id = $site_id");
            
            // Add new assignments
            foreach ($schools as $school_id) {
                $school_id = (int)$school_id;
                $query = "INSERT INTO site_school (site_id, school_id) VALUES ($site_id, $school_id)";
                mysqli_query($connection, $query);
            }
            
            mysqli_commit($connection);
            $message = '<div class="alert alert-success">School assignments updated successfully!</div>';
        } catch (Exception $e) {
            mysqli_rollback($connection);
            $message = '<div class="alert alert-danger">Error updating school assignments: ' . $e->getMessage() . '</div>';
        }
    }
}

// Delete site
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        mysqli_begin_transaction($connection);
        try {
            // First delete school assignments
            mysqli_query($connection, "DELETE FROM site_school WHERE site_id = $id");
            
            // Then delete the site
            $query = "DELETE FROM site WHERE id = $id AND campus = '$campus_id'";
            if (mysqli_query($connection, $query)) {
                mysqli_commit($connection);
                $message = '<div class="alert alert-success">Site deleted successfully!</div>';
            } else {
                throw new Exception(mysqli_error($connection));
            }
        } catch (Exception $e) {
            mysqli_rollback($connection);
            $message = '<div class="alert alert-danger">Error deleting site: ' . $e->getMessage() . '</div>';
        }
    }
}

// Set editing mode
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    if ($edit_id > 0) {
        $result = mysqli_query($connection, "SELECT * FROM site WHERE id = $edit_id AND campus = '$campus_id'");
        if ($result && $editing_site = $result->fetch_assoc()) {
            // Site found and belongs to user's campus
        } else {
            $message = '<div class="alert alert-warning">Site not found or you don\'t have permission to edit it.</div>';
            $editing_site = null;
        }
    }
}

// Get campus data for school management
$campus_data = getCampusCollegesAndSchools($connection, $campus_id);
$colleges = $campus_data['colleges'];
$schools = $campus_data['schools'];

// Get all sites for the current campus
$sites = [];
$query = "SELECT s.*, 
          (SELECT GROUP_CONCAT(DISTINCT sch.name SEPARATOR ', ') 
           FROM site_school ss 
           JOIN school sch ON ss.school_id = sch.id 
           WHERE ss.site_id = s.id) as assigned_schools,
          (SELECT COUNT(*) FROM site_school WHERE site_id = s.id) as school_count
          FROM site s 
          WHERE s.campus = '$campus_id' 
          ORDER BY s.name";
$result = mysqli_query($connection, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sites[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Manage Sites - UR-TIMETABLE</title>
    <link href="assets/img/icon1.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .school-badge {
            font-size: 0.8em;
            margin: 2px;
            cursor: pointer;
        }
        .action-buttons .btn {
            margin: 0 2px;
        }
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .school-checkbox {
            margin-right: 10px;
        }
        .college-section {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .college-name {
            font-weight: bold;
            margin-bottom: 10px;
            color: #0d6efd;
        }
        .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <?php include("./includes/header.php"); ?>
    <?php include("./includes/menu.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Manage Sites</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Manage Sites</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <?php echo $message; ?>
                    
                    <!-- Add/Edit Site Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $editing_site ? 'Edit Site' : 'Add New Site'; ?></h5>
                            <form method="POST" action="">
                                <?php if ($editing_site): ?>
                                    <input type="hidden" name="id" value="<?php echo $editing_site['id']; ?>">
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo $editing_site ? htmlspecialchars($editing_site['name']) : ''; ?>" 
                                               placeholder="Enter site name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <?php if ($editing_site): ?>
                                            <button type="submit" name="update_site" class="btn btn-primary">
                                                <i class="bi bi-check-circle"></i> Update Site
                                            </button>
                                            <a href="manage_sites.php" class="btn btn-secondary">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </a>
                                        <?php else: ?>
                                            <button type="submit" name="add_site" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Add Site
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sites Table -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">All Sites</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Name</th>
                                            <th>Assigned Schools</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        foreach ($sites as $site):    
                                            $assigned_schools = getAssignedSchools($connection, $site['id']);

                                        ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($site['name']); ?></td>
                                            <td>
                                                <?php if (!empty($assigned_schools)): 
                                                    $school_names = [];
                                                    $school_ids = array_keys($assigned_schools);
                                                    $school_ids_str = implode(',', $school_ids);
                                                    $school_query = "SELECT name FROM school WHERE id IN ($school_ids_str)";
                                                    $school_result = mysqli_query($connection, $school_query);
                                                    while ($school = mysqli_fetch_assoc($school_result)) {
                                                        $school_names[] = $school['name'];
                                                    }
                                                    
                                                    foreach (array_slice($school_names, 0, 3) as $name): ?>
                                                        <span class="badge bg-primary school-badge" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($name); ?>">
                                                            <?php echo htmlspecialchars(strlen($name) > 15 ? substr($name, 0, 12) . '...' : $name); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($school_names) > 3): ?>
                                                        <span class="badge bg-secondary">+<?php echo count($school_names) - 3; ?> more</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No schools assigned</span>
                                                <?php endif; ?>

                                            </td>
                                            <td class="action-buttons">
                                                <a href="?edit=<?php echo $site['id']; ?>" class="btn btn-sm btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?delete=<?php echo $site['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this site? This will remove all school assignments.')"
                                                   title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary manage-schools" 
                                                        data-bs-toggle="modal" data-bs-target="#manageSchoolsModal"
                                                        data-site-id="<?php echo $site['id']; ?>"
                                                        data-site-name="<?php echo htmlspecialchars($site['name']); ?>"
                                                        title="Manage Schools">
                                                    <i class="bi bi-building"></i> Manage Schools
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info">
                                                       <a href="upload_facilities.php?site_id=<?php echo $site['id']; ?>">
                                                    <i class="bi bi-building"></i> upload facilities
                                                    </a>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($sites)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No sites found for your campus.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Manage Schools Modal -->
    <div class="modal fade" id="manageSchoolsModal" tabindex="-1" aria-labelledby="manageSchoolsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="manageSchoolsModalLabel">Manage School Assignments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="schoolAssignmentForm" method="POST" action="">
                    <input type="hidden" name="site_id" id="modalSiteId">
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i> Check/uncheck schools to update assignments for: 
                            <strong id="siteNameDisplay"></strong>
                        </div>
                        
                        <!-- Tabs for Assigned/All Schools -->
                        <ul class="nav nav-tabs mb-3" id="schoolTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="assigned-tab" data-bs-toggle="tab" 
                                        data-bs-target="#assigned-schools" type="button" role="tab">
                                    Assigned Schools <span id="assignedCount" class="badge bg-primary ms-1">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="all-tab" data-bs-toggle="tab" 
                                        data-bs-target="#all-schools" type="button" role="tab">
                                    All Schools <span id="totalCount" class="badge bg-secondary ms-1">0</span>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="schoolTabsContent">
                            <!-- Assigned Schools Tab -->
                            <div class="tab-pane fade show active" id="assigned-schools" role="tabpanel">
                                <div id="assignedSchoolsList" class="list-group mb-3">
                                    <!-- Will be populated by JavaScript -->
                                    <div class="text-muted text-center py-3" id="noAssignedSchools">
                                        No schools are currently assigned to this site.
                                    </div>
                                </div>
                            </div>

                            <!-- All Schools Tab -->
                            <div class="tab-pane fade" id="all-schools" role="tabpanel">
                                <?php if (!empty($colleges)): ?>
                                    <?php foreach ($colleges as $college_id => $college): ?>
                                        <?php if (!empty($schools[$college_id])): ?>
                                            <div class="college-section mb-3">
                                                <div class="college-name d-flex align-items-center">
                                                    <i class="bi bi-building me-2"></i>
                                                    <?php echo htmlspecialchars($college['name']); ?>
                                                    <span class="badge bg-light text-dark ms-2">
                                                        <?php echo count($schools[$college_id]); ?> schools
                                                    </span>
                                                </div>
                                                <div class="schools-list ms-4 mt-2">
                                                    <?php foreach ($schools[$college_id] as $school): ?>
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input school-checkbox" 
                                                                   type="checkbox" 
                                                                   name="schools[]" 
                                                                   value="<?php echo $school['id']; ?>"
                                                                   id="school_<?php echo $school['id']; ?>"
                                                                   data-college="<?php echo htmlspecialchars($college['name']); ?>"
                                                                   data-school-name="<?php echo htmlspecialchars($school['name']); ?>">
                                                            <label class="form-check-label" for="school_<?php echo $school['id']; ?>">
                                                                <?php echo htmlspecialchars($school['name']); ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">No colleges or schools found for your campus.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Close
                        </button>
                        <button type="submit" name="update_schools" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript for School Management -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('manageSchoolsModal');
            const schoolCheckboxes = document.querySelectorAll('.school-checkbox');
            const assignedSchoolsList = document.getElementById('assignedSchoolsList');
            const noAssignedSchools = document.getElementById('noAssignedSchools');
            const assignedCount = document.getElementById('assignedCount');
            const totalCount = document.getElementById('totalCount');
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Update total count
            totalCount.textContent = schoolCheckboxes.length;

            // Function to update assigned schools list
            function updateAssignedSchoolsList() {
                const checkedBoxes = document.querySelectorAll('.school-checkbox:checked');
                assignedCount.textContent = checkedBoxes.length;
                
                // Clear current list
                assignedSchoolsList.innerHTML = '';
                
                if (checkedBoxes.length === 0) {
                    noAssignedSchools.style.display = 'block';
                    return;
                }
                
                noAssignedSchools.style.display = 'none';
                
                // Group by college
                const schoolsByCollege = {};
                checkedBoxes.forEach(checkbox => {
                    const college = checkbox.dataset.college;
                    const schoolName = checkbox.dataset.schoolName;
                    const schoolId = checkbox.value;
                    
                    if (!schoolsByCollege[college]) {
                        schoolsByCollege[college] = [];
                    }
                    schoolsByCollege[college].push({id: schoolId, name: schoolName});
                });
                
                // Create list items
                for (const [college, schools] of Object.entries(schoolsByCollege)) {
                    const collegeHeader = document.createElement('div');
                    collegeHeader.className = 'fw-bold text-primary mb-1';
                    collegeHeader.innerHTML = `<i class="bi bi-building"></i> ${college}`;
                    assignedSchoolsList.appendChild(collegeHeader);
                    
                    const schoolList = document.createElement('div');
                    schoolList.className = 'ms-3 mb-2';
                    
                    schools.forEach(school => {
                        const schoolItem = document.createElement('div');
                        schoolItem.className = 'd-flex justify-content-between align-items-center py-1';
                        schoolItem.innerHTML = `
                            <span>${school.name}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove" 
                                    data-school-id="${school.id}" title="Remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                        schoolList.appendChild(schoolItem);
                    });
                    
                    assignedSchoolsList.appendChild(schoolList);
                }
                
                // Add event listeners to remove buttons
                document.querySelectorAll('.btn-remove').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const schoolId = this.dataset.schoolId;
                        const checkbox = document.querySelector(`.school-checkbox[value="${schoolId}"]`);
                        if (checkbox) {
                            checkbox.checked = false;
                            updateAssignedSchoolsList();
                        }
                    });
                });
            }

            // Handle modal show event
            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const siteId = button.getAttribute('data-site-id');
                const siteName = button.getAttribute('data-site-name');
                
                // Update modal title and form
                document.getElementById('modalSiteId').value = siteId;
                document.getElementById('siteNameDisplay').textContent = siteName;
                
                // Reset checkboxes
                schoolCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Fetch assigned schools for this site
                fetch(`get_site_schools.php?site_id=${siteId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Check the assigned schools
                        data.forEach(schoolId => {
                            const checkbox = document.querySelector(`.school-checkbox[value="${schoolId}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                        
                        // Update counts and list
                        updateAssignedSchoolsList();
                    })
                    .catch(error => console.error('Error:', error));
            });
            
            // Update assigned schools list when checkboxes change
            schoolCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateAssignedSchoolsList);
            });
            
            // Handle form submission
            const form = document.getElementById('schoolAssignmentForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                // Get all checked school checkboxes
                const checkedBoxes = document.querySelectorAll('.school-checkbox:checked');
                checkedBoxes.forEach(checkbox => {
                    formData.append('schools[]', checkbox.value);
                });
                
                // Add the update_schools flag
                formData.append('update_schools', '1');
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                
                // Submit form data using fetch
                fetch('manage_sites.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(html => {
                    // Show success message
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success';
                    successAlert.innerHTML = '<i class="bi bi-check-circle"></i> School assignments updated successfully!';
                    
                    // Insert success message at the top of the page
                    const mainContent = document.querySelector('main');
                    if (mainContent) {
                        mainContent.insertBefore(successAlert, mainContent.firstChild);
                        
                        // Scroll to top to show the message
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        
                        // Remove the alert after 5 seconds
                        setTimeout(() => {
                            successAlert.remove();
                        }, 5000);
                    }
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('manageSchoolsModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Reload the page to reflect changes
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error message
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger';
                    errorAlert.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Error saving school assignments. Please try again.';
                    
                    // Insert error message at the top of the form
                    const modalBody = document.querySelector('.modal-body');
                    if (modalBody) {
                        // Remove any existing error messages first
                        const existingAlerts = modalBody.querySelectorAll('.alert');
                        existingAlerts.forEach(alert => alert.remove());
                        
                        modalBody.insertBefore(errorAlert, modalBody.firstChild);
                        
                        // Remove the alert after 5 seconds
                        setTimeout(() => {
                            errorAlert.remove();
                        }, 5000);
                    }
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });
        });
    </script>
    <!-- Load Bootstrap JS first -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>