// dashboard_integration.js - Integration script for dash.php
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing event-driven dashboard...');
    
    // Start community event-driven updates
    communityEventDashboard.start();
    
    // Override existing barangay selection function
    const originalUpdateBarangaySelection = window.updateBarangaySelection;
    window.updateBarangaySelection = function(barangay) {
        // Call original function
        if (originalUpdateBarangaySelection) {
            originalUpdateBarangaySelection(barangay);
        }
        
        // Update event dashboard
        communityEventDashboard.updateBarangaySelection(barangay);
    };
    
    // Override existing WHO standard change function
    const originalHandleWHOStandardChange = window.handleWHOStandardChange;
    window.handleWHOStandardChange = function() {
        // Call original function
        if (originalHandleWHOStandardChange) {
            originalHandleWHOStandardChange();
        }
        
        // Publish WHO standard change event
        const whoStandard = document.getElementById('whoStandardSelect')?.value || 'weight-for-age';
        publishEvent('who_standard_changed', {
            who_standard: whoStandard,
            barangay: currentSelectedBarangay || '',
            timestamp: Date.now()
        });
    };
    
    // Function to publish events (for manual triggers)
    window.publishEvent = function(eventType, data) {
        fetch('events_api.php?action=test_event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                event_type: eventType,
                event_data: data
            })
        }).catch(error => {
            console.error('Error publishing event:', error);
        });
    };
    
    console.log('✅ Event-driven dashboard initialized');
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (communityEventDashboard) {
        communityEventDashboard.stop();
    }
});
