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

Develop in texas-research's DDEV site, which has real stories to test against.
By default Composer installs the package from a zip, so the installed folder
isn't a git repo and edits there get lost. Make it a clone once:

```bash
ddev composer reinstall jswatson0/vpr-storytelling --prefer-install=source
cd web/modules/contrib/vpr-storytelling
git switch main
```

This leaves the site's `composer.json` and `composer.lock` alone. Composer
checks out a commit, not a branch, so `git switch main` gets you onto a branch
you can commit to. The site's own git ignores `web/modules/contrib/`, so package
changes only show up in this folder's git.

Then:

1. Edit and test locally. Check pt2050 as well before releasing anything
   visual, since the CSS was written against texas-research's theme.
2. Commit and push to `main` from this folder.
3. Tag a release (below).

Commit or stash before running `composer update` on the site. Otherwise Composer
asks whether to throw away your uncommitted changes.

If `git push` asks for an SSH key: Composer sets the push URL to SSH. Switch it
to HTTPS with
`git remote set-url --push origin https://github.com/jswatson0/VPR-Story-builder.git`.

## Releases

Tag `MAJOR.MINOR.PATCH`: minor for new blocks or features, patch for fixes,
major for anything that breaks existing stories. Note each release in
CHANGELOG.md, then:

```bash
git tag 1.1.0 && git push origin 1.1.0
```

Sites require `^1.0` and their `composer.lock` pins the exact version, so a new
tag reaches a site only when someone updates it there:

```bash
ddev composer update jswatson0/vpr-storytelling
ddev drush updb -y && ddev drush cr && ddev drush cex -y
```

Commit `composer.lock` and any exported config, then deploy as usual.

### Config changes need an update hook

`config/install` only runs when a site first enables a module. A site that
already has it never sees later changes there. So a release that adds or changes
a browser block (or any other shipped config) must do both:

1. Update the YAML in `config/install/`, so new installs get it.
2. Add a `hook_post_update_NAME()` in `custom_flexpage_content.post_update.php`
   that creates or updates the same config, so existing sites get it.

Thumbnail paths in browser config don't matter: the module rewrites them at
runtime to wherever it's installed.
