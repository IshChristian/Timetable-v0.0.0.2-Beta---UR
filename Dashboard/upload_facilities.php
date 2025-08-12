<?php
session_start();
include("connection.php");

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];
$stmt = $connection->prepare("SELECT campus FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$campus = $result->fetch_assoc();
$campus_id = $campus['campus'] ?? null;

$site_id = isset($_GET['site_id']) ? intval($_GET['site_id']) : 0;

// Handle AJAX upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$campus_id) {
        echo json_encode(['success' => false, 'message' => 'Campus not found']);
        exit();
    }

    if ($site_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid site ID']);
        exit();
    }

    if (!isset($input['facilities']) || !is_array($input['facilities'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid facility data']);
        exit();
    }

    try {
        $connection->begin_transaction();

        $checkStmt = $connection->prepare("SELECT COUNT(*) FROM facility WHERE name = ? AND campus_id = ? AND site = ?");
        $insertStmt = $connection->prepare("INSERT INTO facility (name, type, capacity, campus_id, site) VALUES (?, ?, ?, ?, ?)");

        $duplicates = [];

        foreach ($input['facilities'] as $fac) {
            $name = trim($fac['name'] ?? '');
            $type = trim($fac['type'] ?? '');
            $capacity = (int)($fac['capacity'] ?? 0);

            if ($name && $type && $capacity > 0) {
                $checkStmt->bind_param("sii", $name, $campus_id, $site_id);
                $checkStmt->execute();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->free_result();

                if ($count > 0) {
                    $duplicates[] = $name;
                    continue;
                }

                $insertStmt->bind_param("ssiii", $name, $type, $capacity, $campus_id, $site_id);
                $insertStmt->execute();
            }
        }

        $checkStmt->close();
        $insertStmt->close();

        $connection->commit();

        if (!empty($duplicates)) {
            echo json_encode(['success' => true, 'message' => 'Some facilities were skipped due to duplication: ' . implode(', ', $duplicates)]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Facilities uploaded successfully!']);
        }
    } catch (Exception $e) {
        if (isset($checkStmt)) $checkStmt->close();
        if (isset($insertStmt)) $insertStmt->close();
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Upload Facilities</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
  <link href="assets/css/style.css" rel="stylesheet" />
  <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include("./includes/header.php"); ?>
<?php include("./includes/menu.php"); ?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Upload Facilities</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Upload Facilities</li>
      </ol>
    </nav>
  </div>

  <div class="container mt-4">
    <div class="row">
      <div class="col-md-7">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Excel Upload</strong>
            <a href="sample_facility_template.xlsx" class="btn btn-sm btn-success" download>
              <i class="bi bi-download"></i> Download Template
            </a>
          </div>
          <div class="card-body">
            <form id="uploadForm">
              <input type="hidden" id="site_id" value="<?= htmlspecialchars($site_id) ?>" />
              <div class="mb-3">
                <label class="form-label">Select Excel File</label>
                <input type="file" class="form-control" id="excelFile" accept=".xlsx,.xls" required />
              </div>
              <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Reference Table -->
      <div class="col-md-5">
        <div class="card">
          <div class="card-header">
            <strong>Reference Format</strong>
          </div>
          <div class="card-body">
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th>Column</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><code>Name</code></td>
                  <td>Facility name (e.g., Room 101)</td>
                </tr>
                <tr>
                  <td><code>Type</code></td>
                  <td>Facility type (e.g., Lab, Lecture Hall)</td>
                </tr>
                <tr>
                  <td><code>Capacity</code></td>
                  <td>Integer (must be > 0)</td>
                </tr>
              </tbody>
            </table>
            <div class="mt-2 text-muted small">* Make sure there are no duplicates within the same site and campus.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
document.getElementById('uploadForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    console.log("Form submitted");

    const fileInput = document.getElementById('excelFile');
    const siteId = document.getElementById('site_id').value;

    if (fileInput.files.length === 0) {
        console.log("No file selected");
        Swal.fire('Error', 'Please select an Excel file.', 'error');
        return;
    }

    const file = fileInput.files[0];
    const reader = new FileReader();

    reader.onload = async function (e) {
        try {
            console.log("File loaded, parsing Excel data...");
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet = workbook.Sheets[workbook.SheetNames[0]];
            const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

            console.log("Raw Excel Rows:", rows);

            if (rows.length < 2) {
                console.log("Excel only has header or no data");
                Swal.fire('Error', 'Excel file is empty or has no data.', 'error');
                return;
            }

            rows.shift();

            const facilities = rows
                .filter(row => row.length >= 3)
                .map(row => ({
                    name: row[0],
                    type: row[1],
                    capacity: parseInt(row[2])
                }))
                .filter(f => f.name && f.type && !isNaN(f.capacity) && f.capacity > 0);

            console.log("Parsed Facilities:", facilities);

            if (facilities.length === 0) {
                console.log("No valid facility rows found");
                Swal.fire('Error', 'No valid data found in the Excel file.', 'error');
                return;
            }

            console.log("Sending data to server...");
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ facilities })
            });

            const text = await response.text();
            console.log("Raw server response text:", text);

            try {
                const result = JSON.parse(text);
                console.log("Server Response:", result);

                if (result.success) {
                    Swal.fire('Success', result.message, 'success').then(() => {
                        window.location.href = 'manage_sites.php';
                    });
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch (err) {
                console.error("Failed to parse JSON from server response:", err);
                Swal.fire('Error', 'Server returned invalid response.', 'error');
            }
        } catch (err) {
            console.error("Excel Parsing Error:", err);
            Swal.fire('Error', 'Failed to process the Excel file.', 'error');
        }
    };

    reader.onerror = function (err) {
        console.error("File read error:", err);
        Swal.fire('Error', 'Unable to read the file.', 'error');
    };

    reader.readAsArrayBuffer(file);
});
</script>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    </body>
</html>
