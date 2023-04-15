---
description:
---

# Mapbox API

## getApiUrl(service, params = {})

Get the URL used internally for pinging the Mapbox JavaScript API.

The `service` value specifies which API URL to use. It may be set to either `maps` or `search`.

Optionally add `params` to the Mapbox API URL.

:::code
```twig
{% set apiUrl = mapbox.getApiUrl('maps') %}
```
```php
$apiUrl = Mapbox::getApiUrl('maps');
```
:::

## getAccessToken()

Get the access token.

:::code
```twig
{% set accessToken = mapbox.getAccessToken() %}
```
```php
$accessToken = Mapbox::getAccessToken();
```
:::

## setAccessToken(token)

Override the access token.

:::code
```twig
{% do mapbox.setAccessToken('YOUR_ACCESS_TOKEN') %}
```
```php
Mapbox::setAccessToken('YOUR_ACCESS_TOKEN');
```
:::
