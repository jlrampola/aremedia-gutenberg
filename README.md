# Are Media Category Articles

A Gutenberg block that displays the newest article from a selected category as a
hero, followed by up to four smaller cards. Content updates on new page requests.

![Category Articles block in WordPress](docs/images/wordpress-desktop.png)

Screenshot shows the WordPress frontend with five demo articles.
[Mobile screenshot](docs/images/wordpress-mobile.png) ·
[Reference layout comparison](docs/images/block-reference.png).
[Typography comparison](docs/images/block-typography.png).

## System requirements

| Component | Supported target |
| --- | --- |
| WordPress | 6.6+ with the block editor |
| PHP | 7.4+ |
| Node.js, required to build | 22.22.2+ on Node 22, or 24.15.0+ on Node 24 |
| Browser | Current Chrome/Edge, Firefox or Safari with CSS container queries |

Checks have used WordPress 7.1.2, PHP 8.2.29 and Node.js 24.21.0. Minimum versions
have not been tested. Composer 2 is needed only for PHP coding-standards checks.

## Installation

1. Clone or download the source repository into a folder named `aremedia-gutenberg`.
2. Open a terminal in that folder and run `npm run build`.
3. Run `npm test` to check the generated assets and editor contracts.
4. Place the folder in `wp-content/plugins/aremedia-gutenberg/`, including the
   newly generated `build/` directory.
5. Activate **Are Media Category Articles** in WordPress, then insert the block.

The source download excludes `build/`; generate it **before activation**.
Building needs Node.js but does not need `npm install` or Composer.

On Windows, after building, `npm run package` creates an installable ZIP in
`artifacts/`. Upload that ZIP through **Plugins → Add New Plugin → Upload Plugin**.
This generated installation ZIP includes the runtime `build/` files.

## Usage

1. Insert **Category Articles** and open its **Article settings** sidebar.
2. Choose a category.
3. Toggle **Display category label** and **Show subtext (excerpt)** as needed.
4. Check the preview and save the page.

The newest published post becomes the hero; the next four appear left to right,
then on the next row. Equal publication dates are ordered by post ID descending.
Sticky priority is ignored. Fewer posts produce fewer cards; empty categories
render nothing on the frontend. Missing images use neutral placeholders.

Optional sponsor: add a post custom field named `aremedia_sponsor` with a brand
name. Smaller cards display “Sponsored by …”; the hero does not. Enable the native
Custom fields panel in editor Preferences if needed.

## Development

```text
aremedia-gutenberg.php   Plugin setup, shared query and sponsor metadata
src/block.json          Block registration and saved settings
src/index.js            Inspector controls and REST editor preview
src/render.php          Dynamic frontend template
src/style.css           Shared responsive layout
build/                  Generated locally by npm run build; ignored by Git
tests/                 Editor, renderer and build checks
tools/                 Build/watch, browser checks and Windows packaging
docs/                  Screenshots, validation and review preparation
```

Run from the repository root:

```sh
npm run build       # Generate production assets; no downloads needed
npm start           # Build and watch src/; stop with Ctrl+C
npm test            # Node tests
npm run test:php    # PHP tests; PHP must be on PATH
```

For official coding-standards checks:

```sh
npm install
npm run lint:js
npm run lint:css
composer install
composer lint
```

On Windows, `npm run package` creates the checked installable ZIP in `artifacts/`.
Edit `src/`, then rebuild; do not edit generated files in `build/`.

### Implementation assumptions

- Only direct category assignments are included, not child categories.
- Labels use the selected category. Both display toggles default to on.
- Only published, non-password-protected posts appear.
- Hero excerpts use WordPress's excerpt API, trimmed to 30 words.
- Headlines use Times New Roman; labels, excerpts and sponsor prefixes use locally
  bundled Jost, matched against `reference/typography.png`.
- Hero subtext stays at 15px, normal style and weight 400 at every block width.
- PHP and the editor share query rules through the existing posts REST endpoint.
- Frontend output is dynamic and needs no JavaScript. Open pages do not poll;
  page/CDN caches may delay updates.

## AI tool usage

| Which tools were used | What they were used for |
| --- | --- |
| OpenAI Codex, including reviewer agents | Requirements/design, implementation, tests, documentation, debugging and code review |
| Superpowers, Ponytail and Caveman | Workflow, scope and response-style instructions within Codex |
| Codex browsing and GitHub integration | Official WordPress references and repository/account verification |

**Modifications to generated output:** set public REST context for editorial
roles, switched to WordPress's block-aware excerpt API, aligned sponsor placement,
added block-title/description translations, corrected the mobile test fixture,
and matched measured reference typography, image proportions, spacing and rules.
Regression checks were added for the REST-context and excerpt fixes.

Substantial code, tests and documentation were AI-generated. These revisions were
also AI-assisted; no unaided human edits or completed no-AI rehearsal are claimed.
The candidate must personally review, understand and explain the final code.

## Known limitations and possible improvements

- Minimum-version, cross-browser and assistive-technology checks remain incomplete.
  WordPress editor controls, save/reopen and frontend checks passed.
  See [validation results](docs/validation.md).
- Registry access blocked standards-tool installation; JS/CSS lint and PHPCS have
  not passed. Dependency lockfiles remain pending.
- Third-party excerpt filters can cause preview differences. Manual excerpts give
  editors control over exact copy.
- Language packs are not bundled. Editorial plugins may affect Custom fields.
- Prioritize remaining WordPress integration, compatibility and accessibility
  checks before adding more controls.

[Repository](https://github.com/jlrampola/aremedia-gutenberg) ·
[Live-review preparation](docs/live-review.md). No source push or submission yet;
hiring-team access still needs verification. License: GPL-2.0-or-later.
Bundled Jost fonts use the [SIL Open Font License](src/fonts/OFL.txt).
