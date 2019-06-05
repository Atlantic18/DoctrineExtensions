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
### Loggable
#### Fixed
- Added missing string casting of `objectId` in `LogEntryRepository::revert()` method (#2009)
### Sluggable
#### Fixed
- Automatically disable/enable the soft-deletable filter when searching for similar slugs

### Tree
#### Fixed
- Remove hard-coded parent column name in repository prev/next sibling queries [#2020]

## [2.4.37] - 2019-03-17
### Translatable
#### Fixed
- Bugfix to load null value translations (#1990)
