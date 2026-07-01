<?php

namespace Drupal\custom_flexpage_content\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Renders the Visual Storytelling block reference documentation page.
 */
class VsBlockDocsController extends ControllerBase {

  /**
   * Placeholder images used across sample block configs.
   */
  private const IMG_WIDE  = 'https://picsum.photos/seed/utresearch/1920/1080';
  private const IMG_SQ    = 'https://picsum.photos/seed/utscience/800/800';
  private const IMG_PORT  = 'https://picsum.photos/seed/utlab/800/1200';
  private const IMG_CARD1 = 'https://picsum.photos/seed/ut1/800/600';
  private const IMG_CARD2 = 'https://picsum.photos/seed/ut2/800/600';
  private const IMG_CARD3 = 'https://picsum.photos/seed/ut3/800/600';

  /**
   * Returns the block reference page render array.
   */
  public function index(): array {
    return [
      '#theme'    => 'vs_block_docs_page',
      '#blocks'   => $this->blockDefinitions(),
      '#attached' => ['library' => ['custom_flexpage_content/block_docs']],
    ];
  }

  /**
   * Returns documentation definitions for all Visual Storytelling blocks.
   */
  private function blockDefinitions(): array {
    return [
      [
        'id'          => 'cover',
        'label'       => 'Cover',
        'description' => 'Full-bleed opening hero with a depth-parallax image, bold headline, and an optional CTA. Best used as the first block on a story page.',
        'use_for'     => ['Story openers', 'Campaign landing pages', 'Feature announcements'],
        'example'     => $this->buildBlockExample('custom_flexpage_story_hero_classic_depth_parallax_block', [
          'background_image_url'      => self::IMG_WIDE,
          'background_alt'            => 'UT Austin research lab',
          'eyebrow'                   => 'UT Austin Research',
          'headline'                  => 'Pioneering Research at the Frontiers of Knowledge',
          'dek'                       => 'From quantum computing to climate science, our researchers are tackling the world\'s most pressing challenges.',
          'cta_label'                 => 'Explore Research',
          'cta_url'                   => '#',
          'height'                    => 'tall',
          'text_alignment'            => 'left',
          'content_vertical_position' => 'middle',
          'copy_theme'                => 'light',
          'overlay_strength'          => 'medium',
          'background_scroll_behavior'=> 'none',
          'text_zoom_effect'          => 'none',
        ]),
        'fields'      => [
          'Background image' => 'Upload via Media Library (preferred) or paste an image URL. Landscape images at 1920 × 1080 or wider work best.',
          'Focal point' => 'X/Y sliders (0–100) control which part of the image stays visible when cropped on different screen sizes.',
          'Height' => 'Short (~40vh), Tall (~70vh), or Full screen (100vh). Full screen is the default for a dramatic opener.',
          'Text alignment' => 'Left, center, or right — applies to all copy inside the block.',
          'Vertical position' => 'Top, middle, or bottom — where the copy sits within the block height.',
          'Overlay strength' => 'Controls the dark film over the image. Increase it if white copy is hard to read.',
          'Copy theme' => 'Light (white text) or Dark (dark text). Match to the image brightness.',
          'Scroll behavior' => 'Parallax (image moves slower than the page) or Scroll (image moves with the page).',
          'Headline, Eyebrow, Dek' => 'Eyebrow is a small label above the headline. Dek is supporting text below it.',
          'CTA label / URL' => 'Optional button. Leave both empty to hide the button.',
        ],
        'tips'        => [
          'Use a landscape image that is bright in the center and darker at the edges so the copy stands out without needing a heavy overlay.',
          'Keep the headline under 8 words for maximum impact at large sizes.',
          'Set focal point before choosing height — a portrait-oriented focal subject may be clipped on "Short."',
        ],
      ],
      [
        'id'          => 'section-header',
        'label'       => 'Section Header',
        'description' => 'Chapter title or thematic divider. Can stand alone as a text-only heading or carry a full-bleed background image or video.',
        'use_for'     => ['Chapter breaks inside a long story', 'Thematic dividers', 'Pull-quote moments'],
        'example'     => $this->buildBlockExample('custom_flexpage_story_title_section_block', [
          'title'              => 'Chapter One: The Discovery',
          'subtitle'           => 'A single observation changed the direction of the entire lab.',
          'bg_image_url'       => self::IMG_WIDE,
          'bg_alt'             => 'Lab background',
          'height'             => 'tall',
          'overlay_style'      => 'gradient-down',
          'overlay_color'      => 'dark',
          'overlay_opacity'    => 50,
          'copy_theme'         => 'light',
          'text_position'      => 'bottom-left',
          'bg_scroll_effect'   => 'none',
          'text_scroll_effect' => 'none',
        ]),
        'fields'      => [
          'Title, Subtitle, Byline' => 'Title is the main heading. Subtitle appears beneath it. Byline is a smaller credit line.',
          'Background media' => 'Image, video, or none. When set to "None," the block renders as a clean text section.',
          'Layout' => 'Full-width or Half-and-half. Half-and-half places the copy on one side and the media on the other.',
          'Half-image side' => 'Which side the image appears on (desktop). On mobile you can choose whether the image stacks above or below the copy.',
          'Height / Padding' => 'Separate controls for block height and internal top/bottom padding.',
          'Overlay style' => 'Gradient-down, gradient-up, solid, or none. Gradients help legibility over photos.',
          'Scroll effects' => 'Independent controls for the background (parallax, zoom, fade, or none) and the text (fade, rise, or none). Each-line mode fades individual text lines in sequence.',
        ],
        'tips'        => [
          'For text-only chapter breaks, set Background to "None" and use a contrasting color theme.',
          'The "Per-line text animation" option works best with short titles — long subtitles can feel slow.',
          'Half-and-half layout on desktop with image-below on mobile is a clean, readable pattern for chapter openers.',
        ],
      ],
      [
        'id'          => 'text-section',
        'label'       => 'Text Section',
        'description' => 'Pure editorial text block — eyebrow, headline, lead paragraph, one or two body text areas, and an optional CTA. Supports one- or two-column layouts.',
        'use_for'     => ['Long-form body copy', 'Pull quotes', 'In-page callouts'],
        'example'     => $this->buildBlockExample('custom_flexpage_story_text_section_block', [
          'eyebrow'      => 'The Science',
          'headline'     => 'Why This Research Matters',
          'lead'         => 'A breakthrough in protein folding has opened new avenues for drug discovery — and it started in a basement lab on the Forty Acres.',
          'body_value'   => '<p>For decades, researchers believed the problem was unsolvable. Then a team of graduate students, working through the pandemic shutdown, developed an algorithm that changed the field overnight. Their approach combined machine learning with classical bioinformatics in a way that no one had attempted before.</p>',
          'body_format'  => 'basic_html',
          'columns'      => '1',
          'content_width'=> 'normal',
          'color_theme'  => 'light',
          'drop_cap'     => TRUE,
          'fade_in'      => FALSE,
        ]),
        'fields'      => [
          'Eyebrow / Headline / Lead' => 'Lead is a slightly larger introductory paragraph above the main body.',
          'Body / Body 2' => 'Body 2 becomes the second column when Columns is set to 2.',
          'Columns' => '1 or 2. In two-column mode, Body goes left and Body 2 goes right.',
          'Content width' => 'Normal (centered with max-width) or Wide (full-width container).',
          'Text alignment' => 'Left, center, or right.',
          'Color theme' => 'Light (white background, dark text) or Dark (dark background, light text).',
          'Drop cap' => 'Enlarges the first letter of the body text for a magazine-style opener.',
          'Fade in' => 'The block fades in as it enters the viewport.',
        ],
        'tips'        => [
          'Use the Lead field instead of making the first body paragraph bold — it renders with distinct styling.',
          'Two-column mode works well for "quote left, explanation right" layouts.',
          'Drop cap is most effective on the very first text block after a Cover or Section Header.',
        ],
      ],
      [
        'id'          => 'text-plus-image',
        'label'       => 'Text + Image',
        'description' => 'Side-by-side text and media (image or video). Flexible column ratios and several style presets.',
        'use_for'     => ['Feature callouts', 'Product or research highlights', 'Alternating text/image rows'],
        'example'     => $this->buildBlockExample('custom_flexpage_text_over_media_block', [
          'headline'        => 'Engineering the Future of Clean Energy',
          'body_value'      => '<p>The Cockrell School\'s energy lab is developing next-generation solar materials that could reduce the cost of photovoltaic panels by 40%. The team collaborates with industry partners across three continents.</p>',
          'body_format'     => 'basic_html',
          'cta_label'       => 'Meet the Team',
          'cta_url'         => '#',
          'media_image_url' => self::IMG_SQ,
          'media_alt'       => 'Research team in lab',
          'layout'          => 'media-right',
          'mobile_stack'    => 'text-first',
          'column_ratio'    => 'equal',
          'style_preset'    => 'editorial',
          'theme'           => 'light',
          'drop_cap'        => FALSE,
        ]),
        'fields'      => [
          'Media' => 'Image or video from Media Library, or paste a URL. For video, a poster image is shown while it loads.',
          'Layout' => 'Media left or Media right.',
          'Mobile stack order' => 'Whether text or image appears first on narrow screens.',
          'Column ratio' => 'Equal (50/50), Media wide (60/40), or Text wide (40/60).',
          'Style preset' => 'Editorial (clean), Card (light shadow box), or Overlap (media overlaps text area).',
          'Drop cap' => 'Applies a large first letter to the body text.',
          'Color theme' => 'Light or Dark background.',
        ],
        'tips'        => [
          'Alternate layout direction (media-left / media-right) when stacking multiple Text + Image blocks.',
          '"Card" style preset pairs well with a white background and rounded media.',
          'For video, always set a poster image — autoplay is not guaranteed on mobile.',
        ],
      ],
      [
        'id'          => 'panel-reveal',
        'label'       => 'Panel Reveal',
        'description' => 'Sticky scroll sequence where panels slide in one over another as the reader scrolls. Creates a high-impact "chapter flip" effect.',
        'use_for'     => ['Multi-step narratives', 'Before/after comparisons', 'Timeline-style sequences'],
        'example'     => $this->buildBlockExample('custom_flexpage_story_reveal_section_block', [
          'panel_count'           => 3,
          'scroll_duration'       => 'medium',
          'page_turn'             => FALSE,
          'panel_1_image_url'     => self::IMG_WIDE,
          'panel_1_alt'           => 'Panel 1',
          'panel_1_eyebrow'       => 'Step One',
          'panel_1_headline'      => 'The Question',
          'panel_1_dek'           => 'What if the key to clean water was hiding in ancient geology?',
          'panel_1_text_position' => 'bottom-left',
          'panel_1_copy_theme'    => 'light',
          'panel_1_overlay'       => 'medium',
          'panel_2_image_url'     => self::IMG_CARD1,
          'panel_2_alt'           => 'Panel 2',
          'panel_2_eyebrow'       => 'Step Two',
          'panel_2_headline'      => 'The Method',
          'panel_2_dek'           => 'Field samples from 14 aquifer sites, analyzed using mass spectrometry.',
          'panel_2_text_position' => 'bottom-left',
          'panel_2_copy_theme'    => 'light',
          'panel_2_overlay'       => 'medium',
          'panel_3_image_url'     => self::IMG_CARD2,
          'panel_3_alt'           => 'Panel 3',
          'panel_3_eyebrow'       => 'Step Three',
          'panel_3_headline'      => 'The Breakthrough',
          'panel_3_dek'           => 'A mineral compound no one had considered — naturally filtering nitrates at scale.',
          'panel_3_text_position' => 'bottom-left',
          'panel_3_copy_theme'    => 'light',
          'panel_3_overlay'       => 'medium',
        ]),
        'fields'      => [
          'Panels (1–6)' => 'Each panel has its own image, eyebrow, headline, body, and optional CTA. Configure at least 2 panels.',
          'Scroll duration' => 'Slow, Medium, or Fast — controls how much scroll distance each panel consumes before the next slides in.',
          'Page-turn effect' => 'Adds a subtle corner shadow to make panels look like turning pages.',
        ],
        'tips'        => [
          'Use high-contrast hero images for each panel so the transition is dramatic.',
          '"Slow" duration works well for panels with a lot of copy; "Fast" suits purely visual panels.',
          'Keep all panel headlines to a consistent length for a polished look as they swap in.',
        ],
      ],
      [
        'id'          => 'timeline',
        'label'       => 'Timeline',
        'description' => 'Vertical timeline of milestones with dates, headlines, body text, and optional media. Supports alternating and single-column layouts.',
        'use_for'     => ['Historical timelines', 'Project milestones', 'Research journey narratives'],
        'example'     => $this->buildBlockExample('custom_flexpage_story_timeline_milestones_block', [
          'eyebrow'    => 'Our History',
          'headline'   => 'Fifty Years of Discovery',
          'milestones' => implode("\n", [
            '1974 | Institute Founded | A small group of researchers established the center with a $2M federal grant.',
            '1988 | First Major Breakthrough | Publication in <em>Science</em> established the lab\'s global reputation.',
            '2003 | New Facility Opens | The 80,000 sq ft research building became the largest of its kind in the Southwest.',
            '2021 | National Recognition | Named a Center of Excellence by the National Science Foundation.',
          ]),
          'layout'     => 'alternating',
          'theme'      => 'light',
          'media_url'  => '',
          'media_id'   => 0,
        ]),
        'fields'      => [
          'Eyebrow / Headline / Intro' => 'Section-level heading above the timeline items.',
          'Milestones' => 'One milestone per line, pipe-separated: Year | Title | Description. Example: 1974 | Institute Founded | A small grant started it all.',
          'Layout' => 'Alternating (items alternate left/right) or Single-column (all items on one side).',
          'Color theme' => 'Light or Dark.',
          'Background media' => 'Optional image shown behind the entire timeline section.',
        ],
        'tips'        => [
          'Dates don\'t have to be calendar dates — they can be labels like "Phase 1" or "Year One."',
          'Alternating layout works best with 4+ items; fewer items can look sparse.',
          'Use concise body text per milestone — 2–3 sentences max.',
        ],
      ],
      [
        'id'          => 'card-grid',
        'label'       => 'Card Grid',
        'description' => 'Responsive grid of cards, each with an image, headline, body, and optional CTA link. Configurable columns, corner radius, surface style, and section width.',
        'use_for'     => ['Research highlights', 'Team or project showcases', 'News/article teasers'],
        'example'     => $this->buildBlockExample('custom_flexpage_story_grid_catalog_block', [
          'eyebrow'         => 'Featured Research',
          'headline'        => 'Initiatives Shaping Tomorrow',
          'desktop_columns' => '3',
          'card_surface'    => 'filled',
          'corner_radius'   => 'small',
          'gutter_size'     => 'regular',
          'section_width'   => 'wide',
          'theme'           => 'light',
          'item_1_title'    => 'Climate & Energy',
          'item_1_body_value'  => '<p>Developing carbon-capture materials and next-generation solar cells for a decarbonized grid.</p>',
          'item_1_body_format' => 'basic_html',
          'item_1_media_url'   => self::IMG_CARD1,
          'item_1_media_alt'   => 'Climate research',
          'item_1_cta_label'   => 'Learn more',
          'item_1_cta_url'     => '#',
          'item_2_title'    => 'Neuroscience',
          'item_2_body_value'  => '<p>Mapping the connectome to understand how memory, attention, and emotion emerge from neural circuits.</p>',
          'item_2_body_format' => 'basic_html',
          'item_2_media_url'   => self::IMG_CARD2,
          'item_2_media_alt'   => 'Neuroscience lab',
          'item_2_cta_label'   => 'Learn more',
          'item_2_cta_url'     => '#',
          'item_3_title'    => 'Space Sciences',
          'item_3_body_value'  => '<p>Designing instruments for planetary missions and studying the formation of our solar system.</p>',
          'item_3_body_format' => 'basic_html',
          'item_3_media_url'   => self::IMG_CARD3,
          'item_3_media_alt'   => 'Space sciences',
          'item_3_cta_label'   => 'Learn more',
          'item_3_cta_url'     => '#',
        ]),
        'fields'      => [
          'Section heading' => 'Eyebrow, headline, and intro text above the grid.',
          'Desktop columns' => '2, 3, or 4 columns. On mobile, cards always stack to 1 column.',
          'Card surface' => 'Filled (colored background), Outlined (border), or Flat (no card chrome).',
          'Corner radius' => 'None, Small, Medium, or Large.',
          'Gutter size' => 'Tight, Regular, or Loose — space between cards.',
          'Section width' => 'Normal or Wide.',
          'Color theme' => 'Light or Dark.',
          'Card items' => 'Each card: image (Media Library or URL), headline, body, CTA label, CTA URL.',
        ],
        'tips'        => [
          'Use consistent image aspect ratios across all cards — the grid looks best when all images are the same height.',
          '"Filled" surface with rounded corners works well for editorial features; "Outlined" or "Flat" suits more minimal designs.',
          'Leave CTA label and URL empty on cards that don\'t need a link.',
        ],
      ],
      [
        'id'          => 'photo-grid',
        'label'       => 'Photo Grid',
        'description' => 'Responsive image grid with lightbox. Clicking any image opens a full-size modal with an optional title and description.',
        'use_for'     => ['Photo essays', 'Lab or fieldwork photography', 'Event galleries'],
        'example'     => $this->buildBlockExample('research_image_grid_block', [
          'modal_title_default' => 'Research image',
          'images_per_row'      => 3,
          'items'               => [
            ['title' => 'Electron microscopy', 'media_id' => 0, 'image_url' => self::IMG_CARD1, 'alt_text' => 'Sample 1', 'description' => ['value' => '', 'format' => 'basic_html']],
            ['title' => 'Field sampling',      'media_id' => 0, 'image_url' => self::IMG_CARD2, 'alt_text' => 'Sample 2', 'description' => ['value' => '', 'format' => 'basic_html']],
            ['title' => 'Lab analysis',        'media_id' => 0, 'image_url' => self::IMG_CARD3, 'alt_text' => 'Sample 3', 'description' => ['value' => '<p>High-resolution spectroscopy data from the 2023 field season.</p>', 'format' => 'basic_html']],
          ],
        ]),
        'fields'      => [
          'Default modal title' => 'Fallback title shown in the lightbox when an image has no individual title.',
          'Images per row' => '1–4 columns.',
          'Grid items (up to 12)' => 'Each item: image from Media Library or URL, title, alt text, and a rich-text modal description.',
        ],
        'tips'        => [
          'Items without a Media Library selection OR image URL are silently skipped.',
          'Alt text falls back to the item title if left blank — always fill in one of them.',
          'The modal description supports basic HTML (bold, links, lists) — useful for image credits.',
        ],
      ],
      [
        'id'          => 'interactive-panels',
        'label'       => 'Interactive Panels',
        'description' => 'Tabbed selector that shows one panel at a time. A sidebar or top-bar list of labels lets readers jump between panels.',
        'use_for'     => ['Compare multiple initiatives', 'Explore research areas', 'Feature sets with equal weight'],
        'example'     => $this->buildBlockExample('custom_flexpage_detail_selector_block', [
          'eyebrow'             => 'Research Centers',
          'headline'            => 'Explore Our Areas of Focus',
          'default_panel_label' => 'Overview',
          'default_headline'    => 'World-Class Research Facilities',
          'default_body_value'  => '<p>Select a center from the list to learn about its mission, team, and recent breakthroughs.</p>',
          'default_body_format' => 'basic_html',
          'default_media_url'   => self::IMG_WIDE,
          'default_media_alt'   => 'Overview',
          'heading_position'    => 'left',
          'theme'               => 'light',
          'item_1_heading'      => 'Materials Science',
          'item_1_headline'     => 'Engineering Matter at the Atomic Scale',
          'item_1_body_value'   => '<p>Developing new materials for energy storage, quantum devices, and biomedical applications.</p>',
          'item_1_body_format'  => 'basic_html',
          'item_1_media_url'    => self::IMG_CARD1,
          'item_1_media_alt'    => 'Materials science',
          'item_2_heading'      => 'Environmental Science',
          'item_2_headline'     => 'Understanding Earth\'s Changing Systems',
          'item_2_body_value'   => '<p>From ocean acidification to urban heat islands, we study how human activity reshapes the planet.</p>',
          'item_2_body_format'  => 'basic_html',
          'item_2_media_url'    => self::IMG_CARD2,
          'item_2_media_alt'    => 'Environmental science',
          'item_3_heading'      => 'Computational Biology',
          'item_3_headline'     => 'Decoding Life with Algorithms',
          'item_3_body_value'   => '<p>Applying machine learning to genomics, proteomics, and drug target identification.</p>',
          'item_3_body_format'  => 'basic_html',
          'item_3_media_url'    => self::IMG_CARD3,
          'item_3_media_alt'    => 'Computational biology',
        ]),
        'fields'      => [
          'Section heading' => 'Eyebrow, headline, and intro above the selector.',
          'Heading position' => 'Left (selector sidebar) or Top (selector as tab bar).',
          'Default panel' => 'The "Overview" panel shown before a tab is selected. Has its own image, headline, body, and CTA.',
          'Panel items (up to 8)' => 'Each panel: tab label, image, headline, body text, CTA label, CTA URL.',
          'Color theme' => 'Light or Dark.',
        ],
        'tips'        => [
          '"Left" heading position (sidebar) works well for 4–8 panels. "Top" tab bar works best for 2–4 options.',
          'Keep tab labels short (1–3 words) — they must fit in the selector without truncation.',
          'The default panel is optional but recommended — it gives context before the reader starts clicking.',
        ],
      ],
      [
        'id'          => 'background-video',
        'label'       => 'Background Video Section',
        'description' => 'Full-width section with a silently autoplaying looping video behind the content. Falls back to a poster image on devices where autoplay is blocked.',
        'use_for'     => ['Cinematic section breaks', 'Lab footage backgrounds', 'High-impact campaign moments'],
        'example'     => $this->buildBlockExample('custom_flexpage_story_background_video_block', [
          'video_media_id'  => 0,
          'video_url'       => '',
          'poster_media_id' => 0,
          'poster_url'      => self::IMG_WIDE,
          'poster_alt'      => 'Video fallback image',
          'eyebrow'         => 'In the Lab',
          'headline'        => 'Where Discovery Happens',
          'dek'             => 'Step inside the research spaces shaping the next century of science.',
          'cta_label'       => 'Take a tour',
          'cta_url'         => '#',
          'height'          => 'tall',
          'text_position'   => 'bottom-left',
          'copy_theme'      => 'light',
          'overlay_strength'=> 'medium',
        ]),
        'fields'      => [
          'Background video' => 'Select from Media Library (preferred). MP4/H.264 recommended. Keep files under 10 MB.',
          'Or enter a direct video URL' => 'Fallback for externally hosted video. Use only if the file is not in Media Library.',
          'Poster / fallback image' => 'Shown while the video loads and on devices that block autoplay. Strongly recommended.',
          'Height' => 'Short (~40vh), Tall (~70vh), or Full screen (100vh).',
          'Text position' => '9 grid positions (top/center/bottom × left/center/right).',
          'Copy theme' => 'Light (white text) or Dark (dark text).',
          'Overlay strength' => 'None, Light, Medium, or Heavy dark overlay over the video to ensure copy contrast.',
          'Eyebrow / Headline / Description / CTA' => 'All optional. Leave empty for a purely visual background-video section.',
        ],
        'tips'        => [
          'Always upload a poster image — autoplay is disabled on most mobile devices and low-power mode.',
          'Video files over 10 MB will slow page load significantly. Compress with Handbrake before uploading.',
          'Use "Medium" or "Heavy" overlay when placing light copy over bright or highly varied video footage.',
          'Test on a real mobile device — video autoplay behavior varies by browser and OS.',
        ],
      ],
    ];
  }

  /**
   * Instantiates a block plugin with sample config and returns its build array.
   */
  private function buildBlockExample(string $plugin_id, array $config): array {
    try {
      $block = \Drupal::service('plugin.manager.block')->createInstance(
        $plugin_id,
        $config + ['id' => $plugin_id, 'label' => '', 'label_display' => '0']
      );
      return $block->build();
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
