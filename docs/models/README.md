---
description:
---

# Models

## Addresses

| Model                                     | Overview
|:------------------------------------------|:---------
| [Address Model](/models/address-model/)   | Used in many places, including [Address fields](/address-field/).
| [Location Model](/models/location-model/) | Parent model of the **Address Model**.

You will frequently encounter an **Address Model**, which is an extension of the **Location Model**. When working with an Address, it's possible to use the properties and methods of both models.

## Dynamic Maps

| Model                                           | Overview
|:------------------------------------------------|:---------
| [Dynamic Map Model](/models/dynamic-map-model/) | Handles creation of [dynamic maps](/dynamic-maps/).

The **Dynamic Map Model** is a powerful [chainable object](/dynamic-maps/chaining/) which can be configured to display each map as desired.

## Plugin Settings

<div class="custom-block warning">
    <p>⚠️&nbsp; You will almost never need to call this model directly.</p>
</div>

| Model                                     | Overview
|:------------------------------------------|:---------
| [Settings Model](/models/settings-model/) | Handles internal plugin settings.

The **Settings Model** handles the internal settings for the entire Mapbox plugin. It is a standard component of many Craft plugins.

---
---
---

# Pseudo-Models

Not quite a Model, **Coordinates** defines a standard format for `coords` values used in various places around the plugin. It's also directly compatible with many facets of the Mapbox API itself.

| Pseudo-Model                        | Overview
|:------------------------------------|:---------
| [Coordinates](/models/coordinates/) | Common configuration of longitude & latitude.
