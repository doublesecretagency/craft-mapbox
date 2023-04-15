---
description:
---

# Opening Popups

If your map includes [popups](/dynamic-maps/popups/), users will typically open them by clicking on each corresponding marker. Sometimes, however, you'll want something _outside_ of the map to open a popup.

The snippet below includes three parts:

1. A **dynamic map**, containing markers for all entries.
2. A **list of entries**. Clicking on each title will open its corresponding popup on the map.
3. The **JavaScript function** which handles switching between popups.

```twig
{# Configure the map options #}
{% set options = {
    'id': 'my-map',
    'popupTemplate': 'example/my-popup'
} %}

{# Display a dynamic map with all entries #}
{{ mapbox.map(entries, options).tag() }}

{# Loop through all entries #}
{% for entry in entries %}

    {# Compile each marker ID #}
    {% set markerId = "#{entry.id}-myAddressField" %}

    {# On click, open the popup of the specified marker #}
    <div onclick="popup('{{ markerId }}')">
        {{ entry.title }}
    </div>

{% endfor %}

{# JavaScript function to switch popups #}
<script>
    function popup(markerId) {
        mapbox.getMap('my-map')   // Get the map
            .closePopup('*')      // Close all popups
            .openPopup(markerId); // Open the specified popup
    }
</script>
```

The `openPopup` and `closePopup` methods are available in JS, Twig, and PHP.

Usage is nearly identical across all three languages, although you will have more versatility when using it in **JavaScript** (since Twig and PHP are only relevant when the map is first loaded).

:::tip Further Reading
For more information, see the [`openPopup` and `closePopup` universal methods...](/dynamic-maps/universal-methods/#openpopup-markerid)
:::
