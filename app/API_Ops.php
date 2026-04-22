<?php
// **================================================**
// ** File: API_Ops.php                              **
// ** Responsibility: API endpoints for CRUD         **
// ** - Handle requests from API_Ops.js              **
// ** - Database operations via DB_Ops.php           **
// ** - Return JSON responses                        **
// **================================================**

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include DB operations
require_once __DIR__ . '/DB_Ops.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'read':
            handleRead();
            break;
        case 'create':
            handleCreate();
            break;
        case 'update':
            handleUpdate();
            break;
        case 'delete':
            handleDelete();
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleRead() {
    // Sample jobs data (in a real app, this would come from DB_Ops.php)
    $jobs = [
        [
            'id' => 1,
            'title' => 'Senior PHP Developer',
            'company' => 'Tech Startup Inc',
            'location' => 'San Francisco, CA',
            'jobType' => 'Full-time',
            'description' => 'We are looking for an experienced PHP developer with 5+ years of experience to join our growing team.',
            'salary' => '$80,000 - $120,000'
        ],
        [
            'id' => 2,
            'title' => 'Frontend Developer',
            'company' => 'Digital Agency',
            'location' => 'Remote',
            'jobType' => 'Remote',
            'description' => 'Exciting opportunity to work on modern web applications using React and Vue.js.',
            'salary' => '$60,000 - $90,000'
        ],
        [
            'id' => 3,
            'title' => 'Full Stack Developer',
            'company' => 'E-commerce Solutions',
            'location' => 'New York, NY',
            'jobType' => 'Full-time',
            'description' => 'Join our innovative team building next-generation e-commerce platforms.',
            'salary' => '$70,000 - $100,000'
        ],
        [
            'id' => 4,
            'title' => 'DevOps Engineer',
            'company' => 'Cloud Services',
            'location' => 'Remote',
            'jobType' => 'Full-time',
            'description' => 'Manage and optimize cloud infrastructure for high-traffic applications.',
            'salary' => '$90,000 - $130,000'
        ]
    ];

    // Handle search filter if provided
    if (isset($_GET['search'])) {
        $search = strtolower($_GET['search']);
        $jobs = array_filter($jobs, function($job) use ($search) {
            return strpos(strtolower($job['title']), $search) !== false ||
                   strpos(strtolower($job['company']), $search) !== false ||
                   strpos(strtolower($job['description']), $search) !== false;
        });
    }

    echo json_encode([
        'success' => true,
        'jobs' => array_values($jobs)
    ]);
}

function handleCreate() {
    try {
        // Get JSON data from request body
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (empty($input['title']) || empty($input['company_name'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Title and Company Name are required'
            ]);
            return;
        }

        // Create database instance and save job
        $db = new JobsDatabase();
        $newJobId = $db->createJob($input);

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Job created successfully',
            'id' => $newJobId
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleUpdate() {
    try {
        // Get JSON data from request body
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate job ID
        if (empty($input['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Job ID is required'
            ]);
            return;
        }

        // Create database instance and update job
        $db = new JobsDatabase();
        $db->updateJob($input['id'], $input);

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Job updated successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleDelete() {
    // Get JSON data from request body
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Job ID is required'
        ]);
        return;
    }

    // In a real app, delete from database via DB_Ops.php
    echo json_encode([
        'success' => true,
        'message' => 'Job deleted successfully'
    ]);
}
?>
