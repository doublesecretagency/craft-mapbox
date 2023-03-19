// Wait for document to finish loading
document.addEventListener('DOMContentLoaded', () => {

    // Mapbox plugin JS object
    window.mapbox = window.mapbox || {

        // Log progress to console (enabled in devMode)
        log: (window._mbData ? window._mbData.logging : false),

        // Initialize collection of maps
        _maps: {},

        // ========================================================================= //

        // Create a new map object
        map: function (locations, options) {

            // Log status
            if (this.log) {
                console.log(`============================================================`);
                console.log(`Creating a new map object`);
            }

            // Create a new map object
            const map = new DynamicMap(locations, options);

            // Store map object for future reference
            this._maps[map.id] = map;

            // Return the map object
            return map;
        },

        // Get a specified map object
        getMap: function(mapId, assumeSuccess) {

            // Log status (if success is not assumed)
            if (this.log && !assumeSuccess) {
                console.log(`============================================================`);
                console.log(`[${mapId}] Getting existing map`);
            }

            // Get existing map
            const map = this._maps[mapId];

            // If map does not exist, emit warning
            if (!map) {
                console.warn(`[MB] Unable to find map "${mapId}"`);
            }

            // Return map
            return map;
        },

        // ========================================================================= //

        // Initialize specified maps
        init: function(mapId, callback) {

            // Get selected map containers
            const containers = this._whichMaps(mapId);

            // Loop through containers
            for (let i in containers) {

                // Get each map
                let map = containers[i];

                // If map doesn't exist, skip it
                if (!map) {
                    console.warn(`[MB] Cannot find specified map container #${mapId}`);
                    continue;
                }

                // Count the number of matching containers (should ideally be 1)
                let matchingContainers = document.querySelectorAll(`#${map.id}`).length;

                // If no matching containers exist, skip it
                if (!matchingContainers) {
                    console.warn(`[MB] No DOM element exists using the identifier #${map.id}`);
                    continue;
                }

                // If multiple matching containers exist, skip it
                if (1 < matchingContainers) {
                    console.warn(`[MB] Multiple DOM elements are using the identifier #${map.id}`);
                    continue;
                }

                // Log status
                if (this.log) {
                    console.log(`============================================================`);
                    console.log(`[${map.id}] Initializing map`);
                }

                // Get DNA of each map
                let dna = map.dataset.dna;

                // If no DNA exists, skip it
                if (!dna) {
                    console.warn(`[MB] Map container #${map.id} is missing DNA`);
                    continue;
                }

                // Render each map
                this._unpack(dna);

            }

            // If map callback was specified and is a function
            if (callback && 'function' === typeof callback) {

                // Log status
                if (this.log) {
                    console.log(`[${map.id}] Running map callback function:\n`,callback);
                }

                // Execute map callback
                callback();

            }

        },

        // ========================================================================= //

        // Determine which maps to compile
        _whichMaps: function(selection) {

            // No map containers by default
            let containers = [];

            // Switch according to how map IDs were specified
            switch (typeof selection) {

                // Individual map
                case 'string':
                    containers = [document.getElementById(selection)];
                    break;

                // Selection of maps
                case 'object':

                    // Add each map container to collection
                    for (let i in selection) {
                        let c = document.getElementById(selection[i]);
                        containers.push(c);
                    }
                    break;

                // All maps
                case 'undefined':
                    const allMaps = document.getElementsByClassName('mb-map');
                    containers = Array.prototype.slice.call(allMaps);
                    break;

                // Something went wrong
                default:
                    containers = [];
                    break;

            }

            // Return collection
            return containers;
        },

        // Unpack and initialize map DNA
        _unpack: function(dna) {

            // Unpack the DNA sequence
            const sequence = JSON.parse(dna);

            // If no DNA exists, error and bail
            if (!sequence) {
                console.warn('[MB] No map DNA provided.');
                return;
            }

            // Initialize
            let map;

            // Loop through DNA sequence
            for (let i = 0; i < sequence.length; i++) {

                // Get map DNA block
                let block = sequence[i];

                // If first block is not a map, error and bail
                if (0 === i && 'map' !== block.type) {
                    console.warn('[MB] Map DNA is misconfigured.');
                    return;
                }

                // Switch according to DNA block type
                switch (block.type) {

                    // Create a new map
                    case 'map':
                        map = new DynamicMap(block.locations, block.options);
                        break;

                    // Add markers to the map
                    case 'markers':
                        map.markers(block.locations, block.options);
                        break;

                    // Style the map
                    case 'style':
                        map.style(block.mapStyle);
                        break;

                    // Zoom the map
                    case 'zoom':
                        map.zoom(block.level);
                        break;

                    // Center the map
                    case 'center':
                        map.center(block.coords);
                        break;

                    // Fit the map bounds
                    case 'fit':
                        map.fit(block.options);
                        break;

                    // Pan to a specific marker
                    case 'panToMarker':
                        map.panToMarker(block.markerId);
                        break;

                    // Set new options for an existing marker
                    case 'changeMarker':
                        map.changeMarker(block.markerId, block.options);
                        break;

                    // Hide a marker
                    case 'hideMarker':
                        map.hideMarker(block.markerId);
                        break;

                    // Show a marker
                    case 'showMarker':
                        map.showMarker(block.markerId);
                        break;

                    // Open the popup of a specific marker
                    case 'openPopup':
                        map.openPopup(block.markerId);
                        break;

                    // Close the popup of a specific marker
                    case 'closePopup':
                        map.closePopup(block.markerId);
                        break;

                }

            }

            // Finish initializing map
            map.tag();

            // Store map object for future reference
            this._maps[map.id] = map;

            // Return the map object
            return map;
        },

    };

});
