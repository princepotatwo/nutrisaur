// replace_3second_refresh.js - Replace 3-second refresh with event-driven updates

// 1. DISABLE the old 3-second refresh system
function disableOldRefreshSystem() {
    // Stop the existing real-time updates
    if (typeof stopRealtimeUpdates === 'function') {
        stopRealtimeUpdates();
    }
    
    // Clear any existing intervals
    if (typeof realtimeUpdateInterval !== 'undefined' && realtimeUpdateInterval) {
        clearInterval(realtimeUpdateInterval);
        realtimeUpdateInterval = null;
    }
    
    console.log('🛑 Disabled old 3-second refresh system');
}

// 2. ENABLE the new event-driven system
function enableEventDrivenSystem() {
    // Start the community event dashboard
    if (typeof communityEventDashboard !== 'undefined') {
        communityEventDashboard.start();
        console.log('✅ Enabled event-driven dashboard system');
    } else {
        console.error('❌ CommunityEventDashboardManager not loaded');
    }
}

// 3. REPLACE the old startRealtimeUpdates function
function replaceStartRealtimeUpdates() {
    // Override the old function
    window.startRealtimeUpdates = function() {
        console.log('🔄 Old 3-second system disabled - using event-driven system instead');
        enableEventDrivenSystem();
    };
    
    // Override the old stop function
    window.stopRealtimeUpdates = function() {
        console.log('🛑 Stopping event-driven system');
        if (typeof communityEventDashboard !== 'undefined') {
            communityEventDashboard.stop();
        }
    };
}

// 4. INITIALIZE the replacement
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 Replacing 3-second refresh with event-driven system...');
    
    // Disable old system
    disableOldRefreshSystem();
    
    // Replace old functions
    replaceStartRealtimeUpdates();
    
    // Enable new system
    enableEventDrivenSystem();
    
    console.log('✅ Event-driven system active - no more 3-second polling!');
});

// 5. ADD fallback polling (optional - only if events fail)
function addFallbackPolling() {
    // Only use fallback if event system fails
    let fallbackInterval = null;
    let eventSystemFailed = false;
    
    // Check if event system is working
    setTimeout(() => {
        if (!communityEventDashboard || !communityEventDashboard.isConnected) {
            console.log('⚠️ Event system failed, enabling fallback polling (every 30 seconds)');
            eventSystemFailed = true;
            
            fallbackInterval = setInterval(async () => {
                if (document.visibilityState === 'visible') {
                    console.log('🔄 Fallback polling update...');
                    await updateDashboardForBarangay(currentSelectedBarangay || '');
                }
            }, 30000); // 30 seconds fallback (much less frequent)
        }
    }, 10000); // Check after 10 seconds
    
    return fallbackInterval;
}

// 6. EXPORT functions for manual control
window.dashboardRefreshControl = {
    disableOld: disableOldRefreshSystem,
    enableNew: enableEventDrivenSystem,
    addFallback: addFallbackPolling
};
