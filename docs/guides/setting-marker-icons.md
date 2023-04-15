---
description: You can easily set custom marker icons when creating a map or adding a new marker. It's also a breeze to change icons of existing markers.
---

# Setting Marker Icons

There are two opportunities to set a marker icon:

- When the marker is created (using the [`markers` method](/dynamic-maps/universal-methods/#markers-locations-options) or [`map` method](/dynamic-maps/basic-map-management/#map-locations-options))
- After the marker already exists (using the [`changeMarker` method](/dynamic-maps/universal-methods/#changemarker-markerid-options))

## Creating Marker Icons

When the map is initially created, or markers are initially placed, you can use the `markerOptions` option to control how the marker looks and behaves.

If you've got multiple groups of markers, you can specify different marker options for each batch of markers.

:::code
```js
// Get all bars & restaurants
const bars        = {'lng':  -73.935242, 'lat': 40.730610}; // Coords only in JS
const restaurants = {'lng': -118.243683, 'lat': 34.052235}; // Coords only in JS

// Create a dynamic map (with no markers)
const map = mapbox.map();

// Add all bar markers (blue)
map.markers(bars, {
    'markerOptions': {'color': '#197BBD'}
});

// Add all restaurant markers (red)
map.markers(restaurants, {
    'markerOptions': {'color': '#BE3100'}
});

// Display map (inject into `#my-map-container`)
map.tag({'parentId': 'my-map-container'});
```
```twig
{# Get all bars & restaurants #}
{% set bars        = craft.entries.section('locations').type('bars').all() %}
{% set restaurants = craft.entries.section('locations').type('restaurants').all() %}

{# Create a dynamic map (with no markers) #}
{% set map = mapbox.map() %}

{# Add all bar markers (blue) #}
{% do map.markers(bars, {
    'markerOptions': {'color': '#197BBD'}
}) %}

{# Add all restaurant markers (red) #}
{% do map.markers(restaurants, {
    'markerOptions': {'color': '#BE3100'}
}) %}

{# Display map #}
{{ map.tag() }}
```
```php
// Get all bars & restaurants
$bars        = Entry::find()->section('locations')->type('bars')->all();
$restaurants = Entry::find()->section('locations')->type('restaurants')->all();

// Create a dynamic map (with no markers)
$map = Mapbox::map();

// Add all bar markers (blue)
$map->markers($bars, [
    'markerOptions' => ['color' => '#197BBD']
]);

// Add all restaurant markers (red)
$map->markers($restaurants, [
    'markerOptions' => ['color' => '#BE3100']
]);

// Display map
$twigMarkup = $map->tag();
```
:::

## Changing Marker Icons

It's also possible to change the options of an existing marker. The [`changeMarker` method](/dynamic-maps/universal-methods/#changemarker-markerid-options) accepts all the same [`Marker` parameters](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters) as the initial map or marker creation.

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

Using the `changeMarker` method, it's possible to change [one or more](/dynamic-maps/universal-methods/#changemarker-markerid-options) markers at a time.

:::code
```js
// Change one marker
map.changeMarker('33-address', {'color': '#197BBD'});

// Change specified markers
map.changeMarker(['33-address', '42-address'], {'color': '#197BBD'});

// Change all markers
map.changeMarker('*', {'color': '#197BBD'});
```
```twig
// Change one marker
{% do map.changeMarker('33-address', {'color': '#197BBD'}) %}

// Change specified markers
{% do map.changeMarker(['33-address', '42-address'], {'color': '#197BBD'}) %}

{# Change all markers #}
{% do map.changeMarker('*', {'color': '#197BBD'}) %}
```
```php
// Change one marker
map.changeMarker('33-address', ['color' => '#197BBD']);

// Change specified markers
map.changeMarker(['33-address', '42-address'], ['color' => '#197BBD']);

// Change all markers
$map->changeMarker('*', ['color' => '#197BBD']);
```
:::

:::tip Getting the Marker IDs
To see the existing marker IDs (if you didn't manually specify them), do the following:

1. Put the site into [devMode](https://craftcms.com/docs/4.x/config/config-settings.html#devmode).
2. View the JS console while the map is being rendered.

In the JavaScript console, you should see a complete play-by-play of every map component being created. Simply copy & paste the marker ID's you need from there, or take note of the pattern for your own needs.
:::
