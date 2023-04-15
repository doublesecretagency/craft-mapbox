---
description: Here's a basic example of how you might use an Address field in a Twig template. There's a lot more that's possible, we're just scratching the surface!
---

# Using an Address in Twig

## Example

This is a very simple example of what is possible with the Address field...

```twig
{# Show a formatted address #}
{{ address.multiline() }}

{# Display map with a marker #}
{{ mapbox.map(address).tag() }}
```

<img class="dropshadow" :src="$withBase('/images/address-field/basic-example.png')" alt="Example using the address of the Empire State Building" width="600">

## Breaking it down

Each Address field returns an [Address Model](/models/address-model/), which is extremely powerful and flexible. The example above is relying on several distinct features of the Address Model...

 - We used [`multiline`](/models/address-model/#multiline-maxlines-3) to display a complete address, formatted across multiple lines.
 - We used [`mapbox.map`](/dynamic-maps/basic-map-management/#map-locations-options) to show the location as a marker on a map.
 
To get a better understanding of the various methods available, take a look at the [Address Model](/models/address-model/) documentation.

To learn how to render a map, check out the [Dynamic Maps](/dynamic-maps/) documentation to see what is possible.
