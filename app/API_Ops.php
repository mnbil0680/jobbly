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
// require_once __DIR__ . '/../src/SourceTester.php'; // File missing

// Get action from GET, POST, or JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$requestData = [];

if (file_get_contents('php://input')) {
    $requestData = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!$action) $action = $requestData['action'] ?? null;
}

// Merge POST and GET into requestData if not JSON
if (empty($requestData)) {
    $requestData = array_merge($_GET, $_POST);
}

try {
    switch ($action) {
        case 'read':
            handleRead();
            break;
        case 'sync':
            handleSyncAndSave();
            break;
        case 'save_job':
            handleSaveJob();  // أو handleUnsaveJob()
            break;
        case 'unsave_job':
            handleUnsaveJob();  // أو handleUnsaveJob()
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
        case 'get_user':
            handleGetUser();
            break;
        case 'update_user':
            handleUpdateUser();
            break;
        case 'create_job':
            handleCreate();
            break;
        case 'update_job':
            handleUpdate();
            break;
        case 'delete_job':
            handleDeleteJob();
            break;
        case 'change_password':
            handleChangePassword();
            break;
        case 'change_email':
            handleChangeEmail();
            break;
        case 'delete_user':
            handleDeleteUser();
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
    $view = $_GET['view'] ?? 'explore';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    if ($view === 'saved' && !$userId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    if ($view === 'saved') {
        $rows = $db->getUserSavedJobs($userId, $limit, $offset);
        $totalCount = $db->getUserSavedJobsCount($userId);
    } else {
        $rows = $db->getAllJobs($search, $limit, $offset);
        $totalCount = $db->getTotalJobsCount($search);
    }

    $jobs = array_map(function($job) use ($db, $userId) {
        $mapped = mapDbJobToResponse($job);
        $mapped['isSaved'] = $userId ? $db->isJobSaved($userId, $job['id']) : false;
        return $mapped;
    }, $rows);

    echo json_encode([
        'success' => true,
        'count' => count($jobs),
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($totalCount / $limit),
        'jobs' => array_values($jobs),
        'view' => $view
    ]);
}

function handleSyncAndSave() {
    // Release session lock so other pages can load while we fetch
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $sourceId = $_GET['source_id'] ?? $_POST['source_id'] ?? null;
    $config = getSourceConfig();
    $fetcher = new SourceFetcher($config);

    // Fetch specifically if source_id provided, otherwise all
    $filter = $sourceId ? ['source_id' => $sourceId] : [];
    $fetched = $fetcher->fetch_all($filter);

    $allJobs = [];
    $processedSources = [];

    foreach (($fetched['results'] ?? []) as $result) {
        if (($result['status'] ?? '') !== 'ok') {
            if ($result['http_status'] === 429) {
                // Return early with specific error if we hit rate limits on a single source request
                echo json_encode([
                    'success' => false,
                    'message' => "Rate limit exceeded for {$result['name']}. Please wait before trying again.",
                    'source_id' => $result['source_id']
                ]);
                return;
            }
            continue;
        }

        if (empty($result['all_jobs']) || !is_array($result['all_jobs'])) {
            continue;
        }

        foreach ($result['all_jobs'] as $jobData) {
            if (empty($jobData['title']) || empty($jobData['company_name'])) {
                continue;
            }
            $allJobs[] = $jobData;
            $processedSources[] = $result['source_id'] ?? 'unknown';
        }
    }

    $db = new JobsDatabase();
    $savedCount = $db->bulkInsertJobs($allJobs);

    echo json_encode([
        'success' => true,
        'message' => 'Sync complete',
        'saved_count' => $savedCount,
        'sources' => array_values(array_unique($processedSources))
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

function handleDeleteJob() {
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

        // Create database instance and delete job
        $db = new JobsDatabase();
        $db->deleteJob($requestData['id']);

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Job deleted successfully'
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

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $requestData['name'];
    echo json_encode(['success' => true, 'message' => "User registered successfully"]);
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

function handleChangePassword() {
    global $requestData;
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required']);
        return;
    }
    if (empty($requestData['current_password']) || empty($requestData['new_password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
        return;
    }
    if ($requestData['new_password'] !== ($requestData['confirm_password'] ?? '')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
        return;
    }

    try {
        $db = new JobsDatabase();
        $user = $db->getUserById($_SESSION['user_id']);
        if (!$user) {
            throw new Exception("User not found");
        }
        if (!password_verify($requestData['current_password'], $user['password'])) {
            throw new Exception("Current password is incorrect");
        }
        $db->changePassword($_SESSION['user_id'], $requestData['new_password']);
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleChangeEmail() {
    global $requestData;
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required']);
        return;
    }
    if (empty($requestData['new_email']) || empty($requestData['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New email and password are required']);
        return;
    }

    try {
        $db = new JobsDatabase();
        $user = $db->getUserById($_SESSION['user_id']);
        if (!$user) {
            throw new Exception("User not found");
        }
        if (!password_verify($requestData['password'], $user['password'])) {
            throw new Exception("Password is incorrect");
        }
        $db->changeEmail($_SESSION['user_id'], $requestData['new_email']);
        echo json_encode(['success' => true, 'message' => 'Email changed successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDeleteUser() {
    global $requestData;
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required']);
        return;
    }
    if (empty($requestData['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password is required to delete account']);
        return;
    }

    try {
        $db = new JobsDatabase();
        $db->deleteUser($_SESSION['user_id'], $requestData['password']);
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// --- SAVED JOBS HANDLERS ---

function handleSaveJob() {
    global $requestData;
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required']);
        return;
    }

    $jobId = (int)($requestData['job_id'] ?? 0);
    if (!$jobId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID required']);
        return;
    }

    $db = new JobsDatabase();
    $success = $db->saveJobForUser($_SESSION['user_id'], $jobId);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Job saved!' : 'Failed to save job'
    ]);
}

function handleUnsaveJob() {
    global $requestData;
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required']);
        return;
    }

    $jobId = (int)($requestData['job_id'] ?? 0);
    if (!$jobId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Job ID required']);
        return;
    }

    $db = new JobsDatabase();
    $success = $db->unsaveJob($_SESSION['user_id'], $jobId);

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Job removed from saved!' : 'Failed to remove job'
    ]);
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