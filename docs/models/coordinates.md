---
description:
---

# Coordinates

A `coords` value is a basic set of coordinates (longitude & latitude) in a simple JSON format:

:::code
```js JSON
{
    "lng": -64.7527469,
    "lat":  32.3113966
}
```
:::

To be clear, coordinates are not actually a Model (and so this page technically doesn't belong here). But the **format** of `coords` is commonly used throughout the plugin, which gives coordinates a pseudo-model behavior.

## Common Format

A set of coordinates is handled in this common format. Any place you encounter a `coords` value, it will be handled in this format. Here are just a few examples of where you'd see a set of coordinates in this format:

| Where          | Specifically                               |                                                        |
|:---------------|:-------------------------------------------|--------------------------------------------------------|
| Maps           | As the center point of a map.              | [Reference](/dynamic-maps/)                            |
| Popups         | As a predefined value in a popup template. | [Reference](/dynamic-maps/popups/#available-variables) |
| Address Fields | Available on the parent Location Model.    | [Reference](/models/location-model/#coords)            |
 
## Mapbox Coordinates

The internal format of `coords` aligns with the established format of a [Mapbox `LngLat` object](https://docs.mapbox.com/mapbox-gl-js/api/geography/#lnglat). Since the two formats are effectively identical, you can use the values interchangeably. 

:::tip Nearly Identical
Roughly speaking, you can say that the Mapbox plugin "uses" `LngLat` to handle coordinates internally. There may be some subtle differences, but you can generally treat them as the same thing and not encounter any issues.
:::
