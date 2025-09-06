<?php
/**
 * Super Easy Database Interface
 * Makes database operations as simple as possible for any PHP file
 * Just include this file and start using the functions!
 */

require_once __DIR__ . '/DatabaseHelper.php';

// Get global database instance
$GLOBALS['easyDB'] = DatabaseHelper::getInstance();

/**
 * Ultra-simple database functions - No setup needed!
 * Just include this file and use these functions anywhere
 */

// SELECT operations
function getAll($table, $where = '', $params = []) {
    $result = $GLOBALS['easyDB']->select($table, '*', $where, $params);
    return $result['success'] ? $result['data'] : [];
}

function getOne($table, $where = '', $params = []) {
    $result = $GLOBALS['easyDB']->getFirst($table, $where, $params);
    return $result ?: null;
}

function getCount($table, $where = '', $params = []) {
    return $GLOBALS['easyDB']->count($table, $where, $params);
}

// INSERT operations
function addRecord($table, $data) {
    $result = $GLOBALS['easyDB']->insert($table, $data);
    return $result['success'] ? $result['insert_id'] : false;
}

// UPDATE operations
function updateRecord($table, $data, $where, $params = []) {
    $result = $GLOBALS['easyDB']->update($table, $data, $where, $params);
    return $result['success'];
}

// DELETE operations
function deleteRecord($table, $where, $params = []) {
    $result = $GLOBALS['easyDB']->delete($table, $where, $params);
    return $result['success'];
}

// Custom queries
function runQuery($sql, $params = []) {
    $result = $GLOBALS['easyDB']->query($sql, $params);
    return $result['success'] ? $result['data'] : [];
}

// Utility functions
function tableExists($table) {
    $tables = $GLOBALS['easyDB']->listTables();
    return $tables['success'] && in_array($table, $tables['tables']);
}

function recordExists($table, $where, $params = []) {
    return $GLOBALS['easyDB']->exists($table, $where, $params);
}

function isDBConnected() {
    return $GLOBALS['easyDB']->isAvailable();
}

/**
 * Example usage in any PHP file:
 * 
 * require_once __DIR__ . '/api/EasyDB.php';
 * 
 * // Get all users
 * $users = getAll('users');
 * 
 * // Get one user by email
 * $user = getOne('users', 'email = ?', ['john@example.com']);
 * 
 * // Add new user
 * $newUserId = addRecord('users', [
 *     'username' => 'john',
 *     'email' => 'john@example.com',
 *     'password' => password_hash('password', PASSWORD_DEFAULT)
 * ]);
 * 
 * // Update user
 * updateRecord('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
 * 
 * // Delete user
 * deleteRecord('users', 'id = ?', [$userId]);
 * 
 * // Custom query
 * $results = runQuery('SELECT * FROM users WHERE created_at > ?', ['2024-01-01']);
 * 
 * // Check if connected
 * if (!isDBConnected()) {
 *     echo "Database not available";
 * }
 */

// Auto-test connection when file is included
if (!isDBConnected()) {
    error_log("EasyDB: Database connection not available when loading EasyDB.php");
}

?>
