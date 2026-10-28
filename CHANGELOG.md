# Changelog

## 1.0.1

- CTA URL fields accept paths on this site, such as `/research`, as well as
  full URLs. Pages without a node, like views, can now be linked. Bare words,
  `//host` links and unsafe schemes such as `javascript:` are rejected.

## 1.0.0

First release as a standalone package, extracted from texas-research
`web/modules/research/` with history.

- Browser category and 13 `vs_*` browser blocks ship in
  `custom_flexpage_content/config/install`.
- Block thumbnails resolve to the module's installed path at runtime.
- `custom_flexpage_content` now depends on `layout_builder_browser`, and no
  longer on `default_content`, which it never used.
