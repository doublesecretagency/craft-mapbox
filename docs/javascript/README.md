---
description:
---

# `mapbox` in JavaScript

When working with [dynamic maps](/dynamic-maps/), there are two JavaScript files which are automatically loaded into the front-end (although this can be [disabled](/guides/required-js-assets/)) whenever a map is included on the page. The files will be copied, and loaded from the public `cpresources` folder.

```
/web/cpresources/{hash}/js/
   - dynamicmap.js
   - mapbox.js
```

The `mapbox.js` file is the [main entry point](/javascript/mapbox.js/). It allows you to create a new map, load an existing map, or initialize one or more map containers. The globally-accessible `mapbox` JavaScript object will be automatically preloaded by this file.

The `dynamicmap.js` file contains a [`DynamicMap` JavaScript model](/javascript/dynamicmap.js/), which is used to generate individual `DynamicMap` objects for each map. Each one is a chainable instance of a fully functional Mapbox Map.

:::warning Defer to mapbox.js
You will virtually never need to interact with the `DynamicMap` model directly. Use the `mapbox` object to create (or access) a `DynamicMap` model, then simply chain methods from within the `DynamicMap` model.
:::
