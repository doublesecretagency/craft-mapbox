---
description: Clicking on a marker can trigger a popup based on your own custom Twig template. Find out how to add popups to a dynamic map.
---

# Popups

A [popup](https://docs.mapbox.com/mapbox-gl-js/example/popup/) is the formal name for the bubble that pops up when you click on a marker...

<p align="center">
    <img width="704" class="dropshadow" :src="$withBase('/images/maps/popup.png')" alt="Example of a popup">
</p>

In order to add popups to your markers you'll want to specify a `popupTemplate` value. If needed, you can also specify any desired `popupOptions`.

The popup can be created using either the [map](/models/dynamic-map-model/#dynamic-map-options) or [markers](/models/dynamic-map-model/#markers-locations-options) parameters.

:::code
```twig
{% set options = {
    'popupTemplate': 'example/my-popup'
} %}

{% set map = mapbox.map(locations, options) %}
```
```php
$options = [
    'popupTemplate' => 'example/my-popup'
];

$map = Mapbox::map($locations, $options);
```
:::

The Twig template can live anywhere you want within your templates folder.

## Twig Template Example

The following snippet produced the screenshot shown above (using Tailwind CSS):

:::code
```twig example/my-popup.twig
{# Get the entry's thumbnail image #}
{% set image = entry.thumbnail.one() %}

{# This example uses Tailwind CSS #}
<div class="pl-1 pt-1">

    {# Show thumbnail if it exists #}
    {% if image %}
        <img
            alt="Thumbnail image"
            src="{{ image.url }}"
            style="max-width:100%"
            class="rounded border border-gray-500"
        >
    {% endif %}

    {# Show entry and address information #}
    <div class="px-1 pb-1">
        <div class="pt-2 text-gray-800 text-xl font-bold">{{ entry.title }}</div>
        <div class="pt-1 text-gray-800 text-base">{{ address }}</div>
        <div class="pt-2 text-base">
            <a class="text-blue-700" href="{{ entry.url }}">More Information</a>
        </div>
    </div>

</div>
```
:::

Within the context of a popup Twig template, a few magic variables will already be set. Depending on which type of value you provided for the [locations](/dynamic-maps/locations/) parameter, some of these variables may or may not be available to you.

For example, if you specify `locations` as a simple set of **coordinates**, you will only have access to those coordinates and the map ID. But if you specify a full **element**, you will have access to every available variable within a popup.

## Available Variables

Depending on the context of the marker, certain variables will (or won't) be automatically available in your popup template. It depends entirely on **what type of entity created the marker**. The marker could have been created by any of the following types of entities:

 - A normal Entry (or any other Element Type)
 - An [Address Model](/models/address-model/)
 - A simple set of [coordinates](/models/coordinates/)

Keep reading to see which variable are available in which contexts.

### Variables for All Popups

The following variables will be automatically available to all popups...

| Variable | Description
|:---------|:------------
| `mapId`  | ID of the map which contains this marker.
| `coords` | [Coordinates](/models/coordinates/) of this particular marker.

### Variables for Element-based Popups

If the map markers were generated from complete elements, the following variables will also be available...

| Variable   | Description
|:-----------|:------------
| `markerId` | ID of the marker being placed onto the map.
| `element`  | The full element responsible for creating the marker.
| `address`  | An [Address Model](/models/address-model/) derived from the element's [Address Field](/address-field/).

:::warning Additional Element Type Variables
For each element type, the `element` variable will automatically be aliased for that particular element type.

```twig
    element === entry  {# If marker is an Entry #}
    element === asset  {# If marker is an Asset #}
    element === user   {# If marker is a User #}
    
    ... and so on
```

This applies to _all_ element types, including 3rd-party element types.
:::

## Popup Template Errors

In the event of an error in your Twig code, an error message will be thrown. The message will be contained within the popup itself, in order to streamline debugging.

<p align="center">
    <img width="704" class="dropshadow" :src="$withBase('/images/maps/popup-error.png')" alt="Example of a popup Twig template error">
</p>

The popup will display the template path, alongside the error message being returned.
