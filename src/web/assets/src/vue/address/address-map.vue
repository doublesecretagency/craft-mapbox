<template>
    <div class="mb-map" v-show="addressStore.settings.showMap"></div>
</template>

<script>
// Import Pinia
import { mapStores } from 'pinia';
import { useAddressStore } from '../stores/AddressStore';

export default {
    data() {
        // Make map & marker universally available
        return {
            map: null,
            marker: null,
        }
    },
    computed: {
        // Load Pinia store
        ...mapStores(useAddressStore),
    },
    watch: {
        // When coordinates are changed, update the marker position
        'addressStore.data.coords.lng': function () {
            this._updateMarkerPosition();
        },
        'addressStore.data.coords.lat': function () {
            this._updateMarkerPosition();
        },
        // When zoom level is changed, update the map zoom
        'addressStore.data.coords.zoom': function () {
            this._updateZoomLevel();
        }
    },
    async mounted() {

        // Attempt to get map center from field
        let center = this._getFieldCenter();

        // Initialize map using coordinates from field data or settings
        if (center) {
            this.initMap(center);
            return;
        }

        // Attempt to get map center from user's current location
        await new Promise(function(resolve, reject) {

            // Output console notification
            console.log('Attempting geolocation...');
            // Attempt geolocation
            navigator.geolocation.getCurrentPosition(resolve, reject, {timeout: 5000});

        }).then(

            // SUCCESS
            result => {
                // Output console notification
                console.log('Success!');
                // If coordinates are invalid, bail
                if (!result.coords) {
                    return;
                }
                // Initialize map based on user's current location
                this.initMap({
                    lng: result.coords.longitude,
                    lat: result.coords.latitude,
                    zoom: 10
                });
            },

            // FAILED
            error => {
                // Output error message in console
                console.warn('[MB] Unable to perform HTML5 geolocation.', error);
                // Use the generic fallback coordinates (Bermuda Triangle)
                // https://plugins.doublesecretagency.com/mapbox/guides/bermuda-triangle/
                this.initMap({
                    lng: -64.7527469,
                    lat: 32.3113966,
                    zoom: 6
                });
            }

        );

    },
    methods: {

        /**
         * Initialize the map.
         */
        initMap(startingPosition)
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // After a tiny delay
            setTimeout(() => {

                try {
                    const mapboxgl = window.mapboxgl;

                    // If mapboxgl object doesn't exist yet, log message and bail
                    if (!mapboxgl) {
                        console.error('[GM] The `mapboxgl` object has not yet been loaded.');
                        return;
                    }

                    // Determine map center
                    let mapCenter = {
                        lng: parseFloat(startingPosition.lng),
                        lat: parseFloat(startingPosition.lat)
                    }

                    // Set map options
                    const mapOptions = {
                        'accessToken': window.mapboxAccessToken,
                        'container': this.$el,
                        'center': mapCenter,
                        'zoom': parseFloat(startingPosition.zoom) || 0,
                        'minZoom': 0,
                        'style': 'mapbox://styles/mapbox/streets-v12',
                        'attributionControl': false
                    };

                    // Set marker options
                    const markerOptions = {
                        'draggable': true,
                    };

                    // Create the map
                    this.map = new mapboxgl.Map(mapOptions)
                        .addControl(new mapboxgl.NavigationControl({
                            'showCompass': false,
                        }))
                        .addControl(new mapboxgl.GeolocateControl({
                            'positionOptions': {
                                'enableHighAccuracy': true,
                            },
                            'trackUserLocation': true,
                            'showUserHeading': true,
                        }));

                    // Create a draggable marker
                    this.marker = new mapboxgl.Marker(markerOptions)
                        .setLngLat(mapCenter)
                        .addTo(this.map);

                    // Store map & marker with Pinia
                    addressStore.map = this.map;
                    addressStore.marker = this.marker;

                    // Whenever map is zoomed, update zoom value
                    this.map.on('zoomend', () => {
                        addressStore.data.coords['zoom'] = this._getMapZoom();
                    });

                    // Whenever marker is dropped, re-center the map
                    this.marker.on('dragend', () => {
                        // Get marker coordinates
                        const coords = this.marker.getLngLat();
                        // Update coordinates in Pinia store
                        addressStore.data.coords = {
                            'lng': parseFloat(coords.lng.toFixed(7)),
                            'lat': parseFloat(coords.lat.toFixed(7)),
                            'zoom': this._getMapZoom()
                        };
                        // Center the map
                        this._centerMap();
                    });

                } catch (error) {

                    // Unable to initialize the map
                    console.error(error);

                }

            }, 40);

        },

        // ========================================================================= //

        /**
         * Center map based on current marker position.
         */
        _centerMap()
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // Check whether field has valid coordinates
            const validCoords = addressStore.validateCoords(addressStore.data.coords);

            // If field does not have valid coordinates, bail
            if (!validCoords) {
                return;
            }

            // After a tiny delay
            setTimeout(() => {

                // Get coordinates
                const coords = JSON.parse(JSON.stringify(addressStore.data.coords));

                // Center map on marker coordinates
                this.map.panTo(coords);

                // Resize (redraw) the map
                this.map.resize();

            }, 10);
        },

        // ========================================================================= //

        /**
         * Attempt to get map center coordinates based on the field data or settings.
         */
        _getFieldCenter()
        {
            // Get the Pinia store
            const a = useAddressStore();

            // If valid, get coords from the existing field data
            if (a.validateCoords(a.data.coords)) {
                return a.data.coords;
            }

            // If valid, get default coords from the field settings
            if (a.validateCoords(a.settings.coordinatesDefault)) {
                return a.settings.coordinatesDefault;
            }

            // Unable to get any coordinates from the field
            return false;
        },

        /**
         * Get the current zoom level of the map.
         */
        _getMapZoom()
        {
            // Get the current zoom, or fallback to 11
            const zoom = this.map.getZoom() || 0;

            // Return as a float
            return parseFloat(zoom.toFixed(2));
        },

        // ========================================================================= //

        /**
         * Update the marker position.
         */
        _updateMarkerPosition()
        {
            // Get the Pinia store
            const addressStore = useAddressStore()

            // Check whether field has valid coordinates
            const validCoords = addressStore.validateCoords(addressStore.data.coords);

            // If field does not have valid coordinates, bail
            if (!validCoords) {
                return;
            }

            // Get coordinates
            let coords = addressStore.data.coords;

            // Update marker position
            this.marker.setLngLat(coords);

            // Center map
            this._centerMap();

            // Update zoom based on map level
            addressStore.data.coords['zoom'] = this.map.getZoom();
        },

        /**
         * Update the zoom level.
         */
        _updateZoomLevel()
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // Get zoom level from field data
            let zoom = parseFloat(addressStore.data.coords['zoom']);

            // Corrections for incorrect zoom value
            if (0 === zoom || zoom < 0) {
                // Fallback when zoom is too low
                zoom = 0;
            } else if (!zoom || isNaN(zoom)) {
                // Fallback when zoom is invalid
                zoom = 11;
            }

            // Set map zoom level
            this.map.setZoom(zoom);
        },

    }
};
</script>
