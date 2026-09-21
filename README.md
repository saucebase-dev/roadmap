# Roadmap Module

<div align="center">

[![Tests](https://github.com/saucebase-dev/roadmap/actions/workflows/test.yml/badge.svg)](https://github.com/saucebase-dev/roadmap/actions/workflows/test.yml)
[![Release](https://img.shields.io/github/v/release/saucebase-dev/roadmap)](https://github.com/saucebase-dev/roadmap/releases)
[![Saucebase](https://img.shields.io/badge/Saucebase-1.1+-FF6B35)](https://github.com/saucebase-dev/saucebase)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php&logoColor=white)](https://php.net)

Works with:<br/>
[![Vue 3.5](https://img.shields.io/badge/Vue-3.5-4FC08D?logo=vue.js&logoColor=white)](https://vuejs.org) [![React 19](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)](https://react.dev)

</div>

A public roadmap for [Saucebase](https://github.com/saucebase-dev/saucebase), a Laravel SaaS starter kit.

Adds `/roadmap`, where users suggest features, vote on them, and follow what you are building. You review suggestions in the admin before anything appears.

**[Full documentation →](https://saucebase-dev.github.io/docs/modules/roadmap)**

## Features

- **Public board** — Planned, In Progress and Shipped columns, with a backlog underneath
- **Feature requests** — users suggest what they want
- **Upvotes** — one vote per person, click again to take it back
- **Team response** — answer an item publicly, shown highlighted above the comments
- **Comments** — on every item, with moderation that hides rather than deletes
- **You review first** — suggestions land hidden and only appear once you approve them
- **Merge duplicates** — fold the same request into one, keeping its votes and comments
- **Opens in place** — clicking an item slides it over the board, but the link still works on its own
- **Built for search** — items are real pages and can go in your sitemap
- **Admin panel** — review, change status and moderate at `/admin`
- **Vue and React** — works on both

## Requirements

| | |
| --- | --- |
| Saucebase core | `^1.1` |
| Modules | [Auth](https://github.com/saucebase-dev/auth) |

## Installation

```bash
composer require saucebase/roadmap
php artisan migrate
npm run build
```

Your board is at `/roadmap`. Anyone can read it; voting, commenting and suggesting need an account.

### Sample data (optional)

```bash
php artisan modules:seed --module=roadmap --demo
```

Adds sample items, votes and comments so the board is not empty while you look around.

## How the statuses work

Every suggestion arrives as **Under review** and is invisible to visitors until you move it on.

| Status | Where it shows |
| --- | --- |
| Under review | Nowhere — waiting for you |
| Backlog | Public, listed under the board |
| Planned | Board column |
| In progress | Board column |
| Shipped | Board column |
| Closed | Nowhere — rejected, cancelled or merged |

## Extending

**Listen for status changes.** `StatusChanged` fires whenever you move an item, so you can post to Slack or email the people who voted:

```php
use Modules\Roadmap\Events\StatusChanged;

Event::listen(StatusChanged::class, function (StatusChanged $event) {
    // $event->item
});
```

**Change the pages.** The board and the item view are normal Vue and React pages in `resources/js/`. Edit them like any other.

## Configuration

One switch, at **`/admin` → Settings → Roadmap**: whether comments are on.

See the [documentation](https://saucebase-dev.github.io/docs/modules/roadmap) for moderation, merging and customising the board.

## License

Proprietary. Part of [Saucebase](https://github.com/saucebase-dev/saucebase).
