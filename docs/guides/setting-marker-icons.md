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
map.changeMarker(markerId, markerOptions);
```
```twig
{% do map.changeMarker(markerId, markerOptions) %}
```
```php
$map->changeMarker($markerId, $markerOptions);
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

## Image as Marker Icon

When you need more control than just changing the marker's color, it's possible to use your own `<img>` (or other HTML) for custom marker icons.

<img class="dropshadow" :src="$withBase('/images/guides/custom-marker-icons.png')" alt="Screenshot of map with custom marker icons" width="710" style="margin-top:10px">

1. Start by adding the marker icon element to the DOM...

```html
<div style="display: none">
    <img id="custom-marker-icon" src="path/to/icon/image.png">
</div>
```

:::warning Element must exist, but can be hidden
The specified element must exist in the DOM before the map is rendered.

However, you can safely store it within a hidden (`display: none`) container.
:::

2. When adding `markers`, specify the existing DOM element within the [`markerOptions` option](/dynamic-maps/universal-methods/#markers-locations-options).

Use the `element` parameter to reference the DOM element by its `id`...

:::code
```js
const markerOptions = {
    'element': '#custom-marker-icon',
    'anchor': 'bottom'
}
```
```twig
{% set markerOptions = {
    'element': '#custom-marker-icon',
    'anchor': 'bottom'
} %}
```
```php
$markerOptions = [
    'element' => '#custom-marker-icon',
    'anchor' => 'bottom'
];
```
:::

:::tip Anchor
You will likely also need to specify the [`anchor` parameter](https://docs.mapbox.com/mapbox-gl-js/api/markers/#marker-parameters). By default, Mapbox will try to align the _center_ of your icon with its corresponding coordinates.
:::

---
---

### JavaScript Only

If you are working with JavaScript, you can potentially create a DOM element on the fly.

Use JavaScript to create a new dynamic DOM element, then pass in the entire `element`.

:::code
```js
// Dynamically create an image element
const img = document.createElement('img');
img.src = 'path/to/icon/image.png';

// Use the new element as the marker icon
const markerOptions = {
    'element': img,
    'anchor': 'bottom'
}
```
:::
