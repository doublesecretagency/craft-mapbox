// Dynamic Map model for Mapbox plugin
function DynamicMap(locations, options) {

    // Safely omit both parameters
    locations = locations || [];
    options = options || {};

    // Initialize properties
    this.id = null;
    this.div = null;
    this._map = null;

    // Initialize collections
    this._markers = {};
    this._popups = {};

    // Initialize defaults
    this._d = {};

    // Set a comfortable default zoom level
    this._comfortableZoom = 11;

    // ========================================================================= //

    // Create a new map object
    this.__construct = function(locations, options) {

        // Array
        if (Array.isArray(locations)) {
            // If array is empty, nullify locations
            if (!locations.length) {
                locations = null;
            }

        // Object
        } else if ('object' === typeof locations) {
            // Convert locations to an array
            locations = [locations];

        // Anything else
        } else {
            // Nullify locations
            locations = null;

        }

        // Ensure options are valid
        options = options || {};

        // Ensure map options are valid
        const mapOptions = options.mapOptions || {};

        // If no map ID was specified, generate one
        this.id = options.id || this._generateId('map');

        // Get map container
        this.div = document.getElementById(options.id);

        // If container does not exist
        if (!this.div) {
            // Create new container from scratch
            this.div = document.createElement('div');
        }

        // Configure map container
        this.div.id = this.id;
        this.div.classList.add('mb-map');
        this.div.style.display = 'block';

        // Get specified center, or fallback to null
        mapOptions.center = mapOptions.center || options.center || null;

        // If still no center, fallback to [0,0]
        mapOptions.center = mapOptions.center || [0,0];

        // Set defaults (with fallbacks)
        this._d.center        = mapOptions.center;
        this._d.zoom          = options.zoom          || null;
        this._d.markerOptions = options.markerOptions || {};
        this._d.popupOptions  = options.popupOptions  || {};

        // Optionally set container height
        if (options.height) {
            this.div.style.height = `${options.height}px`;
        }

        // Optionally set width of container
        if (options.width) {
            this.div.style.width = `${options.width}px`;
        }

        // Fallback to default zoom & style
        mapOptions.zoom  = mapOptions.zoom  || options.zoom  || null;
        mapOptions.style = mapOptions.style || options.style || 'streets-v12';

        // Normalize the style
        mapOptions.style = this._normalizeStyle(mapOptions.style);

        // Create a new Mapbox object
        this._createMap(mapOptions);

        // If locations were specified
        if (locations) {
            // Add markers (using default markerOptions & popupOptions)
            this.markers(locations);
        }
    };

    // ========================================================================= //

    // Add a set of markers to the map
    this.markers = function(locations, options) {

        // If no locations, bail
        if (!locations) {
            return;
        }

        // Ensure options are valid
        options = options || {};

        // Ensure marker options are valid
        options.markerOptions = options.markerOptions || this._d.markerOptions;

        // Ensure popup options are valid
        options.popupOptions = options.popupOptions || this._d.popupOptions;

        // Force locations to be an array structure
        if (!Array.isArray(locations)) {
            locations = [locations];
        }

        // Loop through all locations
        for (var i in locations) {

            // Get individual coordinates
            var coords = locations[i];

            // If coordinates are not valid, skip
            if (!coords.hasOwnProperty('lat') || !coords.hasOwnProperty('lng')) {
                continue;
            }

            // Get marker ID or generate a random one
            var markerId = options.id || coords.id || this._generateId('marker');

            // Set marker ID back to coordinates object
            coords.id = markerId;

            // Create a new marker
            this._createMarker(coords, options);

        }

        // Keep the party going
        return this;
    };

    // ========================================================================= //

    // Apply a style to the map
    this.style = function(style) {

        // Ensure style is valid
        style = style || null;

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Styling map`, style);
        }

        // Apply new style
        this._map.setStyle(this._normalizeStyle(style));

        // Keep the party going
        return this;
    };

    // Zoom map to specified level
    this.zoom = function(level, assumeSuccess) {

        // Ensure level is valid
        level = level || this._d.zoom;

        // Update default zoom level
        this._d.zoom = level;

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Zooming map to level`, level);
        }

        // Set zoom level of current map
        this._map.setZoom(level);

        // Keep the party going
        return this;
    };

    // Center the map on a set of coordinates
    this.center = function(coords, assumeSuccess) {

        // Ensure coordinates are valid
        coords = coords
            || this._determineBounds().getCenter()
            || this._d.center;

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Centering map on coordinates`, coords);
        }

        // If coordinates do not exist, emit warning and bail
        if (!coords) {
            console.warn(`[MB] Unable to center map, invalid coordinates:`, coords);
            return this;
        }

        // Ensure coordinates are float values
        coords.lng = parseFloat(coords.lng);
        coords.lat = parseFloat(coords.lat);

        // Update default center coordinates
        this._d.center = coords;

        // Patch, must zoom out first!
        // https://github.com/mapbox/mapbox-gl-js/issues/2864#issuecomment-232540691
        this._map.setZoom(1);

        // Re-center current map
        this._map.setCenter(coords);

        // Keep the party going
        return this;
    };

    // Fit map according to bounds
    this.fit = function(options, assumeSuccess) {

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Fitting map to existing boundaries`);
        }

        // Determine the existing map boundaries
        const bounds = this._determineBounds();

        // If no bounds exist, emit warning and bail
        if (!bounds) {
            console.warn(`[MB] Cannot fit the map, unable to determine bounds.`);
            return this;
        }

        // Ensure options exist
        options = options || {};

        // If no padding specified, use fallback
        options.padding = options.padding || {
            'top'    : 70,
            'right'  : 40,
            'bottom' : 40,
            'left'   : 40,
        };

        // Fit bounds of current map
        this._map.fitBounds(bounds, options);

        // Keep the party going
        return this;
    };

    // ========================================================================= //

    // Pan map to center on a specific marker
    this.panToMarker = function(markerId) {

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Panning to marker "${markerId}"`);
        }

        // Get specified marker
        const marker = this.getMarker(markerId, true);

        // If invalid marker, bail
        if (!marker) {
            console.warn(`[MB] Unable to pan to marker "${markerId}"`);
            return this;
        }

        // Pan map to marker position
        this._map.panTo(marker.getLngLat());

        // Keep the party going
        return this;
    };

    // Set new options for an existing marker
    this.changeMarker = function(markerId, options, assumeSuccess) {

        // If setting options for multiple markers
        if (Array.isArray(markerId)) {
            // Log status (if success is not assumed)
            if (mapbox.log && !assumeSuccess) {
                console.log(`[${this.id}] Setting options for multiple markers`);
            }
            // Set options for each marker individually
            for (var i in markerId) {
                this.changeMarker(markerId[i], options);
            }
            // Our work here is done
            return this;
        }

        // If setting options for all markers
        if ('*' === markerId) {
            // Log status (if success is not assumed)
            if (mapbox.log && !assumeSuccess) {
                console.log(`[${this.id}] Setting options for all markers:`, options);
            }
            // Set options for each marker individually
            for (var key in this._markers) {
                this.changeMarker(key, options, true);
            }
            // Our work here is done
            return this;
        }

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Setting options for marker "${markerId}":`, options);
        }

        // Get specified marker
        const oldMarker = this.getMarker(markerId, true);

        // If invalid marker, bail
        if (!oldMarker) {
            console.warn(`[MB] Unable to set options, marker "${markerId}" does not exist.`);
            return this;
        }

        // Re-create marker object
        this._markers[markerId] = new mapboxgl.Marker(options)
            .setLngLat(oldMarker.getLngLat())
            .addTo(this._map);

        // If a popup exists for this marker
        if (this._popups[markerId]) {
            // Attach existing popup to the new marker
            this._markers[markerId].setPopup(this._popups[markerId]);
        }

        // Remove old marker from the map
        oldMarker.remove();

        // Keep the party going
        return this;
    };

    // ========================================================================= //

    // Hide a marker
    this.hideMarker = function(markerId, assumeSuccess) {

        // If hiding multiple markers
        if (Array.isArray(markerId)) {
            // Log status
            if (mapbox.log) {
                console.log(`[${this.id}] Hiding multiple markers`);
            }
            // Hide each marker individually
            for (var i in markerId) {
                this.hideMarker(markerId[i]);
            }
            // Our work here is done
            return this;
        }

        // If hiding all markers
        if ('*' === markerId) {
            // Log status
            if (mapbox.log && !assumeSuccess) {
                console.log(`[${this.id}] Hiding all markers`);
            }
            // Hide each marker individually
            for (var key in this._markers) {
                this.hideMarker(key, true);
            }
            // Our work here is done
            return this;
        }

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Hiding marker "${markerId}"`);
        }

        // Get specified marker
        const marker = this.getMarker(markerId);

        // If invalid marker, bail
        if (!marker) {
            console.warn(`[MB] Unable to hide marker "${markerId}"`);
            return this;
        }

        // Remove marker from map
        marker.remove();

        // Keep the party going
        return this;
    };

    // Show a marker
    this.showMarker = function(markerId, assumeSuccess) {

        // If showing multiple markers
        if (Array.isArray(markerId)) {
            // Log status
            if (mapbox.log) {
                console.log(`[${this.id}] Showing multiple markers`);
            }
            // Show each marker individually
            for (var i in markerId) {
                this.showMarker(markerId[i]);
            }
            // Our work here is done
            return this;
        }

        // If showing all markers
        if ('*' === markerId) {
            // Log status
            if (mapbox.log && !assumeSuccess) {
                console.log(`[${this.id}] Showing all markers`);
            }
            // Show each marker individually
            for (var key in this._markers) {
                this.showMarker(key, true);
            }
            // Our work here is done
            return this;
        }

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Showing marker "${markerId}"`);
        }

        // Get specified marker
        const marker = this.getMarker(markerId);

        // If invalid marker, bail
        if (!marker) {
            console.warn(`[MB] Unable to show marker "${markerId}"`);
            return this;
        }

        // Add marker directly to map
        marker.addTo(this._map);

        // Keep the party going
        return this;
    };

    // Open a popup
    this.openPopup = function(markerId, assumeSuccess) {

        // If opening multiple popups
        if (Array.isArray(markerId)) {
            // Log status
            if (mapbox.log) {
                console.log(`[${this.id}] Opening multiple popups`);
            }
            // Open each popup individually
            for (var i in markerId) {
                this.openPopup(markerId[i]);
            }
            // Our work here is done
            return this;
        }

        // If opening all popups
        if ('*' === markerId) {
            // Log status
            if (mapbox.log && !assumeSuccess) {
                console.log(`[${this.id}] Opening all popups`);
            }
            // Open each popup individually
            for (let key in this._popups) {
                this.openPopup(key, true);
            }
            // Our work here is done
            return this;
        }

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Opening popup "${markerId}"`);
        }

        // Get popup object
        const popup = this.getPopup(markerId, true);

        // If invalid popup, bail
        if (!popup) {
            console.warn(`[MB] Unable to open popup "${markerId}"`);
            return this;
        }

        // Open the specified popup
        popup.addTo(this._map);

        // Keep the party going
        return this;
    };

    // Close a popup
    this.closePopup = function(markerId, assumeSuccess) {

        // If closing multiple popups
        if (Array.isArray(markerId)) {
            // Log status
            if (mapbox.log) {
                console.log(`[${this.id}] Closing multiple popups`);
            }
            // Close each popup individually
            for (var i in markerId) {
                this.closePopup(markerId[i]);
            }
            // Our work here is done
            return this;
        }

        // If closing all popups
        if ('*' === markerId) {
            // Log status
            if (mapbox.log && !assumeSuccess) {
                console.log(`[${this.id}] Closing all popups`);
            }
            // Close each popup individually
            for (var key in this._popups) {
                this.closePopup(key, true);
            }
            // Our work here is done
            return this;
        }

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Closing popup "${markerId}"`);
        }

        // Get popup object
        var popup = this.getPopup(markerId, true);

        // If invalid popup, bail
        if (!popup) {
            console.warn(`[MB] Unable to close popup "${markerId}"`);
            return this;
        }

        // Close popup
        popup.remove();

        // Keep the party going
        return this;
    };

    // ========================================================================= //

    // Get a specific Mapbox marker object
    this.getMarker = function(markerId, assumeSuccess) {

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Getting existing marker "${markerId}"`);
        }

        // Get existing marker
        var marker = this._markers[markerId];

        // If marker does not exist, emit warning
        if (!marker) {
            console.warn(`[MB] Unable to find marker "${markerId}"`);
        }

        // Return marker
        return marker;
    };

    // Get a specific Mapbox popup object
    this.getPopup = function(markerId, assumeSuccess) {

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Getting existing popup "${markerId}"`);
        }

        // Get existing popup
        var popup = this._popups[markerId];

        // If popup does not exist, emit warning
        if (!popup) {
            console.warn(`[MB] Unable to find popup "${markerId}"`);
        }

        // Return popup
        return popup;
    };

    // Get the current zoom level of the map
    this.getZoom = function(assumeSuccess) {

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Getting the current zoom level of the map`);
        }

        // Return zoom level
        return this._map.getZoom();
    };

    // Get the current center point of the map
    this.getCenter = function(assumeSuccess) {

        // Log status (if success is not assumed)
        if (mapbox.log && !assumeSuccess) {
            console.log(`[${this.id}] Getting the current center point of the map`);
        }

        // Return the center coordinates
        return this._map.getCenter();
    };

    // Get the current bounds of the map
    this.getBounds = function() {

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Getting the current bounds of the map`);
        }

        // Return a pair of bounds coordinates
        return this._map.getBounds();
    };

    // ========================================================================= //

    // Generate a complete map element
    this.tag = function(options) {

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Rendering map`);
        }

        // Ensure options are valid
        options = options || {};

        // Get the ID of the parent container
        const parentId = (options.parentId || null);

        // If no valid parent container specified
        if (!parentId || 'string' !== typeof parentId) {
            // Check to ensure the map is visible
            this._checkMapVisibility();
            // Log status
            if (mapbox.log) {
                console.log(`[${this.id}] Finished initializing map 👍`);
            }
            // Return the element as-is
            return this.div;
        }

        // Get specified parent container
        const parent = document.getElementById(parentId);

        // If parent container exists, populate it
        if (parent) {
            parent.appendChild(this.div);
        } else {
            console.warn(`[MB] Unable to find target container #${parentId}`);
        }

        // Check to ensure the map is visible
        this._checkMapVisibility();

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Finished initializing map in container "${parentId}" 👍`);
        }

        // Return map container
        return this.div;
    };

    // ========================================================================= //

    // Create a new map object
    this._createMap = function(mapOptions) {

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Creating map`);
        }

        // If access token is not set, emit warning and bail
        if (!window.mapboxAccessToken) {
            console.warn(`[MB] Unable to initialize map, no access token provided.`);
            return;
        }

        // Specify map container
        mapOptions.container = this.id;

        // Set access token
        mapboxgl.accessToken = window.mapboxAccessToken;

        // Initialize map data
        this._map = new mapboxgl.Map(mapOptions);
    };

    // Create a new marker object
    this._createMarker = function(coords, options) {

        // Get the marker ID
        const markerId = coords.id;

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Adding marker "${markerId}"`);
        }

        // Initialize marker object
        this._markers[markerId] = new mapboxgl.Marker(options.markerOptions)
            .setLngLat([coords.lng, coords.lat])
            .addTo(this._map);

        // If no popups exist, bail
        if (!window._mbData || !window._mbData.popups) {
            return;
        }

        // Get array of popup content for this map
        const mapPopups = window._mbData.popups[this.id] || [];

        // Get popup content for this marker
        const markerPopup = mapPopups[markerId] || null;

        // If no corresponding popup, bail
        if (!markerPopup) {
            return;
        }

        // Log status
        if (mapbox.log) {
            console.log(`[${this.id}] Adding popup to marker "${markerId}"`);
        }

        // Ensure popup options exist
        options.popupOptions = options.popupOptions || {};

        // Initialize popup object
        this._popups[markerId] = new mapboxgl.Popup(options.popupOptions).setHTML(markerPopup.content);

        // Attach new popup to its respective marker
        this._markers[markerId].setPopup(this._popups[markerId]);
    };

    // ========================================================================= //

    // Get the functional boundaries of the current map
    this._determineBounds = function() {

        // Create a set of map bounds
        const bounds = new mapboxgl.LngLatBounds();

        // Get the total number of markers
        const totalMarkers = Object.keys(this._markers).length;

        // If no markers exist, emit warning and bail
        if (!totalMarkers) {
            console.warn(`[MB] Cannot determine bounds, the map has no existing markers.`);
            return null;
        }

        // Loop through all markers
        for (const key in this._markers) {
            // Get each marker
            const marker = this._markers[key];
            // If marker is not tied to a map, skip it
            if (null === marker.map) {
                continue;
            }
            // Extend map boundaries to include marker
            bounds.extend(marker.getLngLat());
        }

        // Return a set of map bounds
        return bounds;
    };

    // Get the functional boundaries of an array of locations
    this._locationBounds = function(locations) {

        // Create a set of map bounds
        const bounds = new mapboxgl.LngLatBounds();

        // Loop through all markers
        for (const key in locations) {
            // Get each marker
            const location = locations[key];
            // Extend map boundaries to include location
            bounds.extend([
                location.lng,
                location.lat
            ]);
        }

        // Return a set of map bounds
        return bounds;
    };

    // ========================================================================= //

    // Check to ensure the map is visible, emit warnings if necessary
    this._checkMapVisibility = function() {
        // Check the container height
        this._checkHeight();
        // Final touches for rendering the map
        this._finalCalibration();
    };

    // Check the container height, emit warning if necessary
    this._checkHeight = function() {

        // If not logging, skip check
        if (!mapbox.log) {
            return;
        }

        // Get current height of container div
        var height = this.div.clientHeight;

        // If height is a positive number, check is successful
        if (0 < height) {
            return;
        }

        // Zero pixels tall, emit warning
        var url = 'https://plugins.doublesecretagency.com/mapbox/guides/setting-map-height/';
        console.warn(`[MB] The map is not visible because its parent container is zero pixels tall. More info: ${url}`);

    };

    // ========================================================================= //

    // Calculate the correct center
    this._calculateCenter = function(bounds) {

        // If default center is valid, return it
        if (this._validCoords(this._d.center)) {
            return this._d.center;
        }

        // If marker boundaries exist
        if (bounds) {
            // Get center of marker boundaries
            const boundsCenter = bounds.getCenter();
            // If bounds center is valid, return it
            if (this._validCoords(boundsCenter)) {
                return boundsCenter;
            }
        }

        // Log error message
        console.error(`[MB] No items on the map, it will be centered in the middle of the ocean! 🐙`, {'lat':0,'lng':0});

        // We are in the ocean, zoom out
        this.zoom(2, true);

        // Fallback to [0,0]
        return [0,0];
    };

    // Calculate the correct zoom
    this._calculateZoom = function(totalMarkers) {

        // If zoom is specified and valid, return it
        if (this._d.zoom && !isNaN(this._d.zoom)) {
            return this._d.zoom;
        }

        // If fewer than two markers exist
        if (!totalMarkers || totalMarkers < 2) {
            // Return a comfortable zoom level
            return this._comfortableZoom;
        }

        // After a tiny delay
        setTimeout(() => {
            // Fit map to existing markers (sans animation)
            this.fit({'animate': false});
        }, 10);

        // Return a comfortable zoom level by default
        return this._comfortableZoom;
    };

    // Final touches for rendering the map
    this._finalCalibration = function() {

        // Get the total number of markers
        const totalMarkers = Object.keys(this._markers).length;

        // If markers exist, get existing boundaries
        const bounds = totalMarkers ? this._determineBounds() : null;

        // Calculate the center & zoom values
        center = this._calculateCenter(bounds);
        zoom   = this._calculateZoom(totalMarkers);

        // Set the map center & zoom
        this.center(center);
        this.zoom(zoom);
    };

    // Check whether coordinates are valid
    this._validCoords = function(coords) {

        // If no coordinates, mark invalid
        if (!coords) {
            return false;
        }

        // If using short syntax (array with two numbers)
        if (Array.isArray(coords) && coords.length === 2) {
            // Convert to full syntax
            coords = {
                'lng': coords[0] || 0,
                'lat': coords[1] || 0
            }
        }

        // Whether coords are not [0,0]
        return !!(coords.lng || coords.lat);
    };

    // ========================================================================= //

    // Normalize a style format
    this._normalizeStyle = function(style) {

        // If no style specified, return null
        if (!style) {
            return null;
        }

        // If not a string, return style as-is
        if ('string' !== typeof style) {
            return style;
        }

        // Whether a native style was specified
        const isNative = /^[a-z-]+-v[0-9]+$/.test(style);

        // Whether a URL format was specified
        const isUrl = /:\/\//.test(style);

        // If a native style was specified, return with base URL
        if (isNative) {
            return `mapbox://styles/mapbox/${style}`;
        }

        // If a URL format was specified, return as-is
        if (isUrl) {
            return style;
        }

        // Otherwise, return JSON-decoded style
        return JSON.parse(style);
    };

    // ========================================================================= //

    // Generate a random ID
    this._generateId = function(prefix) {

        // Initialize random ID
        var randomId = '';

        // Create an array of the alphabet
        var alpha = 'abcdefghijklmnopqrstuvwxyz';
        var alphabet = alpha.split('');

        // Add six randomly selected characters
        for (char = 0; char < 6; char++) {
            var i = Math.floor(Math.random() * 25);
            randomId += alphabet[i];
        }

        // Return new ID (with optional prefix)
        return (prefix ? `${prefix}-${randomId}` : randomId);
    };

    // Prepare the object
    this.__construct(locations, options);

}
