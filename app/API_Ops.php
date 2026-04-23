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

session_start();

// Include DB operations
require_once __DIR__ . '/DB_Ops.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/SourceFetcher.php';
require_once __DIR__ . '/../src/SourceTester.php';

$requestData = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? $requestData['action'] ?? null;

try {
    switch ($action) {
        case 'read':
            handleRead();
            break;
        case 'sync':
            handleSyncAndSave();
            break;
        case 'test_sources':
            handleTestSources();
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
        case 'login':
            handleLogin();
            break;
        case 'signup':
            handleSignup();
            break;
        case 'logout':
            handleLogout();
            break;
        case 'save_job':
            handleSaveJob();
            break;
        case 'unsave_job':
            handleUnsaveJob();
            break;
        case 'get_user':
            handleGetUser();
            break;
        case 'update_user':
            handleUpdateUser();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleRead() {
    $db = new JobsDatabase();
    $userId = $_SESSION['user_id'] ?? null;
    $search = trim($_GET['search'] ?? '');
    
    $rows = $db->getAllJobs($search);
    $jobs = array_map(function($job) use ($db, $userId) {
        $mapped = mapDbJobToResponse($job);
        $mapped['isSaved'] = $userId ? $db->isJobSaved($userId, $job['id']) : false;
        return $mapped;
    }, $rows);

    echo json_encode([
        'success' => true,
        'count' => count($jobs),
        'jobs' => array_values($jobs),
        'source' => 'database'
    ]);
}

function handleSyncAndSave() {
    $config = getSourceConfig();
    $fetcher = new SourceFetcher($config);
    $fetched = $fetcher->fetch_all();

    $db = new JobsDatabase();
    $savedCount = 0;
    $savedSources = [];
    $failedSources = [];

    foreach (($fetched['results'] ?? []) as $result) {
        if (($result['status'] ?? '') !== 'ok' || empty($result['all_jobs']) || !is_array($result['all_jobs'])) {
            continue;
        }

        foreach ($result['all_jobs'] as $jobData) {
            if (empty($jobData['title']) || empty($jobData['company'])) {
                continue;
            }

            $normalized = normalizeFetchedJob($result['source_id'] ?? 'unknown', $jobData);
            try {
                $db->cacheApiJob($normalized);
                $savedCount++;
                $savedSources[] = $result['source_id'] ?? 'unknown';
            } catch (Exception $e) {
                $failedSources[] = $result['source_id'] ?? 'unknown';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sources fetched and jobs saved to database.',
        'saved_count' => $savedCount,
        'saved_sources' => array_values(array_unique($savedSources)),
        'failed_sources' => array_values(array_unique($failedSources)),
        'fetch_summary' => $fetched['summary'] ?? []
    ]);
}

function handleTestSources() {
    $config = getSourceConfig();
    $tester = new SourceTester($config);

    $sourceId = trim($_GET['source'] ?? '');
    $response = $sourceId !== '' ? $tester->test_source($sourceId) : $tester->test_all();

    echo json_encode([
        'success' => true,
        'results' => $response
    ]);
}

function handleCreate() {
    global $requestData;
    try {
        // Validate required fields
        if (empty($requestData['title']) || empty($requestData['company_name'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Title and Company Name are required'
            ]);
            return;
        }

        // Create database instance and save job
        $db = new JobsDatabase();
        $newJobId = $db->createJob($requestData);

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
    global $requestData;
    try {
        // Validate job ID
        if (empty($requestData['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Job ID is required'
            ]);
            return;
        }

        // Create database instance and update job
        $db = new JobsDatabase();
        $db->updateJob($requestData['id'], $requestData);

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

// --- AUTH HANDLERS ---

function handleLogin() {
    global $requestData;
    if (empty($requestData['email']) || empty($requestData['password'])) {
        throw new Exception("Email and password required");
    }
    $db = new JobsDatabase();
    $user = $db->loginUser($requestData['email'], $requestData['password']);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        echo json_encode(['success' => true, 'user' => ['id' => $user['id'], 'name' => $user['name']]]);
    } else {
        throw new Exception("Invalid email or password");
    }
}

function handleSignup() {
    global $requestData;
    if (empty($requestData['name']) || empty($requestData['email']) || empty($requestData['password'])) {
        throw new Exception("Name, email and password required");
    }
    $db = new JobsDatabase();
    $userId = $db->registerUser($requestData['name'], $requestData['email'], $requestData['password']);
    if ($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $requestData['name'];
        echo json_encode(['success' => true, 'message' => "User registered successfully"]);
    } else {
        throw new Exception("Registration failed (ensure email is unique)");
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true]);
}

function handleGetUser() {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => "Not logged in"]);
        return;
    }
    $db = new JobsDatabase();
    $user = $db->getUserById($_SESSION['user_id']);
    echo json_encode(['success' => true, 'user' => $user]);
}

function handleUpdateUser() {
    global $requestData;
    if (empty($_SESSION['user_id'])) throw new Exception("Unauthorized");
    $db = new JobsDatabase();
    if ($db->updateUserInfo($_SESSION['user_id'], $requestData)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Update failed");
    }
}

// --- SAVED JOBS HANDLERS ---

function handleSaveJob() {
    global $requestData;
    if (empty($_SESSION['user_id'])) throw new Exception("Login required to save jobs");
    if (empty($requestData['job_id'])) throw new Exception("Job ID required");
    
    $db = new JobsDatabase();
    if ($db->saveJobForUser($_SESSION['user_id'], $requestData['job_id'])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to save job");
    }
}

function handleUnsaveJob() {
    global $requestData;
    if (empty($_SESSION['user_id'])) throw new Exception("Login required");
    if (empty($requestData['job_id'])) throw new Exception("Job ID required");
    
    $db = new JobsDatabase();
    if ($db->unsaveJob($_SESSION['user_id'], $requestData['job_id'])) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to unsave job");
    }
}

function getSourceConfig() {
    return [
        'RAPIDAPI_KEY' => defined('RAPIDAPI_KEY') ? RAPIDAPI_KEY : '',
        'ADZUNA_APP_ID' => defined('ADZUNA_APP_ID') ? ADZUNA_APP_ID : '',
        'ADZUNA_APP_KEY' => defined('ADZUNA_APP_KEY') ? ADZUNA_APP_KEY : '',
        'FINDWORK_API_KEY' => defined('FINDWORK_API_KEY') ? FINDWORK_API_KEY : '',
        'JOOBLE_API_KEY' => defined('JOOBLE_API_KEY') ? JOOBLE_API_KEY : '',
        'REED_API_KEY' => defined('REED_API_KEY') ? REED_API_KEY : '',
        'USAJOBS_API_KEY' => defined('USAJOBS_API_KEY') ? USAJOBS_API_KEY : ''
    ];
}

function normalizeFetchedJob($sourceId, $jobData) {
    $externalId = (string)($jobData['id'] ?? md5(($jobData['title'] ?? '') . ($jobData['company'] ?? '')));
    if (strlen($externalId) > 60) {
        $externalId = md5($externalId);
    }
    $salaryMin = isset($jobData['salary_min']) && is_numeric($jobData['salary_min']) ? (float)$jobData['salary_min'] : 0;
    $salaryMax = isset($jobData['salary_max']) && is_numeric($jobData['salary_max']) ? (float)$jobData['salary_max'] : 0;

    return [
        'source_api' => (string)$sourceId,
        'external_id' => $externalId,
        'company_name' => limitText((string)($jobData['company'] ?? 'Unknown'), 120),
        'category_id' => 1,
        'title' => limitText((string)($jobData['title'] ?? ''), 190),
        'description' => limitText((string)($jobData['description'] ?? ''), 5000),
        'location' => limitText((string)($jobData['location'] ?? 'Remote'), 120),
        'job_type' => limitText((string)($jobData['job_type'] ?? 'N/A'), 50),
        'salary_min' => $salaryMin,
        'salary_max' => $salaryMax,
        'currency' => limitText((string)($jobData['currency'] ?? 'USD'), 12)
    ];
}

function limitText($value, $maxLen) {
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }
    if (strlen($trimmed) <= $maxLen) {
        return $trimmed;
    }
    return substr($trimmed, 0, $maxLen);
}

function mapDbJobToResponse($job) {
    $salary = '';
    $min = isset($job['salary_min']) ? (float)$job['salary_min'] : 0;
    $max = isset($job['salary_max']) ? (float)$job['salary_max'] : 0;

    if ($min > 0 || $max > 0) {
        $currency = $job['currency'] ?? 'USD';
        if ($min > 0 && $max > 0) {
            $salary = "{$currency} " . number_format($min) . " - " . number_format($max);
        } elseif ($min > 0) {
            $salary = "{$currency} " . number_format($min) . "+";
        } else {
            $salary = "{$currency} up to " . number_format($max);
        }
    }

    return [
        'id' => (int)($job['id'] ?? 0),
        'title' => (string)($job['title'] ?? ''),
        'company' => (string)($job['company_name'] ?? 'Unknown'),
        'location' => (string)($job['location'] ?? 'N/A'),
        'jobType' => (string)($job['job_type'] ?? 'N/A'),
        'description' => (string)($job['description'] ?? ''),
        'salary' => $salary,
        'status' => (string)($job['status'] ?? ''),
        'created_at' => (string)($job['created_at'] ?? '')
    ];
}
?>