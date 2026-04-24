<?php
/**
 * Database Audit and Cleanup Script
 * Responsibility:
 * - Identify and remove low-quality data (Unknowns)
 * - Sanitize HTML descriptions
 * - Normalize character encoding
 */

require_once __DIR__ . '/DB_Ops.php';

class DataAuditor {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new JobsDatabase();
        // Accessing the private connection might require a tweak to DB_Ops.php 
        // or we can just use the public methods if they exist.
        // For now, I'll assume I can add a getter or use a reflection if needed, 
        // but let's try to work with what we have.
        
        // Since I need to run custom SQL for auditing, I'll add a temporary 
        // method to JobsDatabase or just create a new connection here.
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->conn->set_charset("utf8mb4");
    }

    public function runAudit() {
        echo "Starting Database Audit...\n";

        // 1. Remove "Unknown" and "Untitled" jobs
        $this->removeLowQualityJobs();

        // 2. Remove jobs with empty descriptions
        $this->removeEmptyDescriptions();

        // 3. Sanitize HTML in descriptions
        $this->sanitizeDescriptions();

        echo "Audit Complete.\n";
    }

    private function removeLowQualityJobs() {
        $sql = "DELETE FROM jobs WHERE 
                title = 'Unknown' OR 
                title = 'Untitled Job' OR 
                company_name = 'Unknown' OR 
                location = 'Unknown'";
        
        $this->conn->query($sql);
        echo "Removed " . $this->conn->affected_rows . " low-quality jobs.\n";
    }

    private function removeEmptyDescriptions() {
        $sql = "DELETE FROM jobs WHERE description IS NULL OR TRIM(description) = ''";
        $this->conn->query($sql);
        echo "Removed " . $this->conn->affected_rows . " jobs with empty descriptions.\n";
    }

    private function sanitizeDescriptions() {
        $result = $this->conn->query("SELECT id, description FROM jobs WHERE description LIKE '%<% %'");
        $count = 0;
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $clean = $this->cleanHtml($row['description']);
                if ($clean !== $row['description']) {
                    $stmt = $this->conn->prepare("UPDATE jobs SET description = ? WHERE id = ?");
                    $stmt->bind_param("si", $clean, $row['id']);
                    $stmt->execute();
                    $count++;
                }
            }
        }
        echo "Sanitized HTML in $count descriptions.\n";
    }

    private function cleanHtml($html) {
        if (empty($html)) return '';

        // Strip scripts and styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        // Strip tracking pixels (common in remotive/jobicy)
        $html = preg_replace('/<img[^>]+src=[^>]*blank\.gif[^>]*>/i', '', $html);
        $html = preg_replace('/<img[^>]+src=[^>]*track[^>]*>/i', '', $html);

        // Remove inline styles but keep structure
        $html = preg_replace('/style="[^"]*"/', '', $html);
        $html = preg_replace('/class="[^"]*"/', '', $html);

        // Remove empty paragraphs/divs
        $html = preg_replace('/<(p|div|span)>\s*(&nbsp;)?\s*<\/\1>/i', '', $html);

        return trim($html);
    }
}

// Run if called from CLI or via direct request (with caution)
if (php_sapi_name() === 'cli' || isset($_GET['run_audit'])) {
    $auditor = new DataAuditor();
    $auditor->runAudit();
} else {
    echo "Access denied. Use CLI or ?run_audit=1";
}
