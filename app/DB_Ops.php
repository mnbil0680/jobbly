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
        // TODO: Initialize database connection from config.php
        // $this->connection = new mysqli(...);
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            throw new Exception('Database connection failed: ' . $this->connection->connect_error);
        }
    }

    /**
     * Get all jobs with optional search filter
     */
    public function getAllJobs($search = '') {
        // TODO: Implement SELECT query with search filter
        return [];
    }

    /**
     * Get a single job by ID
     */
    public function getJobById($id) {
        // TODO: Implement SELECT query for single job
        return null;
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

        if($stmt){
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
        return false;
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
        // TODO: Implement sanitization logic
        return $data;
    }
}

// Usage: $db = new JobsDatabase();
?>
