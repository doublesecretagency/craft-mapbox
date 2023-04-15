---
description:
---

# `mapbox.js`

This file contains the `mapbox` JavaScript object. Additionally, a `mapbox` variable will be automatically set as a singleton in the global scope at runtime.

**Use this object as a starting point for creating maps.**

```js
const map = mapbox.map(locations);
```

:::warning How it works
Internally, the `mapbox` object can create multiple instances of the [`DynamicMap` model](/javascript/dynamicmap.js/). Each `DynamicMap` object will be directly tied to a Mapbox map on the page.

The `mapbox` object also keeps a reference to all maps which have already been created, so you can easily access them later.
:::

For a more comprehensive explanation of how to use the internal API, check out the docs regarding the [Universal Methods](/dynamic-maps/universal-methods/) and [JavaScript Methods](/dynamic-maps/javascript-methods/).

## Map Management Methods

### `map(locations = [], options = {})`

Calling this method will create a new [`DynamicMap` map object](/javascript/dynamicmap.js/).

```js
// Marker locations
const locations = [
    {'lng':  -73.935242, 'lat': 40.730610}, // New York
    {'lng': -118.243683, 'lat': 34.052235}, // Los Angeles
    {'lng':  -87.623177, 'lat': 41.881832}  // Chicago
];

// Map options
const options = {
    'id': 'us-cities',
    'height': 300,
    'zoom': 4
};

// Create a new DynamicMap object
const map = mapbox.map(locations, options);
```

The map object is a starting point which sets the map-building chain in motion. You will be able to build upon the map by adding markers, etc.

Once you have the map object in hand, you can then chain methods from within the `DynamicMap` object to further customize the map. There is no limit as to how many methods you can chain, nor what order they should appear in.

#### Arguments

 - `locations` (_[coords](/models/coordinates/)_|_array_) - A single set of coordinates, or an array of coordinate sets.
 - `options` (_array_) - Optional parameters to configure the map. (see below)

### Dynamic Map Options

| Option          | Type            | Default | Description
|-----------------|:---------------:|:-------:|-------------
| `id`            | _string_        | <span style="white-space:nowrap">`"map-{random}"`</span> | Set the `id` attribute of the map container.
| `width`         | _int_           | _null_  | Set the width of the map (in px).
| `height`        | _int_           | _null_  | Set the height of the map (in px).
| `zoom`          | _int_           | (uses `fitBounds`) | Set the default zoom level of the map. <span style="white-space:nowrap">(`1`-`22`)</span>
| `center`        | _[coords](/models/coordinates/)_ | (uses `fitBounds`) | Set the center position of the map.
| `style`         | _string_\|_object_ | _null_  | A string or object declaring the [map style](/guides/styling-a-map/).
| `mapOptions`    | _object_        | _null_  | Accepts any [`Map` parameters](https://docs.mapbox.com/mapbox-gl-js/api/map/#map-parameters).
| `markerOptions` | _object_        | _null_  | Accepts any [`Marker` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters)
| `popupOptions`  | _object_        | _null_  | Accepts any [`Popup` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup-parameters)

#### Returns

 - A chainable `DynamicMap` object.

:::tip Locations are Skippable
If you omit the `locations` parameter, or pass in an empty array, a blank map will be created.
:::

---
---

### `getMap(mapId)`

```js
const map = mapbox.getMap('my-map');
```

Retrieve an existing map object.

#### Arguments

 - `mapId` (_string_) - The ID of the map that you want to access.

#### Returns

 - A chainable `DynamicMap` object.

---
---

## Map Initialization Methods

### `init(mapId = null, callback = null)`

```js
mapbox.init();
```

Initialize a map, or a group of maps. This will be automatically run (unless disabled) for each map on the page.

#### Arguments

 - `mapId` (_string_|_array_|_null_) - The ID of the map that you want to access. You can also specify an array of map IDs. You can also pass _null_ (or omit both parameters) to initialize all maps on the page.
 - `callback` (_function_) - An optional callback function, to be executed after the map has finished loading.

Depending on what is specified as the `mapId` value, the `init` method can initialize one or many maps simultaneously.

```js
// Null - Initialize all maps on the page
mapbox.init();

// String - Initialize only the specified map
mapbox.init('my-map');

// Array - Initialize all specified maps
mapbox.init(['map-one', 'map-two', 'map-three']);
```

You can specify a `callback` function to be run after the map has loaded.

```js
// Pass callback function by reference
mapbox.init('my-map', myCallbackFunction);

// Pass anonymous callback function
mapbox.init('my-map', function () {
    console.log("The map has finished loading!");
});
```

---
---

## Public Properties

### `log`

#### Type

 - _bool_ - Determines whether the JavaScript methods should log their progress to the console.

Set to `false` by default. Can be enabled by setting to `true` before any methods have been run.

:::tip devMode
The `log` property will automatically be set to `true` when [`devMode`](https://craftcms.com/knowledge-base/what-dev-mode-does) is enabled.
:::
