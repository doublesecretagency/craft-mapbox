---
description:
---

# Location Model

The [Address Model](/models/address-model/) is an extension of the Location Model.

## Public Properties

### `lng`

_float_ - Longitude of location.

### `lat`

_float_ - Latitude of location.

### `coords`

_object_ - Alias for `getCoords()`.

## Public Methods

### `hasCoords()`

Determine whether the location has valid coordinates.

```twig
{% if location.hasCoords() %}
    Longitude: {{ location.lng }}
    Latitude:  {{ location.lat }}
{% endif %}
```

**Returns**

- _bool_ - Whether the location has functional coordinates.

### `getCoords()`

Get the coordinates of a location.

**Returns**

- _object_ - Get the location coords as a [coordinates](/models/coordinates/) JSON object.

### `getDistance(location, units = 'miles')`

Get the distance between two points.

```twig
{% set distance = entry.homeAddress.getDistance(entry.businessAddress) %}
```

**Arguments**

- `$location` (_mixed_) - A [set of coordinates](/models/coordinates/), or separate Location Model (or [Address](/models/address-model/) model).
- `$units` (_string_) - Unit of measurement (`mi`,`km`,`miles`,`kilometers`).

**Returns**

- _float_|_null_ - Calculates the distance between the two points.
