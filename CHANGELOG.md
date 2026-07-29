# `perkamo/sdk` Changelog

## Unreleased

### Added

- Add `deleteCustomer($userId)` for signed, idempotent customer erasure with a
  server key that has the dedicated `customer:delete` scope.

## 0.8.0 - 2026-06-26

### Changed

- Replace browser-token helpers with backend-only `ClientTokenSigner` for
  self-signing short-lived Perkamo client tokens with signing keys.
- Update package documentation around `kid`/secret handling and
  client-token browser SDK setup.

## 0.7.0 - 2026-06-06

### Changed

- Renamed the trusted customer state helper surface around the customer model
  and aligned PHP examples with the customer-first API.
- Documented formula-based award events and customer wallet semantics for the
  refreshed program model.

## 0.6.0 - 2026-06-04

### Changed

- Add `identify($userId, $traits)` for creating or updating trusted customer
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

- Removed redundant Space arguments and payload fields from the server client; the server API key now identifies the Space for event, batch and customer calls.

## 0.2.0 - 2026-06-03

### Changed

- Renamed the pre-launch public integration field from `tenant` to `space` across payloads, validation and client configuration.

## 0.1.0 - 2026-06-02

### Added

- Added initial backend PHP client skeleton for Packagist.
- Added signed event, batch and customer API helpers.

## 0.9.0 - 2026-07-29

### Changed

- Add signed, replay-safe customer erasure.
