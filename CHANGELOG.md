# Changelog

## Unreleased

### Added
- Can now select which subfields will use autocomplete.
- Addresses now include an optional [`name`](https://plugins.doublesecretagency.com/mapbox/models/address-model/#name) subfield. ([#4](https://github.com/doublesecretagency/craft-mapbox/issues/4))
- Addresses now include an optional [`neighborhood`](https://plugins.doublesecretagency.com/mapbox/models/address-model/#neighborhood) subfield.
- Addresses now include an optional [`county`](https://plugins.doublesecretagency.com/mapbox/models/address-model/#county) subfield.
- Addresses now include an optional [`mapboxId`](https://plugins.doublesecretagency.com/mapbox/models/address-model/#mapboxid) subfield.
- Address fields will now auto-detect the language of the currently logged-in user.
- Address fields' language can be manually overridden with the new [`fieldParams`](https://plugins.doublesecretagency.com/mapbox/getting-started/config/#fieldparams) setting. ([#7](https://github.com/doublesecretagency/craft-mapbox/pull/7))

### Changed
- Replaced the underlying search API used in the Address field.

## 1.0.1 - 2023-06-14

### Changed
- Changed style of map in the Address field. ([#3](https://github.com/doublesecretagency/craft-mapbox/issues/3))

## 1.0.0 - 2023-05-22

### Added
- Added an easy-to-use [Address Field](https://plugins.doublesecretagency.com/mapbox/address-field/).
- Added a universal API for [Dynamic Maps](https://plugins.doublesecretagency.com/mapbox/dynamic-maps/).

For more information, please see the [complete documentation...](https://plugins.doublesecretagency.com/mapbox/)
