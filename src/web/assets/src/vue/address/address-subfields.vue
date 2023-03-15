<template>
    <div>
        <mapbox-address-autofill>
            <input v-for="subfield in addressStore.subfields"
                type="text"
                :placeholder="subfield.label + (subfield.required ? ' *' : '')"
                :ref="subfield.handle"
                v-model="addressStore.data.address[subfield.handle]"
                :name="`${addressStore.namespace.name}[${subfield.handle}]`"
                class="text fullwidth"
                :class="{'required': isRequiredAndInvalid(subfield)}"
                :style="subfield.styles"
                :autocomplete="autocomplete(subfield.handle)"
            />
        </mapbox-address-autofill>
    </div>
</template>

<script>
// Import Pinia
import { mapStores } from 'pinia';
import { useAddressStore } from '../stores/AddressStore';

export default {
    computed: {
        // Load Pinia store
        ...mapStores(useAddressStore),
    },
    mounted() {
        // Initialize the Autocomplete functionality
        this.initAutocomplete();
    },
    methods: {

        /**
         * Initialize the Autocomplete functionality.
         */
        initAutocomplete()
        {
            // TBD
        },

        /**
         * Whether a subfield is both required and empty.
         */
        isRequiredAndInvalid(subfield)
        {
            // If subfield is not required, return false
            if (!subfield.required) {
                return false;
            }

            // Get the Pinia store
            const addressStore = useAddressStore();

            // If subfield is not empty, return false
            if (addressStore.data.address[subfield.handle]) {
                return false;
            }

            // Subfield is required and empty
            return true;
        },

        /**
         * Get the autocomplete attribute value for a given subfield handle.
         */
        autocomplete(handle)
        {
            switch (handle) {
                case 'name':    return 'organization';
                case 'street1': return 'address-line1';
                case 'street2': return 'address-line2';
                case 'city':    return 'address-level2';
                case 'state':   return 'address-level1';
                case 'zip':     return 'postal-code';
                case 'country': return 'country-name';
                default:        return 'chrome-off';
            }
        },

    }
}
</script>
