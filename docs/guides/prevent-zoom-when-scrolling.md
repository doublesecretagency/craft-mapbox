---
description:
---

# Prevent Zoom When Scrolling

It is a common frustration that a map will zoom in or out as you scroll past it. This is a native behavior of Mapbox, but it can be managed via the `scrollZoom` option of the [`mapOptions` parameter](/dynamic-maps/basic-map-management/#dynamic-map-options).

:::code
```js
const options = {
    'mapOptions': {
        'scrollZoom': false
    }
};

const map = mapbox.map(locations, options);
```
```twig
{% set options = {
    'mapOptions': {
        'scrollZoom': false
    }
} %}

{% set map = mapbox.map(locations, options) %}
```
```php
$options = [
    'mapOptions' => [
        'scrollZoom' => false
    ]
];

$map = Mapbox::map($locations, $options);
```
:::

:::tip More Information
For more info, see [the `mapOptions` parameter](/dynamic-maps/basic-map-management/#dynamic-map-options)
:::
