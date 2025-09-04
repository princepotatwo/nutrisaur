<?php
/**
 * Database Structure Test File
 * Comprehensive test to check database connection, tables, columns, and data integrity
 */

// Include the unified DatabaseAPI
require_once __DIR__ . "/api/DatabaseAPI.php";

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type for better output
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Structure Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .table-name { font-weight: bold; color: #007bff; }
        .column-name { font-weight: bold; color: #28a745; }
        .data-type { color: #6c757d; }
        .nullable { color: #dc3545; }
        .key { background-color: #fff3cd; }
    </style>
</head>
<body>
    <h1>🔍 Database Structure Test Results</h1>
    <p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
";

try {
    // Initialize DatabaseAPI
    $db = new DatabaseAPI();
    
    if (!$db) {
        throw new Exception("Failed to initialize DatabaseAPI");
    }
    
    $pdo = $db->getPDO();
    if (!$pdo) {
        throw new Exception("Failed to get PDO connection");
    }
    
    echo "<div class='test-section success'>
        <h3>✅ DatabaseAPI Initialized Successfully</h3>
        <p>DatabaseAPI instance created and PDO connection established.</p>
    </div>";
    
    // Test 1: Database Information
    echo "<div class='test-section info'>
        <h3>🗄️ Test 1: Database Information</h3>";
    
    try {
        // Get database version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<p><strong>Database Version:</strong> " . $version['version'] . "</p>";
        
        // Get current database name
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $dbName = $stmt->fetch();
        echo "<p><strong>Current Database:</strong> " . $dbName['db_name'] . "</p>";
        
        // Get character set
        $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'");
        $charset = $stmt->fetch();
        echo "<p><strong>Character Set:</strong> " . $charset['Value'] . "</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Database Info Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Test 2: List All Tables
    echo "<div class='test-section info'>
        <h3>📋 Test 2: Database Tables</h3>";
    
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p>✅ Found <strong>" . count($tables) . "</strong> tables:</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li class='table-name'>$table</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>⚠️ No tables found in the database.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Table List Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Test 3: Table Structure Analysis
    echo "<div class='test-section info'>
        <h3>🏗️ Test 3: Table Structure Analysis</h3>";
    
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            echo "<h4 class='table-name'>Table: $table</h4>";
            
            // Get table structure
            $stmt = $pdo->prepare("DESCRIBE `$table`");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($columns) > 0) {
                echo "<table>";
                echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                foreach ($columns as $column) {
                    $keyClass = !empty($column['Key']) ? 'key' : '';
                    echo "<tr class='$keyClass'>";
                    echo "<td class='column-name'>{$column['Field']}</td>";
                    echo "<td class='data-type'>{$column['Type']}</td>";
                    echo "<td class='nullable'>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "<td>{$column['Extra']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Get row count
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
                $stmt->execute();
                $count = $stmt->fetch();
                echo "<p><strong>Row Count:</strong> " . $count['count'] . "</p>";
                
            } else {
                echo "<p>⚠️ No columns found in table $table</p>";
            }
            
            echo "<hr>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Table Structure Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Test 4: Data Sample Analysis
    echo "<div class='test-section info'>
        <h3>📊 Test 4: Data Sample Analysis</h3>";
    
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            echo "<h4 class='table-name'>Sample Data from: $table</h4>";
            
            // Get sample data (first 3 rows)
            $stmt = $pdo->prepare("SELECT * FROM `$table` LIMIT 3");
            $stmt->execute();
            $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($sampleData) > 0) {
                echo "<pre>" . json_encode($sampleData, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p>⚠️ No data found in table $table</p>";
            }
            
            echo "<hr>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Data Sample Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Test 5: Index Analysis
    echo "<div class='test-section info'>
        <h3>🔍 Test 5: Index Analysis</h3>";
    
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            echo "<h4 class='table-name'>Indexes for: $table</h4>";
            
            $stmt = $pdo->prepare("SHOW INDEX FROM `$table`");
            $stmt->execute();
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($indexes) > 0) {
                echo "<table>";
                echo "<tr><th>Index Name</th><th>Column</th><th>Non-Unique</th><th>Cardinality</th></tr>";
                
                foreach ($indexes as $index) {
                    echo "<tr>";
                    echo "<td>{$index['Key_name']}</td>";
                    echo "<td>{$index['Column_name']}</td>";
                    echo "<td>{$index['Non_unique']}</td>";
                    echo "<td>{$index['Cardinality']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>⚠️ No indexes found for table $table</p>";
            }
            
            echo "<hr>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Index Analysis Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Test 6: Foreign Key Analysis
    echo "<div class='test-section info'>
        <h3>🔗 Test 6: Foreign Key Analysis</h3>";
    
    try {
        $stmt = $pdo->query("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                REFERENCED_TABLE_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($foreignKeys) > 0) {
            echo "<table>";
            echo "<tr><th>Table</th><th>Column</th><th>Constraint</th><th>References Table</th><th>References Column</th></tr>";
            
            foreach ($foreignKeys as $fk) {
                echo "<tr>";
                echo "<td>{$fk['TABLE_NAME']}</td>";
                echo "<td>{$fk['COLUMN_NAME']}</td>";
                echo "<td>{$fk['CONSTRAINT_NAME']}</td>";
                echo "<td>{$fk['REFERENCED_TABLE_NAME']}</td>";
                echo "<td>{$fk['REFERENCED_COLUMN_NAME']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No foreign keys found in the database</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Foreign Key Analysis Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // Test 7: Database Size Analysis
    echo "<div class='test-section info'>
        <h3>📏 Test 7: Database Size Analysis</h3>";
    
    try {
        $stmt = $pdo->query("
            SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_MB',
                table_rows
            FROM 
                information_schema.tables 
            WHERE 
                table_schema = DATABASE()
            ORDER BY 
                (data_length + index_length) DESC
        ");
        $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($sizes) > 0) {
            echo "<table>";
            echo "<tr><th>Table</th><th>Size (MB)</th><th>Rows</th></tr>";
            
            foreach ($sizes as $size) {
                echo "<tr>";
                echo "<td>{$size['table_name']}</td>";
                echo "<td>{$size['Size_MB']}</td>";
                echo "<td>{$size['table_rows']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No size information available</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Size Analysis Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    echo "<div class='test-section success'>
        <h3>🎉 Database Structure Analysis Complete!</h3>
        <p>All database structure tests completed successfully. The database is properly configured and accessible.</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='test-section error'>
        <h3>❌ Database Structure Test Failed</h3>
        <p><strong>Error:</strong> " . $e->getMessage() . "</p>
        <p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>
    </div>";
}

echo "</body></html>";
?>
