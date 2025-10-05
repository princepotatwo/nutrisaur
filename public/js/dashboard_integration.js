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
        // Get WHO standard and barangay values
        const whoStandard = document.getElementById('whoStandardSelect')?.value || 'weight-for-age';
        const barangay = currentSelectedBarangay || '';
        
        console.log('🔄 WHO standard changed to:', whoStandard);
        
        // Update the event dashboard's current WHO standard
        communityEventDashboard.currentWHOStandard = whoStandard;
        
        // Call original function
        if (originalHandleWHOStandardChange) {
            originalHandleWHOStandardChange();
        }
        
        // Trigger WHO standard change handler directly
        communityEventDashboard.handleWHOStandardChanged({
            who_standard: whoStandard,
            barangay: barangay,
            timestamp: Date.now()
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
