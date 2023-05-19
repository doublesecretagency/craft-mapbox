---
description: The basic tools for dynamic map management can be used equally across JavaScript, Twig, and PHP. From here, chain other commands for a more complex map.
---

# Basic Map Management

**Everything starts here.** In order to apply a [chain](/dynamic-maps/chaining/) of customizations, you must first create a map object using the `map` method, or retrieve an existing map object using the `getMap` method.

Once you've got a map object, you will be able to apply any of the [Universal Methods](/dynamic-maps/universal-methods/) available.

:::warning Chainable Map Object
Both of these methods return a chainable **map object**. This makes it possible to string together as many of the following methods as needed, in any order you prefer.

Each language has a special command for rendering the map, and thus ending the chain. Make note of how to end the chain for whichever language you are working in.
:::

## `map(locations, options)`

Use the `map` method to create a new map object.

:::code
```js
const map = mapbox.map(locations, options);
```
```twig
{% set map = mapbox.map(locations, options) %}
```
```php
$map = Mapbox::map($locations, $options);
```
:::

#### Arguments

 - `locations` (_[locations](/dynamic-maps/locations/)_) - Will be used to create markers for the map.
 - `options` (_array_|_null_) - The `options` parameter allows you to configure the map in greater detail.
 
:::tip Available Options
Most (though not all) of these options are available across JavaScript, Twig, and PHP. Please note the few options which are not universally available.
:::

### Dynamic Map Options
 
| Option          | Available   | Description
|-----------------|:-----------:|-------------
| `id`            | JS/Twig/PHP | Set the `id` attribute of the map container.
| `width`         | JS/Twig/PHP | Set the width of the map (in px).
| `height`        | JS/Twig/PHP | Set the height of the map (in px).
| `zoom`          | JS/Twig/PHP | Set the default zoom level of the map. <span style="white-space:nowrap">(`1`-`22`)</span>
| `center`        | JS/Twig/PHP | Set the center position of the map.
| `style`         | JS/Twig/PHP | A string or object declaring the [map style](/guides/styling-a-map/).
| `mapOptions`    | JS/Twig/PHP | Accepts any [`Map` parameters](https://docs.mapbox.com/mapbox-gl-js/api/map/#map-parameters).
| `markerOptions` | JS/Twig/PHP | Accepts any [`Marker` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters)
| `popupOptions`  | JS/Twig/PHP | Accepts any [`Popup` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#popup-parameters)
| `popupTemplate` | Twig/PHP    | Template path to use for creating [popups](/dynamic-maps/popups/).
| `field`         | Twig/PHP    | Address field(s) to be included on the map. (includes all by default)

:::warning Additional Details
For more info, please consult either the [JavaScript method](/javascript/mapbox.js/#map-locations-options) or the [Twig/PHP constructor](/models/dynamic-map-model/#construct-locations-options).
:::

---
---

## `getMap(mapId)`

Retrieve an existing map object. The `mapId` value would have been set when the map was originally created. If no ID was specified at the time of creation, the map will have a randomly generated ID.

:::code
```js
const map = mapbox.getMap(mapId);
```
```twig
{% set map = mapbox.getMap(mapId) %}
```
```php
$map = Mapbox::getMap($mapId);
```
:::

#### Arguments

 - `mapId` (_string_) - The ID of the map that you want to access.
