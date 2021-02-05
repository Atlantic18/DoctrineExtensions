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

## [2.4.42] - 2020-08-20
### Translatable
#### Fixed
- Allow for both falsy and null-fallback translatable values (#2152)

## [2.4.41] - 2020-05-10
### Sluggable
#### Fixed
- Remove PHPDoc samples as they are interpreted by Annotation Reader (#2120)

## [2.4.40] - 2020-04-27
### SoftDeleteable
#### Fixed
- Invalidate query cache when toggling filter on/off for an entity (#2112)

## [2.4.39] - 2020-01-18
### Tree
### Fixed
- The value of path source property is cast to string type for Materialized Path Tree strategy (#2061)

## [2.4.38] - 2019-11-08
### Global / Shared
#### Fixed
- Add `parent::__construct()` calls to Listeners w/ custom constructors (#2012)
- Add upcoming Doctrine ODM 2.0 to `composer.json` conflicts (#2027)

### Loggable
#### Fixed
- Added missing string casting of `objectId` in `LogEntryRepository::revert()` method (#2009)
### Sluggable
#### Fixed
- Automatically disable/enable the soft-deletable filter when searching for similar slugs

### ReferenceIntegrity
#### Fixed
- Get class from meta in ReferenceIntegrityListener (#2021)

### Translatable
#### Fixed
- Return default AST executor instead of throwing Exception in Walker (#2018)
- Fix duplicate inherited properties (#2029)

### Tree
#### Fixed
- Remove hard-coded parent column name in repository prev/next sibling queries (#2020)

## [2.4.37] - 2019-03-17
### Translatable
#### Fixed
- Bugfix to load null value translations (#1990)
