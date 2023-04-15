---
description:
---

# Dynamic Map Model

The Dynamic Map Model is critical for generating a [Dynamic Map](/dynamic-maps/). Thanks to the magic of [chaining](/dynamic-maps/chaining/), it allows you to build maps that are as complex as they need to be.

## Public Properties

### `id`

_string_ - The map's unique ID. Can be set manually or generated automatically.

## Public Methods

### `__construct($locations = [], $options = [])`

This method will be called when a `new DynamicMap` is initialized. It creates a starting point which sets the map-building chain in motion. You will be able to build upon the map by adding markers, etc.

:::code
```twig
{% set map = mapbox.map(locations) %}
```
```php
$map = Mapbox::map($locations);
```
:::

:::warning The "map" variable
For each of the remaining examples on this page, the `map` variable will be an instance of a **Dynamic Map Model**. In each example, you will see how map methods can be chained at will.

It will be assumed that the `map` object has already been initialized, as demonstrated above.
:::

Once you have the map object in hand, you can then chain other methods to further customize the map. There is no limit as to how many methods you can chain, nor what order they should be in.

#### Arguments

- `$locations` (_mixed_) - See a description of acceptable [locations...](/dynamic-maps/locations/)
- `$options` (_array_) - Optional parameters to configure the map. (see below)

### Dynamic Map Options

| Option          | Type              | Default | Description
|:----------------|:-----------------:|:-------:|:------------
| `id`            | _string_          | <span style="white-space:nowrap">`"map-{random}"`</span> | Set the `id` attribute of the map container.
| `width`         | _int_             | _null_  | Set the width of the map (in px).
| `height`        | _int_             | _null_  | Set the height of the map (in px).
| `zoom`          | _int_             | <span style="white-space:nowrap">via `fitBounds`</span> | Set the default zoom level of the map. <span style="white-space:nowrap">(`1`-`22`)</span>
| `center`        | [coords](/models/coordinates/) | <span style="white-space:nowrap">via `fitBounds`</span> | Set the center position of the map.
| `style`         | _string_\|_object_ | _null_  | A string or object declaring the [map style](/guides/styling-a-map/).
| `mapOptions`    | _object_          | _null_  | Accepts any [`Map` options](https://docs.mapbox.com/mapbox-gl-js/api/map/#map-parameters).
| `markerOptions` | _object_          | _null_  | Accepts any [`Marker` options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters)
| `popupOptions`  | _object_          | _null_  | Accepts any [`Popup` options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup-parameters)
| `popupTemplate` | _string_          | _null_  | Template path to use for creating [popups](/dynamic-maps/popups/).
| `field`         | _string_\|_array_ | _null_  | Address field(s) to be included on the map. (includes all by default)

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::tip Locations are Skippable
If you skip the `locations` parameter, a blank map will be created.
:::

---
---

### `markers($locations, $options = [])`

Append markers to an existing map object.

#### Arguments

- `$locations` (_mixed_) - See a description of acceptable [locations...](/dynamic-maps/locations/)
- `$options` (_array_) - Optional parameters to configure the markers. (see below)
 
| Option          | Type               | Default | Description
|:----------------|:------------------:|:-------:|:------------
| `id`            | _string_           | <span style="white-space:nowrap">`"marker-{random}"`</span> | Reference point for each marker.
| `markerOptions` | _object_           | _null_  | Accepts any [`Marker` options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters)
| `popupOptions`  | _object_           | _null_  | Accepts any [`Popup` options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup-parameters)
| `popupTemplate` | _string_           | _null_  | Template path to use for creating [popups](/dynamic-maps/popups/).
| `field`         | _string_\|_array_  | _null_  | Address field(s) to be included on the map. (includes all by default)

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.markers(locations) %}
```
```php
$map->markers($locations);
```
:::

---
---

### `style($mapStyle)`

Style a map based on a given collection of styles.

:::warning Generating Styles
For more information on how to generate a set of styles, read [Styling a Map](/guides/styling-a-map/).
:::

#### Arguments

- `$mapStyle` (_string_|_array_) - A string specifying which style to use, or a JSON object declaring the map style. Defaults to `streets-v12`.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.style(mapStyle) %}
```
```php
$map->style($mapStyle);
```
:::

---
---

### `zoom($level)`

Change the map's zoom level.

#### Arguments

- `$level` (_string_) - The new zoom level. Must be an integer between `1` - `22`.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.zoom(level) %}
```
```php
$map->zoom($level);
```
:::

---
---

### `center($coords)`

Re-center the map.

#### Arguments

- `$coords` (_[coords](/models/coordinates/)_) - A simple key-value set of [coordinates](/models/coordinates/).

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.center(coords) %}
```
```php
$map->center($coords);
```
:::

---
---

### `fit($options)`

Pan and zoom map to automatically fit all markers within the viewing area. Uses [`fitBounds`](https://docs.mapbox.com/mapbox-gl-js/api/map/#map#fitbounds) internally.

#### Arguments

- `$options` (_array_) - Options as defined by the [`fitBounds` documentation](https://docs.mapbox.com/mapbox-gl-js/api/map/#map#fitbounds).

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.fit(options) %}
```
```php
$map->fit($options);
```
:::

---
---

### `panToMarker($markerId)`

Re-center map on the specified marker.

#### Arguments

- `$markerId` (_string_) - ID of the target marker.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.panToMarker(markerId) %}
```
```php
$map->panToMarker($markerId);
```
:::

---
---

### `changeMarker($markerId, $options)`

Changes the configuration of an existing marker. Rebuilds and replaces the existing marker object.

#### Arguments

- `$markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.
- `$options` (_array_) - Array of any [`Marker` options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters).

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.changeMarker(markerId, options) %}
```
```php
$map->changeMarker($markerId, $options);
```
:::

---
---

### `hideMarker($markerId)`

Hide a marker.

#### Arguments

- `$markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.hideMarker(markerId) %}
```
```php
$map->hideMarker($markerId);
```
:::

---
---

### `showMarker($markerId)`

Show a marker.

#### Arguments

- `$markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.showMarker(markerId) %}
```
```php
$map->showMarker($markerId);
```
:::

---
---

### `openPopup($markerId)`

Open the popup of a specific marker.

#### Arguments

- `$markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all popups.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.openPopup(markerId) %}
```
```php
$map->openPopup($markerId);
```
:::

---
---

### `closePopup($markerId)`

Close the popup of a specific marker.

#### Arguments

- `$markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all popups.

#### Returns

- _self_ - A chainable self-reference to this `DynamicMap` object.

:::code
```twig
{% do map.closePopup(markerId) %}
```
```php
$map->closePopup($markerId);
```
:::

---
---

### `tag($options = [])`

Renders the necessary `<div>` container to hold the map. The final `<div>` will contain specific attributes and data, which are then used to generate the map. Each container must be **initialized** in order for its dynamic map to be created.

#### Arguments

- `$options` (_array_) - Configuration options for the rendered `<div>`.

| Option     | Type     | Default | Description
|:-----------|:--------:|:-------:|:------------
| `init`     | _bool_   | `true`  | Whether to automatically initialize the map via JavaScript.
| `assets`   | _bool_   | `true`  | Whether to preload the necessary JavaScript assets.
| `callback` | _string_ | `null`  | JavaScript function to run after the map has loaded.

By setting the `init` option to `false`, the map will not be automatically initialized in JavaScript. It must therefore be [manually initialized in JavaScript](/dynamic-maps/javascript-methods/#init-mapid-null-callback-null) when the page has completely rendered.

#### Returns

- _Markup_ - A Twig Markup instance, ready to be rendered in Twig with curly braces.

:::code
```twig
{{ map.tag() }}
```
```php
$twigMarkup = $map->tag();
```
:::

If you need to [disable the automatic map initialization](/guides/delay-map-init/):

:::code
```twig
{{ map.tag({'init': false}) }}
```
```php
$twigMarkup = $map->tag(['init' => false]);
```
:::

---
---

### `getDna()`

Get the complete map DNA, which is used to hydrate a map's container in the DOM.

_Aliased as `dna` property via magic method._

#### Returns

- _array_ - An array of data containing the complete map details.

:::code
```twig
{% set dna = map.dna %}
{% set dna = map.getDna() %}
```
```php
$dna = $map->dna;
$dna = $map->getDna();
```
:::
