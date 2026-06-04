# `perkamo/sdk` Changelog

## 0.6.0 - 2026-06-04

### Changed

- Add `identify($userId, $traits)` for creating or updating trusted profile
  traits through `POST /v1/identify`.

## 0.5.0 - 2026-06-04

### Changed

- Add `program()` and `eventCatalog()` helpers for trusted backend/admin
  integrations.
- Enrich `PerkamoApiException` with request id, retry-after and rate-limit
  metadata when returned by the API or gateway.

## 0.4.1 - 2026-06-04

### Changed

- Default `Client` to the hosted Perkamo API so standard integrations can pass
  only the server API key.

## 0.4.0 - 2026-06-04

### Changed

- Add typed event request and response objects for event ingestion.
- Add typed `createBrowserToken()` and `createBrowserStreamToken()` helpers for
  Perkamo-issued browser JWTs.
- Switch the package license to MIT.

## 0.3.1 - 2026-06-03

### Changed

- Refreshed the mirrored Packagist release after the synchronized SDK documentation patch.

## 0.3.0 - 2026-06-03

### Changed

- Removed redundant Space arguments and payload fields from the server client; the server API key now identifies the Space for event, batch and profile calls.

## 0.2.0 - 2026-06-03

### Changed

- Renamed the pre-launch public integration field from `tenant` to `space` across payloads, validation and client configuration.

## 0.1.0 - 2026-06-02

### Added

- Added initial backend PHP client skeleton for Packagist.
- Added signed event, batch and profile API helpers.
