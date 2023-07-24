<template>
    <div>

        <div>
            <input
                type="text"
                v-for="subfield in addressStore.subfields"
                :placeholder="subfield.label + (subfield.required ? ' *' : '')"
                v-model="addressStore.data.address[subfield.handle]"
                :name="`${addressStore.namespace.name}[${subfield.handle}]`"
                class="text fullwidth"
                :class="{'required': isRequiredAndInvalid(subfield)}"
                :style="subfield.styles"
                autocomplete="off"
                v-on="subfield.autocomplete ? {keydown, keyup, focus} : {}"
            />
        </div>

        <div
            ref="results"
            class="search-results"
            v-show="showResults"
            :style="{
                'width': resultsWidth,
                'left': resultsLeft,
            }"
        >
            <div class="search-results-list">
                <div
                    class="suggestion"
                    :class="{'active': i === addressStore.activeSuggestion}"
                    v-for="(suggestion, i) in addressStore.suggestions"
                    @click="retrieve(suggestion)"
                    :key="i"
                    tabindex="-1"
                >
                    <div class="suggestion-icon">
                        <img
                            :src="makiSrc(suggestion.maki || 'default')"
                            :alt="makiAlt(suggestion.maki || 'default')"
                        >
                    </div>
                    <div class="suggestion-text">
                        <div class="suggestion-name">{{ suggestion.name }}</div>
                        <div class="suggestion-desc">{{ suggestion.place_formatted }}</div>
                    </div>
                </div>
            </div>

            <div class="search-results-attribution">
                <a href="https://www.mapbox.com/search-service" target="_blank" tabindex="-1">Powered by Mapbox</a>
            </div>
        </div>

    </div>
</template>

<script>
// Import Pinia
import { mapStores } from 'pinia';
import { useAddressStore } from '../stores/AddressStore';

// Import from Vue
import { toRaw } from 'vue';

export default {
    data() {
        // If libraries have not yet been defined
        if (!window.mapboxsearchcore) {
            // Log error and bail
            console.warn('[MB] Unable to load the Mapbox search libraries.');
        }

        // Extract search libraries
        const {
            SearchBoxCore,
            SearchSession
        } = window.mapboxsearchcore;

        // Configure the search options
        const options = this.configureOptions();

        // Configure search session
        const search = new SearchBoxCore(options);
        const session = new SearchSession(search, 200); // Debounce 2 of 2

        // Return data
        return {
            'session': session,
            'resultsLeft': '0',
            'resultsWidth': '400px',
        }
    },
    computed: {
        // Load Pinia store
        ...mapStores(useAddressStore),
        showResults() {
            // Get the Pinia store
            const addressStore = useAddressStore();
            // Return whether any results exist
            return (addressStore.suggestions.length > 0);
        }
    },
    mounted() {
        this.listeners();
    },
    methods: {

        /**
         * Configure the field options.
         */
        configureOptions()
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // Configure the search object
            // https://docs.mapbox.com/mapbox-search-js/api/core/search/#searchboxoptions
            const options = {
                accessToken: window.mapboxAccessToken,
            };

            // Get optional field parameters
            const fieldParams = (addressStore.settings.fieldParams || {});

            // Set maximum number of search results (default 6)
            options['limit'] = (fieldParams.limit || 6);

            // If limit is more than 10, emit warning and set to 10
            if (10 < options['limit']) {
                console.warn('[GM] Search results limit may not exceed 10.');
                options['limit'] = 10;
            }

            // Set language of search results (default English)
            options['language'] = (fieldParams.language || 'en');

            // If restricted to one country, specify country
            if (fieldParams.country) {
                options['country'] = fieldParams.country;
            }

            // Attempt to determine coordinates
            const proximity = this.proximity();

            // If coords can be determined, set search proximity
            if (proximity) {
                options['proximity'] = proximity;
            }

            // Return fully configured options
            return options;
        },

        // ========================================================================= //

        /**
         * Determine target proximity for search.
         */
        proximity() {
            // Get the Pinia store
            const a = useAddressStore();

            // If valid, get coords from the existing field data
            if (a.validateCoords(a.data.coords)) {
                // Use existing coordinates as the search center
                return a.data.coords;
            }

            // If valid, get default coords from the field settings
            if (a.validateCoords(a.settings.coordinatesDefault)) {
                return a.settings.coordinatesDefault;
            }

            // No coords could be determined
            return false;
        },

        // ========================================================================= //

        /**
         * Add listeners for search events.
         */
        listeners()
        {
            // Get original (raw) session object
            const session = toRaw(this.session);

            // Get the Pinia store
            const addressStore = useAddressStore();

            // When a search is performed
            session.addEventListener('suggest', (response) => {
                // If no response, bail
                if (!response) {
                    return;
                }
                // Store the results
                addressStore.suggestions = (response.suggestions || []);
            });

            // When a result is selected
            session.addEventListener('retrieve', (response) => {
                // If no response, bail
                if (!response) {
                    return;
                }
                // If no features, log message and bail
                if (!response.features || !response.features.length) {
                    console.warn('[MB] No location data available.');
                    return;
                }
                // Load feature info
                addressStore.updateData(response.features[0]);
                // If valid, get coords from the selected result
                if (addressStore.validateCoords(addressStore.data.coords)) {
                    // Update the search center to use newly selected coordinates
                    session.search.defaults.proximity = addressStore.data.coords;
                }
            });
        },

        // ========================================================================= //

        /**
         * Perform address lookup.
         */
        search(searchValue)
        {
            // Perform search
            toRaw(this.session).suggest(searchValue);

            // Reset list of search suggestions
            this.resetSuggestions();
        },

        /**
         * Retrieve a specific address.
         */
        retrieve(suggestion)
        {
            // Retrieve suggestion info
            toRaw(this.session).retrieve(suggestion);

            // Reset list of search suggestions
            this.resetSuggestions();
        },

        // ========================================================================= //

        /**
         * Reset list of search suggestions.
         */
        resetSuggestions()
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // Reset suggestions
            addressStore.suggestions = [];
            addressStore.activeSuggestion = null;
        },

        // ========================================================================= //

        /**
         * When a subfield gains focus.
         */
        focus(e)
        {
            // Reset list of search suggestions
            this.resetSuggestions();

            // Get target subfield element
            const subfield = e.target;

            // Move results to be right after the subfield
            subfield.parentNode.insertBefore(
                this.$refs.results,  // Results list
                subfield.nextSibling // Next subfield
            );

            // Adjust position of search results
            this.resultsLeft  = `${subfield.offsetLeft}px`;
            this.resultsWidth = `${subfield.offsetWidth}px`;
        },

        /**
         * Handle a keydown event.
         */
        keydown(e)
        {
            // If the escape key was pressed
            if (27 === e.keyCode) {
                this.keyupEsc(e);
            }

            // If results are hidden, bail
            if (!this.showResults) {
                return;
            }

            // Prevent default behavior
            // on specified key presses
            const keys = [
                13, // Enter/Return
                38, // Left arrow
                40, // Right arrow
            ];

            // If key isn't specified, bail
            if (-1 === keys.indexOf(e.keyCode)) {
                return;
            }

            // Prevent default behavior
            e.preventDefault();
        },

        /**
         * Handle a keyup event.
         */
        keyup(e)
        {
            // Which key was pressed?
            switch (e.keyCode) {

                // Do nothing
                case 16: // Shift
                case 27: // Escape
                case 32: // Space
                case 37: // Left arrow
                case 39: // Right arrow
                    break;

                // Do something
                case 13: // Enter/Return
                    this.keyupEnter(e);
                    break;
                case 38: // Up arrow
                    this.keyupArrowUp(e);
                    break;
                case 40: // Down arrow
                    this.keyupArrowDown(e);
                    break;
                default: // Any other key
                    this.keyupTyping(e);
                    break;

            }
        },

        /**
         * When the Escape key is pressed.
         */
        keyupEsc(e)
        {
            // Reset list of search suggestions
            this.resetSuggestions();

            // After a tiny delay
            setTimeout(() => {
                // Refocus the input
                e.target.focus();
            }, 10);
        },

        /**
         * When the "Enter" key is pressed.
         */
        keyupEnter(e)
        {
            // Prevent the default behavior
            e.preventDefault();

            // Get the Pinia store
            const addressStore = useAddressStore();

            // Get index of the active suggestion
            const i = addressStore.activeSuggestion;

            // If no active suggestion, bail
            if (null === i) {
                return;
            }

            // Retrieve selected suggestion
            this.retrieve(addressStore.suggestions[i]);
        },

        /**
         * When the up arrow key is pressed.
         */
        keyupArrowUp(e)
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // If no active suggestion
            if (null === addressStore.activeSuggestion) {
                // Start with the last
                addressStore.activeSuggestion = (addressStore.suggestions.length - 1);
                return;
            }

            // Decrement the active suggestion
            addressStore.activeSuggestion--;

            // If less than zero
            if (addressStore.activeSuggestion <= -1) {
                // Reset active suggestion
                addressStore.activeSuggestion = null;
            }
        },

        /**
         * When the down arrow key is pressed.
         */
        keyupArrowDown(e)
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // If no active suggestion
            if (null === addressStore.activeSuggestion) {
                // Start with the first
                addressStore.activeSuggestion = 0;
                return;
            }

            // Increment the active suggestion
            addressStore.activeSuggestion++;

            // If more than the total number of suggestions
            if (addressStore.suggestions.length <= addressStore.activeSuggestion) {
                // Reset active suggestion
                addressStore.activeSuggestion = null;
            }
        },

        /**
         * When the user is generally typing.
         */
        keyupTyping(e)
        {
            // If the timer exists
            if (this.timer) {
                // Clear timer
                clearTimeout(this.timer);
                this.timer = null;
            }

            // Set a new timer
            this.timer = setTimeout(() => {
                // Perform search
                this.search(e.target.value);
            }, 350); // Debounce 1 of 2
        },

        // ========================================================================= //

        /**
         * Get src of the Maki icon.
         */
        makiSrc(icon)
        {
            // Get the Pinia store
            const addressStore = useAddressStore();

            // Return the icon URL
            return addressStore.images[`maki-${icon}`] || `#${icon}`;
        },

        /**
         * Get alt of the Maki icon.
         */
        makiAlt(icon)
        {
            // Convert to title case
            icon = icon.replace(
                /\w\S*/g,
                function (txt) {
                    return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                }
            );

            // Prepend "Icon"
            return `${icon} Icon`;
        },

        // ========================================================================= //

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

    }
}
</script>

<style scoped>
.search-results {
    width: 670px;
    background-color: #ffffff;
    border: none;
    border-radius: 4px;
    box-shadow: 0 0 10px 2px rgba(0, 0, 0, 0.05), 0 0 6px 1px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.1);
    color: rgba(0, 0, 0, 0.75);
    font-family: -apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Ubuntu, roboto, noto, arial, sans-serif;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.2em;
    min-width: min(300px, 100vw);
    overflow-y: auto;
    position: absolute;
    transform: translateZ(0px);
    transition: visibility 150ms;
    z-index: 1000;
}

.search-results-list {}
.suggestion {
    align-items: center;
    display: flex;
    padding: 0.5em 0.75em;
    cursor: pointer;
}
.suggestion:hover {
    background-color: #f0f0f0;
}
.suggestion.active,
.suggestion:active {
    background-color: #f0f0f0;
}

.suggestion-icon {
    margin-right: 10px;
    transform: scale(1.2);
}
.suggestion-icon img {
    color: rgba(0, 0, 0, 0.75);
}
.suggestion-text {}
.suggestion-name {
    font-weight: bold;
}
.suggestion-desc {}

.search-results-attribution {
    padding: 0.5em 0.75em;
}
.search-results-attribution a {
    color: #667f91;
}
.search-results-attribution a:hover {
    text-decoration: underline;
}
</style>
