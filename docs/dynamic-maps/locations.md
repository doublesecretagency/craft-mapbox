---
description: You'll often need to specify "locations" when creating maps. But what does that mean? Check out this complete breakdown of accepted types of locations.
---

# Locations

When working with [dynamic maps](/models/dynamic-map-model/), you'll need to specify the `locations` that you want to appear on the map. You'll most often encounter this parameter when you [create a map](/dynamic-maps/basic-map-management/#map-locations-options) or [add markers](/dynamic-maps/universal-methods/#markers-locations-options) to a map. 

What are "locations"? They can be any of the following...
 - [Coordinates](#coordinates) (simple JSON)
 - [Addresses](#address-models) (from an Address field)
 - [Elements](#elements) (like Entries, Users, etc)

## Coordinates

The `locations` value can be a set of [coordinates](/models/coordinates/), or an array of coordinate sets.

:::code
```js
// Just one set of coordinates
const locations = {
    'lng': -64.7527469,
    'lat':  32.3113966
};

// An array of coordinate sets
const locations = [
    {'lng': -115.7930198, 'lat': 37.2430548},
    {'lng':   -4.4496567, 'lat': 57.3009274}
];
```
```twig
{# Just one set of coordinates #}
{% set locations = {
    'lng': -64.7527469,
    'lat':  32.3113966
} %}

{# An array of coordinate sets #}
{% set locations = [
    {'lng': -115.7930198, 'lat': 37.2430548},
    {'lng':   -4.4496567, 'lat': 57.3009274}
] %}
```
```php
// Just one set of coordinates
$locations = [
    'lng' => -64.7527469,
    'lat' =>  32.3113966
];

// An array of coordinate sets
$locations = [
    ['lng' => -115.7930198, 'lat' => 37.2430548],
    ['lng' =>   -4.4496567, 'lat' => 57.3009274]
];
```
:::

:::warning In JavaScript, you may only use Coordinates
If you are working in JavaScript, then your only option is to work with coordinates. It is physically impossible to translate Address Models and Elements into JavaScript.
:::

---
---

## Address Models

The `locations` value can be set as an individual [Address Model](/models/address-model/), or as an array of Address Models.

:::code
```twig
{# Just one Address Model #}
{% set locations = entry.myAddressField %}

{# An array of Address Models #}
{% set locations = [
    entry.homeAddress,
    entry.businessAddress
] %}
```
```php
// Just one Address Model
$locations = $entry->myAddressField;

// An array of Address Models
$locations = [
    $entry->homeAddress,
    $entry->businessAddress
];
```
:::

---
---

## Elements

The `locations` value can be set as an individual [Element](https://craftcms.com/docs/4.x/elements.html), or as an array of Elements. This can include any native Element Types (ie: Entries, Categories, Users, etc), as well as any custom Element Types introduced by other plugins or modules.

:::code
```twig
{# Just one Element #}
{% set locations = entry %}

{# An array of Elements #}
{% set locations = craft.entries.all() %}
```
```php
// Just one Element
$locations = $entry;

// An array of Elements
$locations = Entry::find()->all();
```
:::

:::tip Elements must have a valid Address field
When using an Element, ensure that it has at least one Address field attached to it. The plugin will automatically render each valid Address field on the map.
:::
