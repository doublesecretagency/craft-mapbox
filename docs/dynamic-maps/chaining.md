---
description: Add layers of complexity by chaining commands together. The same set of chainable commands are nearly identical across JavaScript, Twig, and PHP.
---

# Chaining

## What & Why

**Chaining** refers to the ability to run multiple commands on a map sequentially...

:::code
```js
const map = mapbox.map(locations)
    .style(mapStyle)
    .center(coords)
    .zoom(level);
```
```twig
{% set map = mapbox.map(locations)
    .style(mapStyle)
    .center(coords)
    .zoom(level) %}
```
```php
$map = Mapbox::map($locations)
    ->style($mapStyle)
    ->center($coords)
    ->zoom($level);
```
:::

To ensure that our maps are as dynamic as possible, there are a [series of methods](/dynamic-maps/universal-methods/) which can be chained together, in any order you'd like.

## Starting a Chain

A chain must always **begin** with the creation of a map object. No matter how you intend to decorate your dynamic map, it will always start the same way...

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
 
Or if you want to reference a map that has already been created, you can retrieve it like this...

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

:::warning Two flavors of "map object"
Internally, there are really two different things that are being referred to as the "map object".

- In Twig/PHP, it's a [Dynamic Map Model](/models/dynamic-map-model/).
- In JavaScript, it's a [`DynamicMap` model](/javascript/dynamicmap.js/).

 The internal structure of this object varies greatly between JavaScript and Twig/PHP, but fortunately, you don't need to worry about the difference at all. The API has been designed to make usage nearly identical, regardless of language.
:::

## Ending a Chain

Not all chains need to be concluded right away... you may sometimes find it helpful to keep a chain alive long enough to perform more operations on the map object. Eventually though, you'll probably want to end the chain.

To end the chain, apply the `tag` method to wrap it all up. Depending on which language you are working in, you'll notice properties unique to that context.

### Twig

In Twig, the `tag` method [renders a finished map](/dynamic-maps/twig-php-methods/#tag-options).

:::code
```twig
{# Renders a map #}
{{ map.tag() }}
```
:::

### PHP

In PHP, the `tag` method [creates a new `Twig\Markup` object](/dynamic-maps/twig-php-methods/#tag-options).

:::code
```php
// Creates a new Twig\Markup object
$twigMarkup = $map->tag();
```
:::

### JavaScript

In JavaScript, the `tag` method [creates a new HTML element](/dynamic-maps/javascript-methods/#tag-options).

There are three (roughly equal) ways to add a map to the DOM...
:::code
```js Specify parent ID
// Inject map directly into the DOM
map.tag({'parentId': 'my-map-container'});
```
```js Use ID from options
// Inject map into the DOM using "id" specified by options
map.tag();
```
```js Create detached element
// Create a new HTML element, detached from the DOM
const mapDiv = map.tag();
```
:::

:::warning Multiple ways to set the DOM element
You may omit the `parentId` if a map `id` was [already specified](/dynamic-maps/basic-map-management/#dynamic-map-options).

When the map is created, specify the DOM element `id`.  It must match an existing, empty `<div>` element.
:::
