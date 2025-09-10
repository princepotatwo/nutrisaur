<?php
/**
 * Cleanup Duplicate FCM Tokens Script
 * This script removes duplicate FCM tokens, keeping only the latest one per user
 */

require_once __DIR__ . '/public/api/DatabaseAPI.php';

try {
    $db = DatabaseAPI::getInstance();
    $pdo = $db->getPDO();
    
    echo "🧹 Starting cleanup of duplicate FCM tokens...\n\n";
    
    // First, let's see how many duplicate tokens we have
    $checkStmt = $pdo->prepare("
        SELECT email, COUNT(*) as token_count 
        FROM community_users 
        WHERE fcm_token IS NOT NULL AND fcm_token != ''
        GROUP BY email 
        HAVING COUNT(*) > 1
    ");
    $checkStmt->execute();
    $duplicates = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Found " . count($duplicates) . " users with duplicate FCM tokens:\n";
    foreach ($duplicates as $dup) {
        echo "   - {$dup['email']}: {$dup['token_count']} tokens\n";
    }
    echo "\n";
    
    if (count($duplicates) > 0) {
        // Clean up duplicates by keeping only the latest token per user
        $cleanupStmt = $pdo->prepare("
            DELETE c1 FROM community_users c1
            INNER JOIN community_users c2 
            WHERE c1.email = c2.email 
            AND c1.fcm_token IS NOT NULL 
            AND c1.fcm_token != ''
            AND c2.fcm_token IS NOT NULL 
            AND c2.fcm_token != ''
            AND (c1.updated_at < c2.updated_at OR (c1.updated_at = c2.updated_at AND c1.community_user_id < c2.community_user_id))
        ");
        
        $result = $cleanupStmt->execute();
        $deletedCount = $cleanupStmt->rowCount();
        
        echo "✅ Cleanup completed!\n";
        echo "🗑️  Removed $deletedCount duplicate FCM tokens\n\n";
        
        // Verify the cleanup
        $verifyStmt = $pdo->prepare("
            SELECT email, COUNT(*) as token_count 
            FROM community_users 
            WHERE fcm_token IS NOT NULL AND fcm_token != ''
            GROUP BY email 
            HAVING COUNT(*) > 1
        ");
        $verifyStmt->execute();
        $remainingDuplicates = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($remainingDuplicates) === 0) {
            echo "✅ Verification successful: No more duplicate FCM tokens!\n";
        } else {
            echo "⚠️  Warning: Still found " . count($remainingDuplicates) . " users with duplicates\n";
        }
        
        // Show final token count
        $finalStmt = $pdo->prepare("
            SELECT COUNT(*) as total_tokens 
            FROM community_users 
            WHERE fcm_token IS NOT NULL AND fcm_token != ''
        ");
        $finalStmt->execute();
        $totalTokens = $finalStmt->fetch(PDO::FETCH_ASSOC)['total_tokens'];
        
        echo "📱 Total active FCM tokens: $totalTokens\n";
        
    } else {
        echo "✅ No duplicate FCM tokens found. Database is clean!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
}
?>
