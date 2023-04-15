---
description:
---

# Required JS Assets

:::tip Dynamic Maps Only
This guide is only relevant if you are working with [dynamic maps](/dynamic-maps/). The JavaScript assets described below are not relevant in any other context.
:::

To create and manage embedded maps, a few external JavaScript files (and one CSS file) will be required. It is also necessary for us to load the Mapbox JavaScript API.

Combined, they produce an HTML snippet similar to this...

```html
<link href="https://api.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.css" rel="stylesheet">
<script src="https://api.mapbox.com/mapbox-gl-js/v2.13.0/mapbox-gl.js"></script>
<script src="https://yourwebsite.com/cpresources/[HASH]/js/mapbox.js"></script>
<script src="https://yourwebsite.com/cpresources/[HASH]/js/dynamicmap.js"></script>
```

:::warning Not seeing these lines in your source code?
Make sure that the `<html>`, `<head>`, and `<body>` tags are properly formed (including their respective closing tags). Craft requires those HTML tags to be correctly formed, in order to inject the necessary JavaScript libraries.
:::

If the map is being rendered by Twig or PHP, these files will be [automatically](#loaded-automatically) loaded for you. However, if the map is being rendered exclusively via JavaScript, you will be responsible for loading these JS files [manually](#loaded-manually).

For various reasons, you may not want these files to be loaded automatically. It's possible to [suppress](#disable-automatic-loading) the initial automatic loading of these assets, then manually load them later.

## Loaded Automatically

In addition to the Mapbox API itself, there are two other [JavaScript files](/javascript/) which are required whenever a dynamic map is present. For your convenience, these files will be loaded into the page automatically when rendering a map in Twig or PHP.

:::warning No autoloading for JavaScript-only maps
If your map is being rendered exclusively in JavaScript, the required assets will _not_ be loaded automatically. For purely JS maps, ensure that you load the required assets [manually](#loaded-manually).
:::

## Disable Automatic Loading

If necessary, you can prevent the required assets from being loaded automatically.  When the `tag` method is appended, simply set the `assets` value to `false`.

:::code
```twig
{# Bypass default asset loading #}
{{ map.tag({'assets': false}) }}
```
```php
// Bypass default asset loading
$twigMarkup = $map->tag(['assets' => false]);
```
:::

This will prevent the assets from being automatically injected into your HTML.

## Loaded Manually

If you are not automatically loading the required assets, you will instead be responsible for loading them manually. There are several ways to approach this, how you choose to go about it is up to you.

The simplest approach is to use to the [`loadAssets` method](/helper/dynamic-maps/#loadassets-service-params).

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

You'll need to specify which API service is being loaded (either `maps` or `search`).

If you really don't want Twig/PHP to load the assets on your behalf, and are determined to take matters into your own hands, there is one other tool available to you.

The [`getAssets` method](/helper/dynamic-maps/#getassets-service-params) will retrieve the list of required JS assets as an _array of required URLs_. Once you have those URLs, you are free to load them into the page as you see fit.

:::code
```twig
{# Get an array of required URLs #}
{% set assets = mapbox.getAssets('maps') %}
```
```php
// Get an array of required URLs
$assets = Mapbox::getAssets('maps');
```
:::

:::warning Further Reading
For more information, see the [`loadAssets` method](/helper/dynamic-maps/#loadassets-service-params) and [`getAssets` method](/helper/dynamic-maps/#getassets-service-params).
:::
