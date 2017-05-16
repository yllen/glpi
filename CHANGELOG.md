# GLPI changes

The present file will list all changes made to the project; according to the
[Keep a Changelog](http://keepachangelog.com/) project.

## [9.2] Unreleased

### Added
- Link knowledge base entries with assets or tickets
- Revisions on knowledge base entries and their translations, with diff view
- Add recursive comments on knowledge base entries
- Load minified CSS and JS files (core and plugins) that are generated on release
- Link beetween software licenses
- Alerts on saved searches
- Add ajax browsers notifications in addition to emails

### Changed
- Display knowledge base category items in tickets using a popup instead of a
new whole window
- Reviewed all richtext editor (tinymce) and their upload parts, now more simpler and intuitive
- Don't ask user to select a template if there is no configured template

### API changes

#### Changes

- `CommonDBTM::getTable()` signature has changed
- `Bookmark` has been renamed to `SavedSearch`
- Update to latest jsTree plugin
- `NotificationTemplate::getDataToSend()` signature has changed

#### Deprecated

The following methods have been deprecated:

- `_e()`
- `_ex()`
- `Bookmark::mark_default()`
- `Bookmark::unmark_default()`
- `NotificationTarget*::get*Address()`
- many `NotificationTarget*::get*()`

#### Removals

The following methods have been dropped:

- `CommonGLPI::addDivForTabs()`
- `CommonGLPI::showTabs()`
- `CommonGLPI::showNavigationHeaderOld()`
- `CommonGLPI::show()`
- `Ajax::displaySearchTextForDropdown()`
- `Ajax::getSearchTextForDropdown()`
- `DBMysql::field_flags()`
- `Toolbox::get_magic_quotes_runtime`
- `Toolbox::get_magic_quotes_gpc`
- `Bookmark::changeBookmarkOrder()`
- `Bookmark::moveBookmark()`

For older entries, please check [GLPI website](http://glpi-project.org).
