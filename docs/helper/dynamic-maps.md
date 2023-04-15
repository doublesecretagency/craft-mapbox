---
description:
---

# Dynamic Maps

## map(locations = [], options = {})

**Central to the creation of all dynamic maps.** Use this method to create a new map, before further manipulating the map object. See the [complete method details...](/dynamic-maps/basic-map-management/#map-locations-options)

:::code
```twig
{# Generate a new dynamic map #}
{% set map = mapbox.map(locations) %}
```
```php
// Generate a new dynamic map
$map = Mapbox::map($locations);
```
:::

## getMap(mapId)

Call up an existing map using this method. Once you've retrieved the map object, you are free to manipulate it normally. See the [complete method details...](/dynamic-maps/basic-map-management/#getmap-mapid)

:::code
```twig
{# Retrieve an existing dynamic map #}
{% set map = mapbox.getMap(mapId) %}
```
```php
// Retrieve an existing dynamic map
$map = Mapbox::getMap($mapId);
```
:::

---
---

:::warning Manually Loading Assets
The methods below (`getAssets` and `loadAssets`) are only relevant if you are preventing the required JS files from being loaded automatically. See more about [loading assets...](/guides/required-js-assets/)
:::

## getAssets(service, params = {})

Get a list of required JavaScript assets necessary to render dynamic maps.

The `service` value specifies which API URL to use. It may be set to either `maps` or `search`.

Optionally add `params` to the Mapbox API URL.

:::code
```twig
{# Get an array of required JS and/or CSS files #}
{% set assets = mapbox.getAssets('maps') %}
```
```php
// Get an array of required JS and/or CSS files
$assets = Mapbox::getAssets('maps');
```
:::

## loadAssets(service, params = {})

Load all required JavaScript assets necessary to render dynamic maps.

The `service` value specifies which API URL to use. It may be set to either `maps` or `search`.

Optionally add `params` to the Mapbox API URL.

:::code
```twig
{# Load the required JS and/or CSS files #}
{% do mapbox.loadAssets('maps') %}
```
```php
// Load the required JS and/or CSS files
Mapbox::loadAssets('maps');
```
:::

---
---

:::warning More Info
For more information, check out the documentation on [Dynamic Maps](/dynamic-maps/).
:::
