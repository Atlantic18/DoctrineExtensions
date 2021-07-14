# Doctrine Extensions Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

Each release should include sub-headers for the Extension above the types of
changes, in order to more easily recognize how an Extension has changed in
a release.

```
## [2.4.36] - 2018-07-26
### Sortable
#### Fixed
- Fix issue with add+delete position synchronization (#1932)
```

---

## [Unreleased]
### Tree
#### Fixed
- Add support UUID in nested strategy (#2216)

## [3.1.0] - 2021-06-22
### Fixed
- Allow installing doctrine/cache 2.0 (thanks @alcaeus!)
- Make doctrine/cache a dev dependency

## [3.0.5] - 2021-04-23
### Fixed
- Use path_separator when removing children (#2217)

## [3.0.4] - 2021-03-27
### Fixed
- Add hacky measure to resolve incompatibility with DoctrineBundle 2.3 [#2211](https://github.com/doctrine-extensions/DoctrineExtensions/pull/2211)

## [3.0.3] - 2021-01-23
### Fixed
- Add PHP 8 compatibility to `composer.json`, resolving minor function parameter deprecations [#2194](https://github.com/Atlantic18/DoctrineExtensions/pull/2194)

## [3.0.2] - 2021-01-23
- Ignore; tag & version mismatch

## [3.0.1] - 2021-01-23
- Ignore; wrong branch published

## [3.0.0] - 2020-09-23
### Notable & Breaking Changes
- Minimum PHP version requirement of 7.2
- Source files moved from `/lib/Gedmo` to `/src`
- Added compatibility for `doctrine/common` 3.0 and `doctrine/persistence` 2.0
- All string column type annotations changed to 191 character length (#1941)
- Removed support for `\Zend_date` date format [#2163](https://github.com/Atlantic18/DoctrineExtensions/pull/2163)
- Renamed `Zend Framework` to `Laminas` [#2163](https://github.com/Atlantic18/DoctrineExtensions/pull/2163)

Changes below marked ⚠️ may also be breaking, if you have extended DoctrineExtensions.

### MongoDB
- Requires the `ext-mongodb` PHP extension. Usage of `ext-mongo` is deprecated and will be removed in the next major version.
- Minimum Doctrine MongoDB ODM requirement of 2.0
- Usages of `\MongoDate` replaced with `MongoDB\BSON\UTCDateTime`

### Global / Shared
#### Fixed
- Removed `null` parameter from `Doctrine\Common\Cache\Cache::save()` calls (#1996)

### Tree
#### Fixed
- The value of path source property is cast to string type for Materialized Path Tree strategy (#2061)

### SoftDeleteable
#### Changed
- ⚠️ Generate different Date values based on column type (#2115)
