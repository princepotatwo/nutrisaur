// dash_fix_3second.js - Direct fix for dash.php 3-second flicker
// Add this script to your dash.php to disable the 3-second refresh

// 1. DISABLE the 3-second interval immediately
(function() {
    console.log('🛑 Disabling 3-second refresh system...');
    
    // Override the startRealtimeUpdates function to do nothing
    if (typeof startRealtimeUpdates === 'function') {
        window.originalStartRealtimeUpdates = startRealtimeUpdates;
    }
    
    window.startRealtimeUpdates = function() {
        console.log('🛑 3-second refresh disabled - using event-driven system instead');
        // Don't start the old system
        return;
    };
    
    // Override the setInterval call
    const originalSetInterval = window.setInterval;
    window.setInterval = function(callback, delay) {
        // Block the 3-second interval
        if (delay === 3000) {
            console.log('🛑 Blocked 3-second interval - using event-driven updates instead');
            return null;
        }
        return originalSetInterval(callback, delay);
    };
    
    console.log('✅ 3-second refresh system disabled');
})();

// 2. ENABLE event-driven system
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Starting event-driven dashboard system...');
    
    // Load and start the event-driven system
    if (typeof communityEventDashboard !== 'undefined') {
        communityEventDashboard.start();
        console.log('✅ Event-driven system active - no more 3-second polling!');
    } else {
        console.log('⚠️ Event system not loaded yet, will retry...');
        
        // Retry after a short delay
        setTimeout(() => {
            if (typeof communityEventDashboard !== 'undefined') {
                communityEventDashboard.start();
                console.log('✅ Event-driven system started on retry');
            } else {
                console.error('❌ Event system failed to load');
            }
        }, 1000);
    }
});

// 3. ADD fallback polling (much less frequent)
let fallbackInterval = null;
setTimeout(() => {
    // Only use fallback if event system is not working
    if (typeof communityEventDashboard === 'undefined' || !communityEventDashboard.isConnected) {
        console.log('⚠️ Event system not working, enabling fallback polling (30 seconds)');
        
        fallbackInterval = setInterval(async () => {
            if (document.visibilityState === 'visible' && !dashboardState.updateInProgress) {
                console.log('🔄 Fallback polling update...');
                try {
                    await updateDashboardForBarangay(currentSelectedBarangay || '');
                } catch (error) {
                    console.error('Fallback update error:', error);
                }
            }
        }, 30000); // 30 seconds instead of 3
    }
}, 5000);

// 4. CLEANUP function
window.cleanupDashboardRefresh = function() {
    if (fallbackInterval) {
        clearInterval(fallbackInterval);
        fallbackInterval = null;
    }
    if (typeof communityEventDashboard !== 'undefined') {
        communityEventDashboard.stop();
    }
};

// 5. EXPORT for manual control
window.dashboardRefreshControl = {
    disable3Second: function() {
        console.log('🛑 Manually disabling 3-second refresh');
        window.startRealtimeUpdates = function() { return; };
    },
    enableEvents: function() {
        console.log('✅ Manually enabling event system');
        if (typeof communityEventDashboard !== 'undefined') {
            communityEventDashboard.start();
        }
    },
    cleanup: window.cleanupDashboardRefresh
};
