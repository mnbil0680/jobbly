<?php
// **================================================**
// ** File: DB_Ops.php                               **
// ** Responsibility: Database operations            **
// ** - Database connection (config.php)             **
// ** - Server-side validation logic                 **
// ** - INSERT function                              **
// ** - SELECT / Search function                     **
// ** - UPDATE function                              **
// ** - DELETE function                              **
// ** - Return JSON responses to client              **
// **================================================**

// This file is currently a placeholder
// Future implementation will include:
// - MySQLi/PDO database connection
// - CRUD operation functions
// - Input validation and sanitization
// - Error handling

// Load configuration
require_once __DIR__ . '/../config/config.php';

class JobsDatabase {
    private $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            throw new Exception('Database connection failed: ' . $this->connection->connect_error);
        }
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Get all jobs with optional search filter
     */
    public function getAllJobs($search = '', $limit = null, $offset = 0) {
        $query = "SELECT j.*, c.name as category_name 
                  FROM jobs j 
                  LEFT JOIN categories c ON j.category_id = c.id 
                  WHERE 1=1";

        $params = [];
        $types = "";

        if (!empty($search)) {
            $query .= " AND (j.title LIKE ? OR j.company_name LIKE ? OR j.description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        $query .= " ORDER BY j.created_at DESC";

        if ($limit !== null) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= "ii";
        }

        $stmt = $this->connection->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get total count of jobs for pagination
     */
    public function getTotalJobsCount($search = '') {
        $query = "SELECT COUNT(*) as total FROM jobs j WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $query .= " AND (j.title LIKE ? OR j.company_name LIKE ? OR j.description LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        $stmt = $this->connection->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total'] ?? 0;
    }

    /**
     * Get a single job by ID
     */
    public function getJobById($id) {
        // TODO: Implement SELECT query for single job
        $stmt = $this->connection->prepare("SELECT j.*, c.name as category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Create a new job
     */
    public function createJob($data) {
        // TODO: Implement INSERT query with validation
        if(empty($data['title']) || empty($data['company_name'])){
            throw new Exception('Invalid job data: title and company_name are required');
        }
        if(empty($data['category_id'])){
            throw new Exception('Invalid job data: category_id is required');
        }

        $data = $this->sanitizeData($data);

        $stmt = $this->connection->prepare(
            "INSERT INTO JOBS
            (company_name, category_id, title, description, location, job_type, salary_min, salary_max, currency, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if(!$stmt){
            throw new Exception("Prepare failed: " . $this->connection->error);
        }

        $stmt->bind_param(
            "sissssddss",
            $data['company_name'],
            $data['category_id'],
            $data['title'],
            $data['description'],
            $data['location'],
            $data['job_type'],
            $data['salary_min'],
            $data['salary_max'],
            $data['currency'],
            $data['status']
        );

        $status = 'active';

        if(!$stmt->execute()){
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $newId = $this->connection->insert_id;
        $stmt->close();
        return $newId;
    }

    // ceateJob api version
    private $selectStmt = null;
    private $insertStmt = null;

    public function cacheApiJob($jobData) {
        if (!$this->selectStmt) {
            $this->selectStmt = $this->connection->prepare("SELECT id FROM jobs WHERE poster_id = ?");
        }
        
        $poster_id = $jobData['poster_id'] ?? ($jobData['source_api'] . "_" . $jobData['external_id']);
        $this->selectStmt->bind_param("s", $poster_id);
        $this->selectStmt->execute();
        $existing = $this->selectStmt->get_result()->fetch_assoc();
        if ($existing) return $existing['id'];

        if (!$this->insertStmt) {
            $this->insertStmt = $this->connection->prepare(
                "INSERT INTO jobs (company_name, poster_id, category_id, title, description, location, job_type, salary_min, salary_max, currency, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
        }

        $status = 'open';
        $this->insertStmt->bind_param(
            "ssissssddss",
            $jobData['company_name'],
            $poster_id,
            $jobData['category_id'],
            $jobData['title'],
            $jobData['description'],
            $jobData['location'],
            $jobData['job_type'],
            $jobData['salary_min'],
            $jobData['salary_max'],
            $jobData['currency'],
            $status
        );
        
        if (!$this->insertStmt->execute()) {
            error_log("Failed to insert job: " . $this->insertStmt->error);
            return false;
        }
        return $this->connection->insert_id;
    }

    /**
     * Bulk insert jobs into the database
     * Uses INSERT IGNORE to skip existing jobs (based on unique poster_id)
     */
    public function bulkInsertJobs($jobs) {
        if (empty($jobs)) return 0;

        $totalAffected = 0;
        $batchSize = 100; // Insert in batches to avoid query size limits
        $batches = array_chunk($jobs, $batchSize);

        foreach ($batches as $batch) {
            $values = [];
            $placeholders = [];
            $types = "";

            foreach ($batch as $job) {
                $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                // Ensure required fields
                $company = $job['company_name'] ?? 'Unknown';
                $poster_id = $job['poster_id'] ?? null;
                $category_id = $job['category_id'] ?? 1; // Default to 1
                $title = $job['title'] ?? 'Untitled Job';
                $description = $job['description'] ?? '';
                $location = $job['location'] ?? 'Remote';
                $job_type = $job['job_type'] ?? 'Full-time';
                $salary_min = (float)($job['salary_min'] ?? 0);
                $salary_max = (float)($job['salary_max'] ?? 0);
                $currency = $job['currency'] ?? 'USD';
                $status = 'open';

                $values[] = $company;
                $values[] = $poster_id;
                $values[] = $category_id;
                $values[] = $title;
                $values[] = $description;
                $values[] = $location;
                $values[] = $job_type;
                $values[] = $salary_min;
                $values[] = $salary_max;
                $values[] = $currency;
                $values[] = $status;

                $types .= "ssissssddss";
            }

            $sql = "INSERT IGNORE INTO jobs 
                    (company_name, poster_id, category_id, title, description, location, job_type, salary_min, salary_max, currency, status) 
                    VALUES " . implode(', ', $placeholders);

            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }

            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $totalAffected += $this->connection->affected_rows;
            $stmt->close();
        }

        return $totalAffected;
    }

    /**
     * Update an existing job
     */
    public function updateJob($id, $data) {
        // TODO: Implement UPDATE query with validation
        if(empty($id) || !is_numeric($id)){
            throw new Exception('Job ID is required for update');
        }
        $existingJob = $this->getJobById($id);
        if(!$existingJob){
            throw new Exception('Job not found with ID: ' . $id);
        }


        if (isset($data['title']) && empty($data['title'])) {
            throw new Exception('Title cannot be empty');
        }
        if (isset($data['company_name']) && empty($data['company_name'])) {
            throw new Exception('Company Name cannot be empty');
        }


        $data = $this->sanitizeData($data);

        $updateFields = [];
        $types = '';
        $values = [];


        $fieldMap = [
            'title' => 's',
            'company_name' => 's',
            'location' => 's',
            'job_type' => 's',
            'description' => 's',
            'salary_min' => 'd',
            'salary_max' => 'd',
            'currency' => 's',
            'category_id' => 'i',
            'status' => 's'
        ];

        foreach ($fieldMap as $field => $type) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $types .= $type;
                $values[] = $data[$field];
            }
        }

        if(empty($updateFields)){
            throw new Exception('No valid fields provided for update');
        }

        $values[] = $id;
        $types .= 'i';

        $sql = "UPDATE JOBS SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        if(!$stmt){
            throw new Exception("Prepare failed: " . $this->connection->error);
        }

        $stmt->bind_param($types, ...$values);

        if(!$stmt->execute()){
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        return true;
    }


    /**
     * Delete a job
     */
    public function deleteJob($id) {
        // TODO: Implement DELETE query
        if(empty($id) || !is_numeric($id)){
            throw new Exception('Job ID is required for delete');
        }
        $existingJob = $this->getJobById($id);
        if(!$existingJob){
            throw new Exception('Job not found with ID: ' . $id);
        }

        $stmt = $this->connection->prepare("DELETE FROM JOBS WHERE id = ?");

        if(!$stmt){
            throw new Exception("Prepare failed: " . $this->connection->error);
        }
        $stmt->bind_param("i", $id);

        if(!$stmt->execute()){
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        return true;
    }

    /**
     * Validate job data
     */
    private function validateJobData($data) {
        // TODO: Implement validation logic
        return true;
    }

    /**
     * Sanitize input data
     */
    private function sanitizeData($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * user logic
     */

    public function updateGuestProfile($userId, $name, $details) {
        $stmt = $this->connection->prepare("UPDATE users SET name = ?, details = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $details, $userId);
        return $stmt->execute();
    }

    public function getGuestUser() {
        $result = $this->connection->query("SELECT * FROM users ORDER BY id ASC LIMIT 1");
        $user = $result->fetch_assoc();

        if (!$user) {
            $this->connection->query("INSERT INTO users (name, email, password, details) VALUES ('Guest User', 'guest@example.com', 'password', 'Default guest profile')");
            return $this->getGuestUser();
        }
        return $user;
    }

    public function registerUser($name, $email, $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->connection->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed);
        if ($stmt->execute()) {
            return $this->connection->insert_id;
        }
        return false;
    }

    public function loginUser($email, $password) {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function getUserById($id) {
        $stmt = $this->connection->prepare("SELECT id, name, email, details, profile_photo, cv_path FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateUserInfo($id, $data) {
        $fields = [];
        $types = "";
        $values = [];

        if (isset($data['name'])) { $fields[] = "name = ?"; $types .= "s"; $values[] = $data['name']; }
        if (isset($data['details'])) { $fields[] = "details = ?"; $types .= "s"; $values[] = $data['details']; }
        if (isset($data['profile_photo'])) { $fields[] = "profile_photo = ?"; $types .= "s"; $values[] = $data['profile_photo']; }
        if (isset($data['cv_path'])) { $fields[] = "cv_path = ?"; $types .= "s"; $values[] = $data['cv_path']; }

        if (empty($fields)) return true;

        $values[] = $id;
        $types .= "i";
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }


    /**
     * save job logic
     */

    public function saveJobForUser($userId, $jobId) {
        $stmt = $this->connection->prepare("INSERT IGNORE INTO saved_jobs (user_id, job_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $jobId);
        return $stmt->execute();
    }

    public function unsaveJob($userId, $jobId) {
        $stmt = $this->connection->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $stmt->bind_param("ii", $userId, $jobId);
        return $stmt->execute();
    }

    public function getSavedJobs($userId) {
        $stmt = $this->connection->prepare("
            SELECT j.*, c.name as category_name FROM jobs j 
            LEFT JOIN categories c ON j.category_id = c.id
            JOIN saved_jobs sj ON j.id = sj.job_id 
            WHERE sj.user_id = ?
            ORDER BY j.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function isJobSaved($userId, $jobId) {
        $stmt = $this->connection->prepare("SELECT 1 FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $stmt->bind_param("ii", $userId, $jobId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function getCategories() {
        $result = $this->connection->query("SELECT * FROM categories ORDER BY name ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
// Usage: $db = new JobsDatabase();
?>