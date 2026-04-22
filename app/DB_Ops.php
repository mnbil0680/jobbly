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
    public function getAllJobs($search = '') {
        // TODO: Implement SELECT query with search filter
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

        if (!empty($category_id)) {
            $query .= " AND j.category_id = ?";
            $params[] = $category_id;
            $types .= "i";
        }

        $query .= " ORDER BY j.created_at DESC";

        $stmt = $this->connection->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
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
    public function cacheApiJob($jobData) {
        $poster_id = $jobData['source_api'] . "_" . $jobData['external_id'];
        $stmt = $this->connection->prepare("SELECT id FROM jobs WHERE poster_id = ?");
        $stmt->bind_param("s", $poster_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) return $existing['id'];

        $stmt = $this->connection->prepare(
            "INSERT INTO jobs (company_name, poster_id, category_id, title, description, location, job_type, salary_min, salary_max, currency, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $status = 'open';
        $stmt->bind_param(
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
        $stmt->execute();
        return $this->connection->insert_id;
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
        $result = $this->connection->query("SELECT * FROM users LIMIT 1");
        $user = $result->fetch_assoc();
        
        if (!$user) {
            $this->connection->query("INSERT INTO users (name, details) VALUES ('Guest User', 'Default guest profile')");
            return $this->getGuestUser();
        }
        return $user;
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
            SELECT j.* FROM jobs j 
            JOIN saved_jobs sj ON j.id = sj.job_id 
            WHERE sj.user_id = ?
            ORDER BY j.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategories() {
        $result = $this->connection->query("SELECT * FROM categories ORDER BY name ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
// Usage: $db = new JobsDatabase();
?>
