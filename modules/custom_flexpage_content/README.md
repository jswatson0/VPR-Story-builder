# Custom Flexpage Content

This module adds a starter set of visual storytelling blocks for Flex pages:

- Story Title Hero
- Story Text Over Media
- Story Scrollpoints

All blocks are available in Layout Builder under category `Visual Storytelling`.

## Enable

1. `ddev drush en custom_flexpage_content -y`
2. `ddev drush cr`

## Use on Flex Pages

1. Open a `utexas_flex_page` in Layout Builder.
2. Add block.
3. Filter category `Visual Storytelling`.
4. Configure media and narrative content for each section.

## Notes

- Each block supports Media Library image selection and a direct image URL fallback.
- If `layout_builder_restrictions` is later switched to an allowlist, allow this category or block IDs:
  - `custom_flexpage_story_title_hero_block`
  - `custom_flexpage_text_over_media_block`
  - `custom_flexpage_scrollpoints_block`

