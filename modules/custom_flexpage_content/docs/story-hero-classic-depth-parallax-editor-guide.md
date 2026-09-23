# Story Hero: Classic Depth Parallax

## What This Block Is

`Story Hero: Classic Depth Parallax` is a visual opening section for a Flex page.
It combines:

- A full-width background image
- Overlay and vignette treatment for readability
- A foreground copy container (eyebrow, headline, description, CTA)
- Scroll-linked motion (background parallax/zoom and optional text zoom)

Use this block when you want a cinematic first section that introduces the story and sets visual tone.

## What It Renders

On desktop/tablet (motion enabled):

- Background image stays "stuck" during scroll for a depth effect
- Background image subtly moves/scales while the page scrolls
- Foreground copy remains readable over the image
- Optional text zoom effect can animate copy in/out as you scroll

On reduced-motion preference or narrow/mobile layouts:

- Motion effects are reduced or disabled
- Content remains readable and static-first

In Layout Builder editing context:

- The block uses a safer static preview mode so edit controls remain accessible

## Recommended Author Workflow

1. Add the block under category `Visual Storytelling`.
2. Choose your background image first (Media Library preferred).
3. Set focal point so the subject stays visible across viewport sizes.
4. Enter concise copy (short headline + short description).
5. Pick copy theme and overlay strength for contrast/readability.
6. Tune motion fields last.
7. Save and verify on page at desktop and mobile breakpoints.

## Field Guide (Custom Fields)

### Content

- `Eyebrow`
  - Short context label above the headline.
  - Keep this brief (2-6 words is typical).

- `Headline`
  - Primary hero message.
  - Keep to one short line when possible.

- `Description`
  - Supporting copy below headline.
  - Works best as 1-2 concise sentences.

- `CTA label` / `CTA URL`
  - Optional call-to-action button.
  - Use action language (`Explore`, `Learn more`, `Apply`).

### Background Media

- `Background media image (preferred)`
  - Select an image from Media Library.
  - Preferred over URL fallback.

- `Background image URL (fallback)`
  - Used only when no media entity is selected.

- `Background image alt text`
  - Optional. Leave blank to inherit the alt text already set on the selected Media Library image. Only fill this in to override it, or when using the direct image URL fallback (which has no alt text of its own to inherit).

- `Image focal point X (%)`
  - Horizontal crop focus.
  - `0 = left`, `50 = center`, `100 = right`.

- `Image focal point Y (%)`
  - Vertical crop focus.
  - `0 = top`, `50 = center`, `100 = bottom`.

- `Background zoom`
  - Initial image scale before scroll motion.
  - Lower values show more of the image.

### Layout and Positioning

- `Section height`
  - `Short`, `Tall`, `Full screen`.
  - Start with `Tall` for most editorial hero use cases.

- `Text alignment`
  - `Left`, `Center`, `Right`.
  - Left/right settings are intentionally inset from the edges.

- `Text vertical position`
  - `Top`, `Middle`, `Bottom`.
  - Controls where the copy block sits over the image.

### Visual Treatment

- `Copy color theme`
  - `Light copy`: for darker backgrounds.
  - `Dark copy`: for lighter backgrounds.

- `Overlay strength`
  - `Light`, `Medium`, `Heavy`, `Full (100%)`.
  - Increase overlay when text contrast is weak.

- `Text zoom on scroll`
  - `None`, `Zoom in`, `Zoom out`.
  - Subtle effect on copy scale during scroll.

### Scroll Behavior

- `Foreground scroll behavior`
  - `Stable (no vertical movement)`:
    - Recommended default for readability and reduced jitter.
  - `Move faster than scroll (1.1x)`:
    - Enables vertical copy travel.

- `Foreground travel distance`
  - Only shown when `Move faster than scroll (1.1x)` is selected.
  - Controls how far copy travels (`40vh`, `60vh`, `80vh`).
  - Higher value = stronger vertical movement.

## Readability and QA Checklist

- Headline remains legible at all scroll positions
- Copy contrasts with image in both desktop and mobile
- No visible seams/gaps between hero and next section
- CTA is readable and clickable
- Motion feels smooth (no abrupt jumps)

## Accessibility Notes

- Provide meaningful alt text for background imagery.
- Avoid encoding critical information in image-only text.
- Respect that reduced-motion users may see static behavior.
