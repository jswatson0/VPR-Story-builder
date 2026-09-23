# Publishing & Tagging a Story

A story page built with Story Builder is a `utexas_flex_page` node. It has no
tags/category field of its own. To make a story discoverable in listings —
including tagging it for Bridging Barriers (BB) — publish a short companion
**News article** that links out to it, using fields the News content type
already has.

This same page also appears (condensed) on the in-admin
[Block Reference & Tips](/vs-blocks/docs) page under "Publishing & Tagging
Your Story."

## Steps

1. **Build & publish the story.** Finish your story page in Story Builder and
   publish it. Copy its URL.
2. **Create a companion News article.** Go to *Content > Add content > News*.
   Give it a title. Leave the *Body* field empty — it won't be shown once the
   External link (below) is set.
3. **Set the External link.** Paste the story's URL into the News article's
   **External link** field (`field_utnews_external_link`). This makes the
   News listing title link straight to the story instead of to a separate
   News article page.
4. **Add News tags.** Use the **News tags** field
   (`field_utnews_news_tags`, vocabulary `utnews_tags`) to categorize the
   story so it surfaces in the right listings.
   - There is already a general **"Visual Story"** tag in News tags — apply
     it to every story-builder story's companion article so all stories can
     be found/filtered as a group.
   - There is **no "Bridging Barriers" tag yet** as of this writing. For BB
     stories, add one under *Structure > Taxonomy > News tags* (once), then
     apply both "Visual Story" and "Bridging Barriers" to each BB story's
     companion article.

## How this works

The News content type's `NewsContentTypeHelper::prepareLinkedTitle()`
(in `utnews_content_type_news`) checks whether `field_utnews_external_link`
is populated on a News node. If it is, the article's title/teaser link in any
listing block or listing page points at that URL instead of the News node's
own page. This is existing, general-purpose site behavior — not something
built for Story Builder specifically — so it works the same way for linking
News articles out to any external destination, story pages included.

## Known limitation

The News RSS feed view (`utnews_news_feed`) links to the News article's own
page rather than following the External link. Everything else — listing
blocks, listing pages, teasers — follows the External link correctly.
