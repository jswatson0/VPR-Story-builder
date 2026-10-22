# VPR Storytelling

Visual Storytelling Layout Builder blocks and Storybuilder for UT Drupal Kit
sites. This repo is the only copy: sites install it with Composer and don't
keep their own.

| Module | What it does |
|---|---|
| `custom_flexpage_content` | 13 Visual Storytelling blocks, the "Visual Storytelling" category in Layout Builder Browser, and the Block Reference page |
| `storybuilder` | "Start a story" form that clones a template flex page |
| `research_frontend_vendor` | Third-party JS/CSS used by the blocks |

Requires `layout_builder_browser`, `entity_clone` and `media_library_form_element`
(pulled in by Composer).

## Install on a site

In the site's `composer.json`:

```json
"repositories": {
    "vpr-storytelling": {
        "type": "vcs",
        "url": "https://github.com/jswatson0/VPR-Story-builder"
    }
}
```

```bash
ddev composer require jswatson0/vpr-storytelling:^1.0
ddev drush en custom_flexpage_content storybuilder -y
ddev drush cex -y
```

Enabling creates the Visual Storytelling browser category and its 13 blocks.
Then:

- Grant `use storybuilder` to the editor role (and `administer storybuilder`
  to whoever picks the templates).
- Build the template flex pages, then set them at
  `/admin/config/content/storybuilder`. Template node IDs are per-site config
  (`storybuilder.settings`); nothing in this package overrides them.
- `ddev drush cex -y` and commit.

## Working on it

Have a site install it from source so the installed copy is a git clone:

```json
"config": {
    "preferred-install": {
        "jswatson0/vpr-storytelling": "source",
        "*": "dist"
    }
}
```

Then edit in `web/modules/contrib/vpr-storytelling/`, commit and push from
there, and tag a release.

## Releases

Tag `MAJOR.MINOR.PATCH`: minor for new blocks or features, patch for fixes,
major for anything that breaks existing stories. Note each release in
CHANGELOG.md. Sites pick it up when they choose to:

```bash
ddev composer update jswatson0/vpr-storytelling
ddev drush updb -y && ddev drush cex -y
```

### Config changes need an update hook

`config/install` only runs when a site first enables a module. A site that
already has it never sees later changes there. So a release that adds or changes
a browser block (or any other shipped config) must do both:

1. Update the YAML in `config/install/`, so new installs get it.
2. Add a `hook_post_update_NAME()` in `custom_flexpage_content.post_update.php`
   that creates or updates the same config, so existing sites get it.

Thumbnail paths in browser config don't matter: the module rewrites them at
runtime to wherever it's installed.
