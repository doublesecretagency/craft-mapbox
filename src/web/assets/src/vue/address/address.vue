<template>
    <div class="address-field">
        <address-toggle></address-toggle>
        <address-subfields></address-subfields>
        <address-coords></address-coords>
        <address-meta></address-meta>
        <div style="clear:both"></div>
<!--        <address-map></address-map>-->
    </div>
</template>

<script>
// Import Pinia
import { mapStores } from 'pinia';
import { useAddressStore } from '../stores/AddressStore';

import AddressToggle from './address-toggle.vue';
import AddressSubfields from './address-subfields.vue';
import AddressCoords from './address-coords.vue';
import AddressMeta from './address-meta.vue';
// import AddressMap from './address-map.vue';

export default {
    name: 'AddressField',
    components: {
        'address-toggle': AddressToggle,
        'address-subfields': AddressSubfields,
        'address-coords': AddressCoords,
        'address-meta': AddressMeta,
        // 'address-map': AddressMap
    },
    props: {
        namespace: Object,
        settings: Object,
        data: Object,
        images: Object
    },
    computed: {
        // Load Pinia store
        ...mapStores(useAddressStore)
    },
    setup(props) {
        // Get the Pinia store
        const addressStore = useAddressStore();

        // Set Pinia values from props
        addressStore.namespace = props.namespace;
        addressStore.settings = props.settings;
        addressStore.data = props.data;
        addressStore.images = props.images;

        // Whether to show the map by default
        addressStore.showMap = props.settings.showMap;
    },
    mounted() {
        // Get the Pinia store
        const addressStore = useAddressStore();

        // Get address data & coordinates
        const addressData = addressStore.data.address;
        const addressCoords = addressStore.data.coords;

        // Activate autofill
        const autofill = mapboxsearch.autofill({accessToken: window.mapboxAccessToken});

        // When a result is selected
        autofill.addEventListener('retrieve', event => {

            // Reset address meta data
            addressData.formatted = null;
            addressData.raw = null;

            // Reset address coordinates
            addressCoords.lng = null;
            addressCoords.lat = null;

            // If not already set, reset zoom level
            addressCoords.zoom = (addressCoords.zoom ?? 11);

            // If results are invalid, bail
            if (!event || !event.detail || !event.detail.features) {
                return;
            }

            // Get feature info
            const feature = event.detail.features[0] || null;

            // If no feature info, bail
            if (!feature) {
                return;
            }

            // Get coordinates of selected location
            const lng = (feature.geometry.coordinates[0] ?? null);
            const lat = (feature.geometry.coordinates[1] ?? null);

            // Update address coordinates
            addressCoords.lng = (lng ? parseFloat(lng.toFixed(7)) : null);
            addressCoords.lat = (lat ? parseFloat(lat.toFixed(7)) : null);

            // Update address meta data
            addressData.formatted = (feature.properties.full_address ?? null);
            addressData.raw = JSON.stringify(feature);

        });
    }
}
</script>
