---
description: Control your maps equally with JavaScript, Twig, or PHP. Available in multiple languages, these universal methods can be chained to create complex maps.
---

# Universal Methods

The following methods apply equally, whether you are working in JavaScript, Twig, or PHP. These methods have nearly identical parameters and behaviors across all three languages.

:::warning The Magic of Chaining
Each of these methods can be [chained together](/dynamic-maps/chaining/) in any order you'd like. Chaining can be a powerful technique, allowing you to build complex maps with ease.
:::

There are also a few language-specific methods to be aware of. In addition to the Universal Methods below, check out the extended documentation for [JavaScript Methods](/dynamic-maps/javascript-methods/) and [Twig & PHP Methods](/dynamic-maps/twig-php-methods/).

## `markers(locations, options)`

:::code
```js
map.markers(locations, options);
```
```twig
{% do map.markers(locations, options) %}
```
```php
$map->markers($locations, $options);
```
:::

Places additional markers onto the map.

#### Arguments

- `locations` (_[locations](/dynamic-maps/locations/)_) - Will be used to create additional markers for the map.
- `options` (_array_|_null_) - The `options` parameter allows you to configure the markers in greater detail. These options will _only_ apply to the markers created in this method call.

:::warning Available Options
Most, but not all, of these options are available across JavaScript, Twig, and PHP. Please note the few options which are not universally available.
:::

| Option          | Available            | Description
|-----------------|:--------------------:|-------------
| `id`            | JavaScript, Twig/PHP | Reference point for each marker.
| `markerOptions` | JavaScript, Twig/PHP | Accepts any [`Marker` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters)
| `popupOptions`  | JavaScript, Twig/PHP | Accepts any [`Popup` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup-parameters)
| `popupTemplate` | Twig/PHP             | Template path to use for creating [popups](/dynamic-maps/popups/).
| `field`         | Twig/PHP             | Address field(s) to be included on the map. (includes all by default)

:::tip Additional Options Details
For more info, please consult either the [JavaScript object](/javascript/dynamicmap.js/#markers-locations-options) or the [Dynamic Map model](/models/dynamic-map-model/#markers-locations-options).
:::

## `style(mapStyle)`

:::code
```js
map.style(mapStyle);
```
```twig
{% do map.style(mapStyle) %}
```
```php
$map->style($mapStyle);
```
:::

#### Arguments

- `mapStyle` (_string_|_object_) - A string specifying which style to use, or a JSON object declaring the map style. Defaults to `streets-v12`.
 
For more information, see the [Styling a Map](/guides/styling-a-map/) guide.

## `zoom(level)`

:::code
```js
map.zoom(level);
```
```twig
{% do map.zoom(level) %}
```
```php
$map->zoom($level);
```
:::

Change the map's zoom level.

#### Arguments

- `level` (_int_) - The new zoom level. Must be an integer between `1` - `22`.
 
:::tip Zoom Level Reference
- `1` is extremely zoomed out, a view of the entire planet.
- `22` is extremely zoomed in, as close to the ground as possible.
:::

## `center(coords)`

:::code
```js
map.center(coords);
```
```twig
{% do map.center(coords) %}
```
```php
$map->center($coords);
```
:::

Re-center the map.

#### Arguments

- `coords` (_[coords](/models/coordinates/)_) - New center point of map.

## `fit(options)`

:::code
```js
map.fit(options);
```
```twig
{% do map.fit(options) %}
```
```php
$map->fit($options);
```
:::

Pan and zoom map to automatically fit all markers within the viewing area. Uses [`fitBounds`](https://docs.mapbox.com/mapbox-gl-js/api/map/#map#fitbounds) internally.

#### Arguments

- `options` (_object_) - Options as defined by the [`fitBounds` documentation](https://docs.mapbox.com/mapbox-gl-js/api/map/#map#fitbounds).

---
---

### Marker ID formula

The remaining methods all refer to a `markerId` value.

:::warning Formula 
The default formula for a `markerId` is as follows:

```js
    '[ELEMENT ID]-[FIELD HANDLE]' // eg: '101-myAddressField'
```
:::

## `panToMarker(markerId)`

:::code
```js
map.panToMarker(markerId);
```
```twig
{% do map.panToMarker(markerId) %}
```
```php
$map->panToMarker($markerId);
```
:::

Re-center map on the specified marker.

#### Arguments

- `markerId` (_string_) - The ID of the marker that you want to pan to.

## `changeMarker(markerId, options)`

:::code
```js
map.changeMarker(markerId, options);
```
```twig
{% do map.changeMarker(markerId, options) %}
```
```php
$map->changeMarker($markerId, $options);
```
:::

Changes the configuration of an existing marker. Rebuilds and replaces the existing marker object.

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.
- `options` (_array_) - Array of any [`Marker` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters).

## `hideMarker(markerId)`

:::code
```js
map.hideMarker(markerId);
```
```twig
{% do map.hideMarker(markerId) %}
```
```php
$map->hideMarker($markerId);
```
:::

Hide a marker. The marker will not be destroyed, it will simply be detached from the map.

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.

## `showMarker(markerId)`

:::code
```js
map.showMarker(markerId);
```
```twig
{% do map.showMarker(markerId) %}
```
```php
$map->showMarker($markerId);
```
:::

Show a marker. The marker will be re-attached to the map.

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all markers.

## `openPopup(markerId)`

:::code
```js
map.openPopup(markerId);
```
```twig
{% do map.openPopup(markerId) %}
```
```php
$map->openPopup($markerId);
```
:::

Open the popup of a specific marker.

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all popups.

## `closePopup(markerId)`

:::code
```js
map.closePopup(markerId);
```
```twig
{% do map.closePopup(markerId) %}
```
```php
$map->closePopup($markerId);
```
:::

Close the popup of a specific marker.

#### Arguments

- `markerId` (_string_|_array_|`'*'`) - A marker ID, array of marker IDs, or `'*'` for all popups.
