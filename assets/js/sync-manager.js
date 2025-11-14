/**
 * Universal Sync Manager
 * 
 * Ensures real-time synchronization across all tabs, windows, browsers
 * Uses multiple strategies:
 * 1. BroadcastChannel API - for same browser tabs/windows
 * 2. localStorage events - for cross-tab communication
 * 3. Smart polling - for different browsers/contexts
 */

(() => {
    'use strict';

    class SyncManager {
        constructor() {
            this.channels = new Map();
            this.listeners = new Map();
            this.pollIntervals = new Map();
            this.lastChecks = new Map();
            this.isVisible = !document.hidden;
            
            // Setup visibility tracking
            this.setupVisibilityTracking();
            
            // Try to use BroadcastChannel if available
            this.hasBroadcastChannel = typeof BroadcastChannel !== 'undefined';
        }

        /**
         * Register a sync channel
         * @param {string} channelName - Name of the channel (e.g., 'tournaments', 'requests')
         * @param {Function} onUpdate - Callback when update detected
         * @param {Object} options - Configuration options
         */
        register(channelName, onUpdate, options = {}) {
            const config = {
                pollInterval: options.pollInterval || 5000, // Poll every 5 seconds
                storageKey: options.storageKey || `sync_${channelName}_update`,
                checkEndpoint: options.checkEndpoint || null,
                ...options
            };

            // Store listener
            this.listeners.set(channelName, { callback: onUpdate, config });
            
            // Setup BroadcastChannel if available
            if (this.hasBroadcastChannel) {
                try {
                    const channel = new BroadcastChannel(channelName);
                    channel.onmessage = (event) => {
                        if (event.data && event.data.type === 'update') {
                            console.log(`[SyncManager] Received update via BroadcastChannel: ${channelName}`);
                            this.handleUpdate(channelName, event.data);
                        }
                    };
                    this.channels.set(channelName, channel);
                } catch (e) {
                    console.warn(`[SyncManager] BroadcastChannel not available for ${channelName}:`, e);
                }
            }
            
            // Setup localStorage listener
            this.setupStorageListener(channelName);
            
            // Setup polling
            this.setupPolling(channelName);
            
            console.log(`[SyncManager] Registered channel: ${channelName}`);
        }

        /**
         * Trigger an update notification
         * @param {string} channelName - Channel to notify
         * @param {Object} data - Additional data
         */
        notify(channelName, data = {}) {
            const listener = this.listeners.get(channelName);
            if (!listener) {
                console.warn(`[SyncManager] No listener for channel: ${channelName}`);
                return;
            }

            const updateData = {
                type: 'update',
                timestamp: Date.now(),
                channel: channelName,
                ...data
            };

            // 1. Broadcast via BroadcastChannel (same browser, different tabs)
            const channel = this.channels.get(channelName);
            if (channel) {
                try {
                    channel.postMessage(updateData);
                    console.log(`[SyncManager] Broadcasted update: ${channelName}`);
                } catch (e) {
                    console.warn(`[SyncManager] BroadcastChannel send failed:`, e);
                }
            }

            // 2. Update localStorage (for cross-tab, older browsers)
            try {
                localStorage.setItem(
                    listener.config.storageKey,
                    JSON.stringify(updateData)
                );
                console.log(`[SyncManager] Updated localStorage: ${channelName}`);
            } catch (e) {
                console.warn(`[SyncManager] localStorage update failed:`, e);
            }

            // 3. Trigger local callback immediately
            this.handleUpdate(channelName, updateData);
        }

        /**
         * Handle update detected from any source
         */
        handleUpdate(channelName, data) {
            const listener = this.listeners.get(channelName);
            if (!listener) return;

            // Check if this is a duplicate update (within 500ms)
            // Prevents multiple rapid-fire updates from different sources
            const lastCheck = this.lastChecks.get(`${channelName}_callback`) || 0;
            if (Date.now() - lastCheck < 500) {
                console.log(`[SyncManager] Skipping duplicate update: ${channelName}`);
                return;
            }

            this.lastChecks.set(`${channelName}_callback`, Date.now());
            
            console.log(`[SyncManager] Processing update: ${channelName}`, data);
            
            // Call the registered callback
            try {
                listener.callback(data);
            } catch (e) {
                console.error(`[SyncManager] Callback error for ${channelName}:`, e);
            }
        }

        /**
         * Setup localStorage event listener
         */
        setupStorageListener(channelName) {
            const listener = this.listeners.get(channelName);
            if (!listener) return;

            const handleStorageEvent = (e) => {
                if (e.key === listener.config.storageKey && e.newValue) {
                    try {
                        const data = JSON.parse(e.newValue);
                        console.log(`[SyncManager] Received update via localStorage: ${channelName}`);
                        this.handleUpdate(channelName, data);
                    } catch (err) {
                        console.warn(`[SyncManager] Failed to parse localStorage data:`, err);
                    }
                }
            };

            window.addEventListener('storage', handleStorageEvent);
        }

        /**
         * Setup smart polling with endpoint check
         */
        setupPolling(channelName) {
            const listener = this.listeners.get(channelName);
            if (!listener || !listener.config.checkEndpoint) return;

            const poll = async () => {
                // Only poll if tab is visible
                if (!this.isVisible) {
                    console.log(`[SyncManager] Skipping poll (tab hidden): ${channelName}`);
                    return;
                }

                try {
                    const response = await fetch(listener.config.checkEndpoint, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });

                    if (!response.ok) {
                        console.warn(`[SyncManager] Poll failed for ${channelName}:`, response.status);
                        return;
                    }

                    const data = await response.json();
                    
                    // Check if data has changed using hash (more reliable than timestamps)
                    if (data.success) {
                        let hasChanges = false;
                        
                        // Primary: Check data hash if available
                        if (data.data_hash) {
                            const lastHash = this.lastChecks.get(`${channelName}_hash`);
                            if (lastHash && lastHash !== data.data_hash) {
                                hasChanges = true;
                                console.log(`[SyncManager] Data hash changed: ${channelName}`);
                            }
                            this.lastChecks.set(`${channelName}_hash`, data.data_hash);
                        }
                        
                        // Secondary: Check timestamp (fallback)
                        if (!hasChanges && data.last_update) {
                            const lastCheck = this.lastChecks.get(`${channelName}_server`) || 0;
                            const serverTimestamp = typeof data.last_update === 'number' 
                                ? data.last_update * 1000 // Unix timestamp in seconds
                                : new Date(data.last_update).getTime();
                            
                            if (serverTimestamp > lastCheck) {
                                hasChanges = true;
                                console.log(`[SyncManager] Timestamp changed: ${channelName}`);
                            }
                            this.lastChecks.set(`${channelName}_server`, serverTimestamp);
                        }
                        
                        // Trigger update if changes detected
                        if (hasChanges) {
                            this.handleUpdate(channelName, {
                                type: 'update',
                                source: 'polling',
                                timestamp: Date.now(),
                                data_hash: data.data_hash
                            });
                        }
                    }
                } catch (e) {
                    console.warn(`[SyncManager] Polling error for ${channelName}:`, e);
                }
            };

            // Initial poll
            poll();

            // Setup interval
            const intervalId = setInterval(poll, listener.config.pollInterval);
            this.pollIntervals.set(channelName, intervalId);

            console.log(`[SyncManager] Polling enabled for ${channelName} (interval: ${listener.config.pollInterval}ms)`);
        }

        /**
         * Setup visibility tracking
         */
        setupVisibilityTracking() {
            document.addEventListener('visibilitychange', () => {
                this.isVisible = !document.hidden;
                
                if (this.isVisible) {
                    console.log('[SyncManager] Tab visible - checking for updates');
                    // Check all channels when tab becomes visible
                    this.checkAllChannels();
                }
            });

            window.addEventListener('focus', () => {
                console.log('[SyncManager] Window focused - checking for updates');
                this.checkAllChannels();
            });
        }

        /**
         * Check all registered channels for updates
         */
        checkAllChannels() {
            this.listeners.forEach((listener, channelName) => {
                if (listener.config.checkEndpoint) {
                    // Trigger immediate poll
                    this.handleUpdate(channelName, {
                        type: 'update',
                        source: 'focus',
                        timestamp: Date.now()
                    });
                }
            });
        }

        /**
         * Unregister a channel
         */
        unregister(channelName) {
            // Close BroadcastChannel
            const channel = this.channels.get(channelName);
            if (channel) {
                channel.close();
                this.channels.delete(channelName);
            }

            // Clear polling interval
            const intervalId = this.pollIntervals.get(channelName);
            if (intervalId) {
                clearInterval(intervalId);
                this.pollIntervals.delete(channelName);
            }

            // Remove listener
            this.listeners.delete(channelName);
            this.lastChecks.delete(channelName);
            this.lastChecks.delete(`${channelName}_server`);

            console.log(`[SyncManager] Unregistered channel: ${channelName}`);
        }

        /**
         * Cleanup all resources
         */
        destroy() {
            this.listeners.forEach((_, channelName) => {
                this.unregister(channelName);
            });
        }
    }

    // Create global instance
    window.SyncManager = new SyncManager();
    console.log('[SyncManager] Initialized');
})();

