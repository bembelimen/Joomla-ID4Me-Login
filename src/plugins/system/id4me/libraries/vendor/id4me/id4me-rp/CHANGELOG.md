# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2019-10-16
### Added
- Possibility to request claims and scopes in the Authorization request
- `getUserInfo()` function in Service to get user Claims
- `getAuthorizationTokens()` function in Service to get `id_token` and `access_token`
- added full `id_token` access via `IdToken` class 

### Changed
- refactored `IdToken` to split generic `JWT` class for shared functionality with other tokens
- added `JWK` and `JWKS` classes to handle the key conversion

## [1.1.1] - 2019-07-12
### Changed
- Authorization: "prompt" is an optional parameter and has no default value

## [1.1.0] - 2019-05-15
### Changed
- PHP 7.1 to PHP 7.0 as minimal required version

## [1.0.0] - 2019-05-07
### Added
- phpseclib and OpenSSL as new encryption/signing handlers
- Model class for IdToken
- Mock jwks.json for testing
- Add interface for http clients

### Changed
- PHP 7.2 to PHP 7.1 as minimal required version
- getAccessTokens() function to authorize() in Authorization
- getAccessTokens() function to authorize() in Service
- Rework of OpenIdConfigHelper

### Removed
- JWT Framework as dependency
- gmp as dependency
- Guzzle as http client in the library

## [0.1.0] - 2019-03-22
### Added
- Initial

[Unreleased]: https://gitlab.com/ID4me/id4me-rp-client-php/compare/master...1.1.0
[1.1.1]: https://gitlab.com/ID4me/id4me-rp-client-php/tags/1.1.1
[1.1.0]: https://gitlab.com/ID4me/id4me-rp-client-php/tags/1.1.0
[1.0.0]: https://gitlab.com/ID4me/id4me-rp-client-php/tags/1.0.0
[0.1.0]: https://gitlab.com/ID4me/id4me-rp-client-php/tags/0.1.0
