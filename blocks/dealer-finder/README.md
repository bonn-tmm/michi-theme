# Michi Dealer Finder Block

A custom Gutenberg block for displaying Michi dealers with country and state/region filtering.

## Features

- **Country Filter**: Dropdown to select country first
- **State/Region Filter**: Cascading dropdown that populates based on selected country
- **State Sidebar**: Optional sidebar showing all states/regions in the selected country
- **Dealer Cards**: Display dealer information including:
  - Name
  - Address
  - Phone
  - Email
  - Website
  - Dealer types/services

## Installation

1. The block is automatically registered when the theme is active
2. Build the block assets:
   ```bash
   cd wp-content/themes/twentytwentyfive-child/blocks/dealer-finder
   npm install
   npm run build
   ```

## Usage

1. In the WordPress editor, add a new block
2. Search for "Michi Dealer Finder"
3. Customize the heading and description using the block editor
4. Toggle the state sidebar visibility in the block settings (right sidebar)

## Block Settings

- **Heading**: Customizable heading text (default: "Find a Dealer by State")
- **Subheading**: Customizable description text
- **Show State Sidebar**: Toggle to show/hide the state list sidebar

## Data Source

The block fetches dealer data from the REST API endpoint:
- **Endpoint**: `/wp-json/michi/v1/dealers`
- **Source**: `wpsl_stores` custom post type
- **Fields**: Includes all WP Store Locator meta fields and ACF dealer type fields

## Development

- **Edit mode**: `npm run start` (watches for changes and rebuilds automatically)
- **Production build**: `npm run build`

## File Structure

```
dealer-finder/
├── block.json          # Block configuration
├── index.js            # Block registration and editor UI
├── view.js             # Frontend JavaScript
├── style.css           # Frontend and editor styles
├── editor.css          # Editor-only styles
├── package.json        # NPM dependencies
└── README.md           # This file
```
