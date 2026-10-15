# Saucebase Roadmap

<div align="center">

[![Tests](https://github.com/saucebase-dev/roadmap/actions/workflows/test.yml/badge.svg)](https://github.com/saucebase-dev/roadmap/actions/workflows/test.yml)
[![Release](https://img.shields.io/github/v/release/saucebase-dev/roadmap)](https://github.com/saucebase-dev/roadmap/releases)
[![Saucebase Core](https://img.shields.io/packagist/dependency-v/saucebase/roadmap/saucebase%2Fcore?label=Saucebase%20Core&color=FF6B35)](https://github.com/saucebase-dev/core)
[![License](https://img.shields.io/github/license/saucebase-dev/roadmap)](LICENSE)

</div>

A public roadmap for Saucebase: feature requests, upvotes, comments and team replies.

A module for [Saucebase](https://github.com/saucebase-dev/saucebase), the AI-ready Laravel SaaS kit. Modules are Composer packages you add to a Saucebase app; once installed, the code is yours to change.

## Features

- **Public board**: Planned, In Progress and Shipped, with a backlog underneath
- **Feature requests** from your users, reviewed by you before they appear
- **Upvotes**: one per person, click again to take it back
- **Team replies**, highlighted above the comments
- **Comments**, with moderation that hides rather than deletes
- **Merge duplicates**, keeping their votes and comments
- **Opens in place**: items slide over the board, and their links work on their own
- **Built for search**: every item is a real page
- **Admin panel** to review, change status and moderate
- **Vue and React**

## Install

**New to Saucebase?** Start a new app and pick **roadmap** when it asks for modules:

```bash
curl -fsSL https://install.saucebase.dev | bash
```

It installs PHP and Composer if they're missing. With the CLI already installed, run `saucebase new my-app` instead.

**Already have a Saucebase app?** Add it with:

```bash
composer require saucebase/roadmap
php artisan migrate
php artisan modules:seed --module=roadmap
npm run build
```

## Configuration

One switch in the admin panel, under **Settings → Roadmap**: whether comments are on.

## Demo data

```bash
php artisan modules:seed --module=roadmap --demo
```

Adds sample items with votes and comments from sample users, and a staff account (`roadmap@saucebase.dev`) that only sees the roadmap in the admin. All use the `DEMO_PASSWORD` from `.env`.

## License

MIT. See [LICENSE](LICENSE).
