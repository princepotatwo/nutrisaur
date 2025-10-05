// CommunityEventDashboardManager.js - Event-driven dashboard manager
class CommunityEventDashboardManager {
    constructor() {
        this.eventSource = null;
        this.isConnected = false;
        this.currentBarangay = '';
        this.currentWHOStandard = 'weight-for-age';
        this.updateInProgress = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }
    
    start() {
        this.connect();
    }
    
    connect() {
        const barangay = currentSelectedBarangay || '';
        const url = `events_api.php?action=event_stream&barangay=${barangay}`;
        
        console.log('🔄 Connecting to event stream:', url);
        
        this.eventSource = new EventSource(url);
        
        this.eventSource.onopen = () => {
            console.log('✅ Community event stream connected');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.showConnectionStatus('Connected');
        };
        
        this.eventSource.onmessage = (event) => {
            try {
                const eventData = JSON.parse(event.data);
                this.handleCommunityEvent(eventData);
            } catch (error) {
                console.error('Error parsing community event:', error);
            }
        };
        
        this.eventSource.onerror = () => {
            console.log('❌ Community event stream disconnected');
            this.isConnected = false;
            this.showConnectionStatus('Disconnected');
            this.reconnect();
        };
    }
    
    async handleCommunityEvent(eventData) {
        if (this.updateInProgress) {
            console.log('⏳ Update already in progress, skipping event');
            return;
        }
        
        this.updateInProgress = true;
        this.showUpdateIndicator();
        
        try {
            console.log('🔄 Handling community event:', eventData.type);
            
            switch (eventData.type) {
                case 'screening_data_saved':
                case 'screening_data_updated':
                    await this.handleScreeningDataSaved(eventData.data);
                    break;
                case 'new_user_registered':
                    await this.handleNewUserRegistered(eventData.data);
                    break;
                case 'user_profile_updated':
                    await this.handleUserProfileUpdated(eventData.data);
                    break;
                case 'barangay_data_changed':
                    await this.handleBarangayDataChanged(eventData.data);
                    break;
                case 'demographic_data_changed':
                    await this.handleDemographicDataChanged(eventData.data);
                    break;
                case 'physical_data_updated':
                    await this.handlePhysicalDataUpdated(eventData.data);
                    break;
                case 'who_standard_changed':
                    await this.handleWHOStandardChanged(eventData.data);
                    break;
                case 'connected':
                case 'heartbeat':
                    // These are connection events, no action needed
                    break;
                default:
                    console.log('Unknown community event type:', eventData.type);
            }
        } catch (error) {
            console.error('Error handling community event:', error);
        } finally {
            this.updateInProgress = false;
            this.hideUpdateIndicator();
        }
    }
    
    async handleScreeningDataSaved(data) {
        console.log('🔄 New screening data saved:', data);
        
        // Update components that depend on screening data
        await Promise.all([
            this.updateCommunityMetrics(data.barangay),
            this.updateWHOClassifications(data.barangay),
            this.updateTrendsChart(),
            this.updateSevereCasesList(data.barangay),
            this.updateGeographicDistribution(data.barangay)
        ]);
    }
    
    async handleNewUserRegistered(data) {
        console.log('🔄 New user registered:', data);
        
        // Update components that depend on user count
        await Promise.all([
            this.updateCommunityMetrics(data.barangay),
            this.updateGeographicDistribution(data.barangay),
            this.updateBarangayDistribution(data.barangay),
            this.updateGenderDistribution(data.barangay),
            this.updateTrendsChart()
        ]);
    }
    
    async handleUserProfileUpdated(data) {
        console.log('🔄 User profile updated:', data);
        
        // Update user-specific components
        await Promise.all([
            this.updateCommunityMetrics(data.barangay),
            this.updateGeographicDistribution(data.barangay),
            this.updateBarangayDistribution(data.barangay),
            this.updateGenderDistribution(data.barangay)
        ]);
    }
    
    async handleBarangayDataChanged(data) {
        console.log('🔄 Barangay data changed:', data);
        
        // Update geographic components
        await Promise.all([
            this.updateGeographicDistribution(data.barangay),
            this.updateBarangayDistribution(data.barangay),
            this.updateGenderDistribution(data.barangay)
        ]);
    }
    
    async handleDemographicDataChanged(data) {
        console.log('🔄 Demographic data changed:', data);
        
        // Update demographic-related components
        await Promise.all([
            this.updateGenderDistribution(data.barangay),
            this.updateAgeClassificationChart(data.barangay),
            this.updateCommunityMetrics(data.barangay)
        ]);
    }
    
    async handlePhysicalDataUpdated(data) {
        console.log('🔄 Physical data updated:', data);
        
        // Update components that depend on weight/height data
        await Promise.all([
            this.updateWHOClassifications(data.barangay),
            this.updateAgeClassificationChart(data.barangay),
            this.updateSevereCasesList(data.barangay),
            this.updateTrendsChart()
        ]);
    }
    
    // Wrapper functions for your existing update functions
    async updateCommunityMetrics(barangay) {
        if (typeof updateCommunityMetrics === 'function') {
            await updateCommunityMetrics(barangay);
        }
    }
    
    async updateWHOClassifications(barangay) {
        if (typeof updateWHOClassificationChart === 'function') {
            await updateWHOClassificationChart(barangay);
        }
    }
    
    async updateTrendsChart() {
        if (typeof updateTrendsChart === 'function') {
            await updateTrendsChart();
        }
    }
    
    async updateSevereCasesList(barangay) {
        if (typeof updateSevereCasesList === 'function') {
            await updateSevereCasesList(barangay);
        }
    }
    
    async updateGeographicDistribution(barangay) {
        if (typeof updateGeographicChart === 'function') {
            await updateGeographicChart(barangay);
        }
    }
    
    async updateBarangayDistribution(barangay) {
        if (typeof updateBarangayDistributionDisplay === 'function') {
            const data = await fetchBarangayDistributionData(barangay);
            updateBarangayDistributionDisplay(data);
        }
    }
    
    async updateGenderDistribution(barangay) {
        if (typeof updateGenderDistributionDisplay === 'function') {
            const data = await fetchGenderDistributionData(barangay);
            updateGenderDistributionDisplay(data);
        }
    }
    
    async updateAgeClassificationChart(barangay) {
        if (typeof updateAgeClassificationChart === 'function') {
            await updateAgeClassificationChart(barangay);
        }
    }
    
    reconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`🔄 Reconnecting community event stream... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            
            setTimeout(() => {
                this.connect();
            }, 3000 * this.reconnectAttempts);
        } else {
            console.error('❌ Max reconnection attempts reached');
            this.showConnectionStatus('Failed');
        }
    }
    
    showConnectionStatus(status) {
        const indicator = document.getElementById('community-connection');
        if (indicator) {
            indicator.textContent = status;
            indicator.className = `connection-indicator ${status.toLowerCase()}`;
        }
    }
    
    showUpdateIndicator() {
        const indicator = document.getElementById('community-updates');
        if (indicator) {
            indicator.style.display = 'flex';
        }
    }
    
    hideUpdateIndicator() {
        const indicator = document.getElementById('community-updates');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }
    
    stop() {
        if (this.eventSource) {
            this.eventSource.close();
            this.isConnected = false;
        }
    }
    
    // Method to update barangay selection
    updateBarangaySelection(barangay) {
        this.currentBarangay = barangay;
        if (this.isConnected) {
            this.stop();
            this.start();
        }
    }
    
    // Method to handle WHO standard changes directly
    async handleWHOStandardChanged(data) {
        console.log('🔄 WHO standard changed:', data);
        
        // Update current WHO standard
        this.currentWHOStandard = data.who_standard;
        
        // Update all components that depend on WHO standard
        await Promise.all([
            this.updateWHOClassifications(data.barangay),
            this.updateAgeClassificationChart(data.barangay),
            this.updateSevereCasesList(data.barangay),
            this.updateTrendsChart(),
            this.updateCommunityMetrics(data.barangay)
        ]);
    }
}

// Initialize the community event dashboard
const communityEventDashboard = new CommunityEventDashboardManager();
