---
description: Make your maps blend seamlessly with any website, by easily adding your own custom style.
meta:
- property: og:type
  content: website
- property: og:url
  content: https://plugins.doublesecretagency.com/mapbox/guides/styling-a-map/
- property: og:title
  content: Styling a Map | Mapbox plugin for Craft CMS
- property: og:description
  content: Make your maps blend seamlessly with any website, by easily adding your own custom style.
- property: og:image
  content: https://plugins.doublesecretagency.com/mapbox/images/meta/styling-a-map.png
- property: twitter:card
  content: summary_large_image
- property: twitter:url
  content: https://plugins.doublesecretagency.com/mapbox/guides/styling-a-map/
- property: twitter:title
  content: Styling a Map | Mapbox plugin for Craft CMS
- property: twitter:description
  content: Make your maps blend seamlessly with any website, by easily adding your own custom style.
- property: twitter:image
  content: https://plugins.doublesecretagency.com/mapbox/images/meta/styling-a-map.png
---

# Styling a Map

Mapbox makes it simple to specify which map style you would like to use, or even generate your own custom style. Your style can be applied when creating the map, or set later using the `style` method.

**Acceptable formats for the `mapStyle` parameter:**
- A native style name only, without the URL prefix (eg: `satellite-v9`).
- A full URL to a Mapbox style (eg: `mapbox://styles/mapbox/satellite-v9`).
- A JSON object conforming to the [Mapbox Style Specification](https://docs.mapbox.com/mapbox-gl-js/style-spec/).

### Pre-made styles

If you'd like a ready-to-go style, select one from the list of [native Mapbox styles...](https://docs.mapbox.com/api/maps/styles/#mapbox-styles)

:::warning Omit base URL of native styles
If using one of the built-in [Mapbox styles](https://docs.mapbox.com/api/maps/styles/#mapbox-styles), you can omit the base URL and specify only the style name itself (eg: `satellite-v9`).
:::

### Custom styles

Or if you'd prefer, you can use Mapbox Studio to [create your own custom style...](https://docs.mapbox.com/studio-manual/reference/styles/)

## Styling a new map

You can specify the `style` option when you create the initial map...

:::code
```js
// Use a native Mapbox style
const mapStyle = 'satellite-streets-v12';

// Apply style to the map
const map = mapbox.map(locations, {
    'style': mapStyle
});
```
```twig
{# Use a native Mapbox style #}
{% set mapStyle = 'satellite-streets-v12' %}

{# Apply style to the map #}
{% set map = mapbox.map(locations, {
    'style': mapStyle
}) %}
```
```php
// Use a native Mapbox style
$mapStyle = 'satellite-streets-v12';

// Apply style to the map
$map = Mapbox::map($locations, [
    'style' => $mapStyle
]);
```
:::

## Styling an existing map

You can also change the style of an existing map...

:::code
```js
// Get an existing map
const map = mapbox.getMap(mapId);

// Style the map
map.style(mapStyle);
```
```twig
{# Get an existing map #}
{% set map = mapbox.getMap(mapId) %}

{# Style the map #}
{% do map.style(mapStyle) %}
```
```php
// Get an existing map
$map = Mapbox::getMap($mapId);

// Style the map
$map->style($mapStyle);
```
:::
