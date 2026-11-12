---
description:
---

# Showing Map Controls

By default, a dynamic map has no controls. You can add zoom buttons, a fullscreen button, a scale bar, or a button to find the visitor's location, by switching them on in the [`mapOptions` parameter](/dynamic-maps/basic-map-management/#dynamic-map-options).

:::code
```js
const options = {
    'mapOptions': {
        'navigationControl': true,
        'fullscreenControl': true,
        'scaleControl': true,
        'geolocateControl': true
    }
};

const map = mapbox.map(locations, options);
```
```twig
{% set options = {
    'mapOptions': {
        'navigationControl': true,
        'fullscreenControl': true,
        'scaleControl': true,
        'geolocateControl': true
    }
} %}

{% set map = mapbox.map(locations, options) %}
```
```php
$options = [
    'mapOptions' => [
        'navigationControl' => true,
        'fullscreenControl' => true,
        'scaleControl' => true,
        'geolocateControl' => true
    ]
];

$map = Mapbox::map($locations, $options);
```
:::

Include only the controls you want. Each one is off unless you switch it on.

| Option              | Control
|:--------------------|:--------
| `navigationControl` | Zoom buttons and a compass. ([options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#navigationcontrol))
| `fullscreenControl` | A button to view the map in fullscreen. ([options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#fullscreencontrol))
| `scaleControl`      | A scale bar. ([options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#scalecontrol))
| `geolocateControl`  | A button to find the visitor's location. ([options](https://docs.mapbox.com/mapbox-gl-js/api/markers/#geolocatecontrol))

## Customizing a Control

Instead of `true`, you can pass an object of that control's options. Add a `position` to move it to a different corner of the map (`top-left`, `top-right`, `bottom-left`, or `bottom-right`).

:::code
```js
const options = {
    'mapOptions': {
        'navigationControl': {
            'showCompass': false,
            'position': 'top-left'
        }
    }
};
```
```twig
{% set options = {
    'mapOptions': {
        'navigationControl': {
            'showCompass': false,
            'position': 'top-left'
        }
    }
} %}
```
```php
$options = [
    'mapOptions' => [
        'navigationControl' => [
            'showCompass' => false,
            'position' => 'top-left'
        ]
    ]
];
```
:::
