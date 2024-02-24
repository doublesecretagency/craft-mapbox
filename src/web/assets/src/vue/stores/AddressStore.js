import { ref, computed, watch } from 'vue';
import { defineStore } from 'pinia';

// Adjust formatting for specific countries
const formatCountries = {
    // Put street number before the street name
    numberFirst: [
        'Australia',
        'Canada',
        'France',
        'Hong Kong',
        'India',
        'Ireland',
        'Malaysia',
        'New Zealand',
        'Pakistan',
        'Singapore',
        'Sri Lanka',
        'Taiwan',
        'Thailand',
        'United Kingdom',
        'United States',
    ],
    // Put comma after the street name
    commaAfterStreet: [
        'Italy',
    ]
};

export const useAddressStore = defineStore('address', () => {

    // ========================================================================= //
    // State

    const namespace = ref({
        id: null,
        name: null,
        handle: null,
    });
    const settings = ref({});
    const data = ref({});
    const images = ref({});
    const formatting = ref(formatCountries);

    const suggestions = ref([]);
    const activeSuggestion = ref(null);

    // When address data is changed
    watch(data, () => {
        // Normalize the address info
        _normalizeData();
    }, {deep: true});

    // ========================================================================= //
    // Getters

    /**
     * Formatting configuration for the map visibility toggle.
     */
    const configToggle = computed(() => {
        // Abbreviate variable
        const s = settings.value;
        // Return configuration
        return {
            'style': s.visibilityToggle, // both | text | icon
            'text': (s.showMap ? 'Hide Map' : 'Show Map'),
            'icon': (s.showMap ? images.value.iconOff : images.value.iconOn),
        }
    });

    /**
     * Formatting configuration for the coordinates subfields.
     */
    const configCoords = computed(() => {
        // Get the coordinates mode
        const mode = settings.value.coordinatesMode;
        // Return configuration
        return {
            'type': ('hidden' === mode ? 'hidden' : 'number'),
            'readOnly': !['editable','hidden'].includes(mode),
        }
    });

    /**
     * Get the complete set of fully configured subfields.
     */
    const subfields = computed(() => {

        // Get subfield configuration from the field settings
        let subfields = settings.value.subfieldConfig;

        // Loop through all subfields
        subfields.forEach(subfield => {

            // Initialize input styles and width
            let styles = {};

            // If the subfield is disabled
            if (!subfield.enabled) {
                // Render it, but keep it hidden
                styles['display'] = 'none';
            } else {
                // Get subfield width
                let width = subfield.width;
                // Never go over 100%
                if (100 < width) {
                    width = 100;
                }
                // Give up 1% width to the right margin
                styles['width'] = `${--width}%`;
            }

            // Append styles to subfield config
            subfield.styles = styles;
        });

        // Return fully configured subfields
        return subfields;
    });

    // ========================================================================= //
    // Actions

    /**
     * Toggle visibility of the map.
     */
    function changeVisibility()
    {
        // Invert map visibility
        settings.value.showMap = !settings.value.showMap;

        // If the map is not visible
        if (!settings.value.showMap) {
            // Nothing more to do
            return;
        }

        // After a tiny delay
        setTimeout(() => {

            // Resize (redraw) the map
            this.map.resize();

            // If missing marker, bail
            if (!this.marker) {
                return;
            }

            // Get marker coordinates
            const coords = this.marker.getLngLat();

            // If missing coordinates, bail
            if (!coords['lng'] || !coords['lat']) {
                return;
            }

            // Center map on marker coordinates
            this.map.panTo(coords, {'animate': false});

        }, 10);
    }

    /**
     * Check whether coordinates are valid.
     */
    function validateCoords(coords)
    {
        // Loop through coordinates
        for (let key in coords) {
            // Skip the zoom value
            if ('zoom' === key) {
                continue;
            }
            // Get individual coordinate
            let coord = coords[key];
            // If coordinate is not a number or string, return false
            if (!['number','string'].includes(typeof coord)) {
                return false;
            }
            // If coordinate is not numeric, return false
            if (isNaN(coord)) {
                return false;
            }
            // If coordinate is an empty string, return false
            if ('' === coord) {
                return false;
            }
        }

        // Success, coordinates are valid!
        return true;
    }

    /**
     * Populate address data when a suggestion is selected.
     */
    function updateData(feature)
    {
        // Get address data & coordinates
        const addressData = data.value.address;
        const addressCoords = data.value.coords;

        // Reset address meta data
        addressData.formatted = null;
        addressData.raw = null;

        // Reset address coordinates
        addressCoords.lng = null;
        addressCoords.lat = null;

        // If not already set, reset zoom level
        addressCoords.zoom = (addressCoords.zoom || 11);

        // Get coordinates of selected location
        const lng = (feature.geometry.coordinates[0] || null);
        const lat = (feature.geometry.coordinates[1] || null);

        // Update address coordinates
        addressCoords.lng = (lng ? parseFloat(lng.toFixed(7)) : null);
        addressCoords.lat = (lat ? parseFloat(lat.toFixed(7)) : null);

        // Update address meta data
        addressData.name      = (feature.properties.name || null);
        addressData.mapboxId  = (feature.properties.mapbox_id || null);
        addressData.formatted = (feature.properties.full_address || null);
        addressData.raw       = JSON.stringify(feature);

        // Get feature context data
        const c = feature.properties.context;

        // Set address data to Vue
        addressData.street1      = (c.address ? c.address.name : null);
        addressData.street2      = null;
        addressData.city         = (c.place ? c.place.name : null);
        addressData.state        = (c.region ? c.region.region_code : null);
        addressData.zip          = (c.postcode ? c.postcode.name : null);
        addressData.neighborhood = (c.neighborhood ? c.neighborhood.name : null);
        addressData.county       = (c.district ? c.district.name : null);
        addressData.country      = (c.country ? c.country.name : null);

        // Reset suggestions
        this.suggestions = [];
        this.activeSuggestion = null;

        // If not changing the map visibility, bail
        if ('noChange' === settings.value.mapOnSearch) {
            return;
        }

        // Change map visibility based on settings
        settings.value.showMap = ('open' === settings.value.mapOnSearch);
    }

    /**
     * Normalize the address data when anything changes.
     */
    function _normalizeData()
    {
        // If coordinates are invalid
        if (!validateCoords(data.value.coords)) {
            // Reset the meta fields
            data.value.address['formatted'] = null;
            data.value.address['raw'] = null;
        }
    }

    // ========================================================================= //

    // Return reactive values
    return {
        // State
        namespace,
        settings,
        data,
        images,
        formatting,
        suggestions,
        activeSuggestion,

        // Getters
        configToggle,
        configCoords,
        subfields,

        // Actions
        changeVisibility,
        validateCoords,
        updateData,
    }

})
