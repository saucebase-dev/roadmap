# Roadmap Module

## Overview

Public roadmap board with feature requests, upvotes, comments, team replies, and duplicate merging. Filament admin at `/admin` → Roadmap.

Vue in `resources/js/vue/`, React in `resources/js/react/`. They mirror each other; change both.

---

## Non-Obvious Design

### Statuses Split Moderation From Stage

`RoadmapStatus` mixes two jobs on purpose, and three helpers decide what shows where:

- **Hidden from visitors:** `UnderReview` (where every suggestion lands) and `Closed` (rejected, cancelled, or merged away).
- **`publicStatuses()`** — what a visitor may read, vote on, and comment on: `Backlog`, `Planned`, `InProgress`, `Shipped`.
- **`boardStatuses()`** — the three board columns: `Planned`, `InProgress`, `Shipped`. `Backlog` is public but deliberately off the board, listed underneath it.

Every read and write path checks `publicStatuses()` and 404s otherwise. Adding a status means deciding which of those two lists it belongs to.

### A Vote Row Is An Upvote

There are no downvotes and no vote type column. A row in `roadmap_votes` means "this user wants this", and voting again deletes the row. The unique index on `(roadmap_item_id, user_id)` is what keeps one vote per person.

Submitting an item also creates that person's vote, so nothing sits at zero.

### Public Page, Optional Login

`roadmap.index` and `roadmap.show` are outside the `auth` middleware — they are the marketing surface and are in the sitemap. Voting, commenting, and suggesting are inside it. Both pages send an `authenticated` prop so the frontend sends guests to login instead of hiding the buttons.

### Page Or Slideover, Same URL

`RoadmapController::show()` returns `Inertia::modal(...)` when the request carries `Modal::HEADER_MODAL`, and an ordinary `Inertia::render(...)` otherwise. The board opens items in a right-hand slideover through `ModalLink`; a typed or shared link renders the full page, which is what search engines and the sitemap need.

Decide on the header, never the referer: the package would otherwise treat any in-app link as "open me over the previous page". `ItemDetail` holds the markup for both so they cannot drift.

Posting a comment inside the slideover redirects the page *behind* it, so the form calls `modal.reload()` when `useModal()` returns a modal, and `router.reload({ only: ['item'] })` when it does not.

### Hidden Comments

Moderation hides rather than deletes: `hidden_at` plus a private `hidden_reason`. `comments()` is every comment, `visibleComments()` is the public one, and both the payload and `comments_count` use the latter. A hidden comment stays in the database for the admin and never reaches the page.

`RoadmapSettings::comments_enabled` switches comments off entirely; the controller 404s on post, so hiding the UI is not the only defence.

### Merging Duplicates

`RoadmapItem::mergeInto()` moves votes and comments to the target, drops votes from anyone who had voted on both (the unique index would reject them), then sets `merged_into_id` and closes the source. `comments()->reorder()` matters: the relation is ordered, and an ordered `UPDATE` is not portable.

### Notifications Go To Everyone Waiting

A status change notifies `subscribers()`: the submitter plus everyone who voted. Voting is how you subscribe.

Mail lines are rendered as Markdown, so the item title is escaped before interpolation — otherwise a title could put a working link into someone's inbox. HTML is already escaped by the mail view; Markdown link syntax is not.

### Rate Limits Are Named, With Two Windows

`RoadmapServiceProvider::registerRateLimits()` defines `roadmap-suggest`, `roadmap-comment`, and `roadmap-vote`. Each write route has a burst limit and a slower one behind it, because a per-minute limit alone still allows a steady drip all day. Keyed by user id, falling back to IP.

### Dates

Comments and responses are sent as ISO strings and formatted with `timeZone: 'UTC'`, so the server render and every browser agree on the day.

---

## Testing

```bash
# PHPUnit — this module only
php -d memory_limit=2048M artisan test --compact modules/roadmap/tests

# E2E
npx playwright test --project="@roadmap*"
```
