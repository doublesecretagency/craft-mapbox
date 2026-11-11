// Import Vue components
import { createApp } from 'vue';
import { createPinia } from 'pinia';

import AddressField from '../vue/address/address';

// Initialize the number of attempts to load the Mapbox libraries
let librariesAttempts = 0;

// Whether a retry is already waiting
let retryPending = false;

// Initialize Vue instances
window.initAddressField = () => {

    // If configs aren't loaded yet, bail
    if ('undefined' === typeof addressFieldConfigs) {
        return;
    }

    // If the Mapbox libraries haven't loaded yet
    if (!window.mapboxgl || !window.mapboxsearchcore) {

        // If a retry is already waiting, bail
        if (retryPending) {
            return;
        }

        // If out of time
        if (librariesAttempts++ >= 100) {
            // Log warning
            console.warn('[MB] Unable to load the Mapbox libraries.');
            // Bail
            return;
        }

        // Mark a retry as waiting
        retryPending = true;

        // Try again shortly
        setTimeout(() => {
            // Clear the waiting retry
            retryPending = false;
            // Initialize any fields which are ready
            window.initAddressField();
        }, 100);

        // Bail for now
        return;
    }

    // Loop through all Address field configurations
    for (let i in addressFieldConfigs) {

        // Get configuration of a single Address field
        let config = addressFieldConfigs[i];

        // Get DOM element
        let element = document.getElementById(config.namespace.id);

        // If element does not exist, skip this one
        if (!element) {
            console.warn(`[MB] The following Address field cannot be found: ${config.namespace.id}`);
            continue;
        }

        // If this field is already running, skip it
        if (element.dataset.mbMounted) {
            continue;
        }

        // Initialize Vue instance for a single Address field
        const app = createApp(AddressField, config);

        // Initialize Pinia
        app.use(createPinia());

        // Mount to DOM
        app.mount(element);

        // Mark this field as running
        element.dataset.mbMounted = 'true';

    }

}
