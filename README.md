# Michi Theme (Beaver Builder Child Theme)

Custom WordPress child theme for the Michi site, based on `bb-theme`.

## What this theme includes

- Child theme bootstrap in `functions.php`
- GitHub-based theme updates via Plugin Update Checker (`updater/`)
- Modular theme logic in `inc/`:
  - asset enqueueing
  - ACF config
  - custom post types
  - REST API endpoints
  - dealer finder logic and migration utilities
- Custom Gutenberg block: `blocks/dealer-finder`
- Slider module in `slider/`
- ACF local JSON in `acf-json/`
- Theme-level templates/styles (`style.css`, `single-michi-product.php`)

## Directory layout

```text
michi-theme/
├── functions.php
├── style.css
├── inc/
├── blocks/
│   └── dealer-finder/
│       ├── block.json
│       ├── src/
│       ├── build/
│       ├── package.json
│       └── README.md
├── updater/
├── slider/
└── acf-json/
```

## Requirements

- WordPress with parent theme `bb-theme` installed
- PHP compatible with your WordPress environment
- Node.js + npm (only needed for block development in `blocks/dealer-finder`)

## Local development

Theme is loaded as a standard WP child theme from:

`wp-content/themes/michi-theme`

Most PHP customizations are split into files required by `functions.php`.

### Work on the Dealer Finder block

```bash
cd blocks/dealer-finder
npm install
npm run build
```

Use `npm run start` in that same folder for watch mode during active block development.

## Update system

Theme updates are configured in `functions.php` using:

- repository: `https://github.com/bonn-tmm/michi-theme`
- branch: `master`
- slug: `michi-theme`

Update checker loader is in `updater/updater.php`.

## Data/API notes

The dealer finder frontend (`blocks/dealer-finder/src/view.js`) consumes:

- `/wp-json/michi/v1/dealers?per_page=500`
- `/wp-json/michi/v1/regions`

Ensure the REST routes registered in `inc/rest-api.php` stay compatible with the expected response shape.

## Release checklist

1. Update theme version header in `style.css`.
2. Build block assets if `blocks/dealer-finder/src/*` changed.
3. Commit and tag release.
4. Push to `main` so updater can detect new version metadata.
