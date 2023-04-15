---
description:
---

# `dynamicmap.js`

This file contains the `DynamicMap` model, which can be used to create dynamic, chainable map objects. Each instance of a `DynamicMap` object correlates with a different map instance.

:::warning Don't access directly
It is extremely rare to need to create a `DynamicMap` model directly. You will almost always use the [`mapbox` singleton object](/javascript/mapbox.js/) to create and retrieve map objects.
:::

For a more comprehensive explanation of how to use the internal API, check out the docs regarding the [Universal Methods](/dynamic-maps/universal-methods/) and [JavaScript Methods](/dynamic-maps/javascript-methods/).

### The `map` variable

For each example on this page, a `map` variable will be an instance of a specific `DynamicMap` object. Each example assumes that the `map` object has already been initialized, as demonstrated on the [`mapbox.js` page](/javascript/mapbox.js/).

:::tip Get a Map
A `map` can be _created_ using [`mapbox.map`](/javascript/mapbox.js/#map-locations-options), or _retrieved_ using [`mapbox.getMap`](/javascript/mapbox.js/#getmap-mapid).
:::

## Map Methods

### `markers(locations, options = [])`

Add markers to an existing map. Does not overwrite any existing markers.

```js
map.markers([
    {'lng':  -73.935242, 'lat': 40.730610}, // New York
    {'lng': -118.243683, 'lat': 34.052235}, // Los Angeles
    {'lng':  -87.623177, 'lat': 41.881832}  // Chicago
]);
```

#### Arguments

- `locations` (_[coords](/models/coordinates/)_|_array_) - See a description of acceptable [locations...](/dynamic-maps/locations/)
- `options` (_array_) - Optional parameters to configure the markers. (see below)
 
| Option          | Type                 | Default | Description
|-----------------|:--------------------:|:-------:|-------------
| `id`            | _string_             | <span style="white-space:nowrap">`"marker-{random}"`</span> | Reference point for each marker.
| `markerOptions` | _object_             | _null_  | Accepts any [`Marker` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters)
| `popupOptions`  | _object_             | _null_  | Accepts any [`Popup` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup-parameters)

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `style(mapStyle)`

Style a map based on a given collection of styles.

:::tip Generating Styles
For more information on how to generate a set of styles, read [Styling a Map](/guides/styling-a-map/).
:::

#### Arguments

- `mapStyle` (_string_|_object_) - A string specifying which style to use, or a JSON object declaring the map style. Defaults to `streets-v12`.

```js
map.style('satellite-v9');
```

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `zoom(level)`

Change the map's zoom level.

#### Arguments

- `level` (_int_) - The new zoom level. Must be an integer between `1` - `22`.

```js
map.zoom(10);
```

:::tip Zoom Level Reference
- `1` is zoomed out, a view of the entire planet.
- `22` is zoomed in, as close to the ground as possible.
:::

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `center(coords)`

Re-center the map.

#### Arguments

- `coords` (_[coords](/models/coordinates/)_) A simple key-value set of coordinates.

```js
map.center({
    'lng': -64.7527469,
    'lat': 32.3113966
});
```

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `fit(options)`

Pan and zoom map to automatically fit all markers within the viewing area. Uses [`fitBounds`](https://docs.mapbox.com/mapbox-gl-js/api/map/#map#fitbounds) internally.

#### Arguments

- `options` (_object_) - Options as defined by the [`fitBounds` documentation](https://docs.mapbox.com/mapbox-gl-js/api/map/#map#fitbounds).

```js
map.fit({'padding': 100});
```

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

## Marker Methods
 
:::warning Automatically generated marker IDs
If the marker has been created from an Element, it will have a marker ID matching this formula:

```
    [ELEMENT ID]-[FIELD HANDLE]
```

Let's say you have an Address field with the handle of `address` attached to your Entries. When you use those entries to create a map, the markers will generate IDs similar to this:

```
    21-address
    33-address
    42-address
    etc...
```
:::

Conversely, if the markers have been created manually via JavaScript, it will use the marker ID specified in the options, or even stowed alongside the coordinates.

If no marker ID is specified, new markers will use a randomly generated ID.

---
---

### `panToMarker(markerId)`

Re-center map on the specified marker.

```js
map.panToMarker('33-address');
```

#### Arguments

- `markerId` (_string_) - The ID of the marker that you want to pan to.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `changeMarker(markerId, options)`

Changes the configuration of an existing marker. Rebuilds and replaces the existing marker object.

```js
map.changeMarker('33-address', {'color': '#DF04EF'});
```

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.
- `options` (_array_) - Array of any [`Marker` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters).

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `hideMarker(markerId)`

Hide a marker. The marker will not be destroyed, it will simply be detached from the map.

```js
map.hideMarker('33-address');
```

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `showMarker(markerId)`

Show a marker. The marker will be re-attached to the map.

```js
map.showMarker('33-address');
```

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `openPopup(markerId)`

Open the popup of a specific marker.

```js
map.openPopup('33-address');
```

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all popups.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

### `closePopup(markerId)`

Close the popup of a specific marker.

```js
map.closePopup('33-address');
```

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all popups.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

---
---

## Non-Chainable Methods

:::warning Breaking the Chain
The following methods are the only ones which do not return a chainable map object.
:::

### `getMarker(markerId)`

Get the [Mapbox Marker object](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker) of a specified marker.

```js
const marker = map.getMarker('33-address');
```

#### Arguments

- `markerId` (_string_) - The ID of the marker that you want to access.

#### Returns

- A Mapbox [Marker](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker) object.

---
---

### `getPopup(markerId)`

Get the [Mapbox Popup object](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup) of a specified popup.

```js
const popup = map.getPopup('33-address');
```

#### Arguments

- `markerId` (_string_) - The ID of the marker with the popup that you want to access.

#### Returns

- A Mapbox [Popup](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup) object.

---
---

### `getZoom()`

Get the current zoom level.

```js
const level = map.getZoom();
```

#### Returns

- An integer between `1` - `22` representing the current zoom level.

---
---

### `getCenter()`

Get the center point coordinates of the map based on its current position.

```js
const coords = map.getCenter();
```

#### Returns

- A set of [coordinates](/models/coordinates/) representing the current map center.

---
---

### `getBounds()`

Get the [bounds](https://docs.mapbox.com/mapbox-gl-js/api/geography/#lnglatboundslike) of the map based on its current position.

```js
const bounds = map.getBounds();
```

#### Returns

- A set of [bounds](https://docs.mapbox.com/mapbox-gl-js/api/geography/#lnglatboundslike), which is effectively a pair of [coordinates](/models/coordinates/), representing the Southwest & Northeast corners of a rectangle.

---
---

### `tag(options = {})`

Creates a new `<div>` element, detached from the DOM. If a `parentId` is specified, the element will automatically be injected into the specified parent container.

```js
map.tag({'parentId': 'target-parent-id'});
```

#### Arguments

- `options` (_array_) - Configuration options for the rendered dynamic map.

| Option     | Type     | Default | Description
|:-----------|:--------:|:-------:|-------------
| `parentId` | _string_ | `null`  | The ID of the target parent container for the newly created element.

#### Returns

- A new DOM element. Will always return the newly created element, regardless of whether it was automatically injected into a parent container.
