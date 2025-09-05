<?php
/**
 * Database Helper Class
 * Simplified interface for DatabaseAPI operations
 * Makes it super easy to use database operations from any PHP file
 */

require_once __DIR__ . '/DatabaseAPI.php';

class DatabaseHelper {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = DatabaseAPI::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Select data from any table
     * @param string $table Table name
     * @param string $columns Columns to select (default: *)
     * @param string $where WHERE condition (optional)
     * @param array $params Parameters for WHERE condition
     * @param string $orderBy ORDER BY clause (optional)
     * @param string $limit LIMIT clause (optional)
     * @return array
     */
    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
        return $this->db->universalSelect($table, $columns, $where, $orderBy, $limit, $params);
    }
    
    /**
     * Insert data into any table
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return array
     */
    public function insert($table, $data) {
        return $this->db->universalInsert($table, $data);
    }
    
    /**
     * Update data in any table
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param string $where WHERE condition
     * @param array $whereParams Parameters for WHERE condition
     * @return array
     */
    public function update($table, $data, $where, $whereParams = []) {
        return $this->db->universalUpdate($table, $data, $where, $whereParams);
    }
    
    /**
     * Delete data from any table
     * @param string $table Table name
     * @param string $where WHERE condition
     * @param array $params Parameters for WHERE condition
     * @return array
     */
    public function delete($table, $where, $params = []) {
        return $this->db->universalDelete($table, $where, $params);
    }
    
    /**
     * Execute custom SQL query
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @return array
     */
    public function query($sql, $params = []) {
        return $this->db->universalQuery($sql, $params);
    }
    
    /**
     * Get table structure
     * @param string $table Table name
     * @return array
     */
    public function describe($table) {
        return $this->db->describeTable($table);
    }
    
    /**
     * List all tables
     * @return array
     */
    public function listTables() {
        return $this->db->listTables();
    }
    
    /**
     * Check if database is available
     * @return bool
     */
    public function isAvailable() {
        return $this->db->isDatabaseAvailable();
    }
    
    /**
     * Get first row from a table
     * @param string $table Table name
     * @param string $where WHERE condition (optional)
     * @param array $params Parameters for WHERE condition
     * @return array|null
     */
    public function getFirst($table, $where = '', $params = []) {
        $result = $this->select($table, '*', $where, $params, '', '1');
        if ($result['success'] && !empty($result['data'])) {
            return $result['data'][0];
        }
        return null;
    }
    
    /**
     * Count rows in a table
     * @param string $table Table name
     * @param string $where WHERE condition (optional)
     * @param array $params Parameters for WHERE condition
     * @return int
     */
    public function count($table, $where = '', $params = []) {
        $result = $this->select($table, 'COUNT(*) as count', $where, $params);
        if ($result['success'] && !empty($result['data'])) {
            return (int)$result['data'][0]['count'];
        }
        return 0;
    }
    
    /**
     * Check if a record exists
     * @param string $table Table name
     * @param string $where WHERE condition
     * @param array $params Parameters for WHERE condition
     * @return bool
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
}

/**
 * Global helper functions for even easier access
 */

// Create global instance
$_dbHelper = null;

function db() {
    global $_dbHelper;
    if ($_dbHelper === null) {
        $_dbHelper = DatabaseHelper::getInstance();
    }
    return $_dbHelper;
}

// Quick access functions
function dbSelect($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
    return db()->select($table, $columns, $where, $params, $orderBy, $limit);
}

function dbInsert($table, $data) {
    return db()->insert($table, $data);
}

function dbUpdate($table, $data, $where, $whereParams = []) {
    return db()->update($table, $data, $where, $whereParams);
}

function dbDelete($table, $where, $params = []) {
    return db()->delete($table, $where, $params);
}

function dbQuery($sql, $params = []) {
    return db()->query($sql, $params);
}

function dbFirst($table, $where = '', $params = []) {
    return db()->getFirst($table, $where, $params);
}

function dbCount($table, $where = '', $params = []) {
    return db()->count($table, $where, $params);
}

function dbExists($table, $where, $params = []) {
    return db()->exists($table, $where, $params);
}

?>
