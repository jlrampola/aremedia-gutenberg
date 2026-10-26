# Are Media Category Articles

A standalone Gutenberg block for magazine-style landing pages: the newest article
from a category appears as a hero, followed by up to four smaller article cards.
Articles update on subsequent page requests without resaving the block.

![Desktop block layout with missing-image placeholders](docs/images/block-desktop.png)

[Mobile screenshot](docs/images/block-mobile.png). These screenshots show the real
PHP template and CSS in headless Edge with offline sample data. They are not
screenshots of a running WordPress installation.

## Requirements

| Component | Requirement | Verification in this workspace |
| --- | --- | --- |
| WordPress | 6.6 or newer; block editor enabled | Local 7.1.2: active plugin, real REST query and draft frontend checked; full integration pending |
| PHP | 7.4 or newer | Syntax and offline tests passed on 8.2.29 |
| Browser | Current Chrome/Edge, Firefox or Safari with CSS container queries | Offline layout checked in installed Edge |
| Node.js, development only | 22.22.2+ on the 22 LTS line, or 24.15.0+ on the 24 LTS line | Build/test/watch checked on 24.21.0, npm 11.19.0 |
| Composer 2, standards checks only | Installs WordPress Coding Standards | Not installed in this session |

The minimum WordPress/PHP versions are compatibility targets, not a tested version
matrix. Node.js, npm and Composer are unnecessary for installing the prepared ZIP.
No runtime library needs to be installed: the editor uses WordPress's own packages.

## Installation

1. Back up your site and use a development/staging site for initial checks.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Select `aremedia-gutenberg-1.0.0.zip`, install and activate it.
4. Open a page with the block editor and insert **Category Articles**.

For a source checkout, place this repository at
`wp-content/plugins/aremedia-gutenberg/` and activate **Are Media Category Articles**.
The committed `build/` directory contains the required production assets. If you
change `src/`, run `npm run build` before using the plugin.

Deactivation removes the dynamic output; existing block settings and sponsor
metadata are retained. No posts, categories or metadata are deleted.

## Usage

1. Insert **Category Articles** using the block inserter.
2. Select a category in the block's **Article settings** sidebar panel.
3. Set **Display category label** to show or hide labels on every article.
4. Set **Show subtext (excerpt)** to show or hide the hero's short excerpt.
5. Check the live preview, save the page, then view its frontend.

The newest published article is the hero. The next four appear in reading order
(left to right, then next row). Publication date controls ordering, not last edit
time. Equal dates are resolved by post ID, newest ID first; sticky priority is
ignored. Only posts assigned directly to the selected category are included.

Both toggles default to on. Fewer than five posts produce fewer cards. Empty or
deleted categories produce an editor message and no frontend output. Missing
featured images produce neutral placeholders. Drafts, private posts and
password-protected posts are excluded from both queries.

### Optional sponsored card

Edit an article and enable the editor's native **Custom fields** panel in
Preferences if needed (labels can vary by WordPress version). Add a custom field
named `aremedia_sponsor`, with the sponsoring brand's plain-text name, and save.
The block displays **Sponsored by …** when that article occupies a smaller card.
It does not display the sponsor on the hero. Only users allowed to edit that post
can change the registered field through REST. The value is public display content;
do not store confidential information in it.

## Development

Run commands from the repository root. Put PHP on PATH for the PHP commands, or
replace `php` with your PHP executable's full path. Local's **Open site shell** can
provide its PHP environment.

```sh
npm run build
npm start
npm test
npm run test:php
```

- `build` copies PHP/CSS/metadata and wraps the editor script with its `block.json`
  definition and dependency manifest. It requires only Node.js and makes no downloads.
- `start` builds once and watches `src/`; stop with Ctrl+C.
- `test` runs the Node editor contract tests and checks generated assets.
- `test:php` runs offline PHP renderer/query contract tests.

On Windows, `npm run package` creates and checks the installable ZIP in
`artifacts/` after a build. It includes runtime assets and documentation, not your
WordPress installation, Git data, dependencies or browser profiles. Use the
separate source archive or Git checkout for development files.

The JavaScript uses native `createElement` and WordPress's registered script
handles, so a bundler/transpiler is unnecessary. All registration settings come
from `block.json`; frontend output is generated from current posts by PHP.

### Coding standards tools

With registry/network access and Composer 2 available:

```sh
npm install
npm run lint:js
npm run lint:css
composer install
composer lint
```

`@wordpress/scripts` is a development-only dependency for the official JS/CSS
checks. Composer supplies WPCS/PHPCS. No downloaded code runs on the frontend.
The declared WordPress scripts version is 37.0.0 (matching the consulted upstream
package source). Registry resolution and transitive lockfiles remain pending
because this session cannot access npm/Composer downloads. Do not substitute
`npm ci` until a lockfile has been resolved and committed.

### Offline browser layout checks on Windows

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tools/check-layout.ps1 -Php 'C:/path/to/php.exe'
```

The script uses installed Edge, renders the actual PHP template with controlled
fixtures, checks desktop and a 390px mobile container, and saves screenshots in
`docs/images/`. Generated HTML, browser profiles and logs stay in ignored
`artifacts/`. It uses a disposable headless profile with browser sandboxing disabled
for these trusted, self-contained offline fixtures; it never opens a live site.
This is not a substitute for the WordPress acceptance checks below.

### Project structure

```text
aremedia-gutenberg.php     Plugin setup, shared query, scoped REST filter, sponsor meta
src/block.json            Block metadata, controls schema and asset paths
src/index.js              Inspector controls and native REST/core-data preview
src/render.php            Dynamic frontend template
src/style.css             Scoped desktop/mobile layout shared with the editor
build/                    Generated files required by WordPress; committed
tools/build.mjs           Dependency-free build and watch command
tools/check-layout.ps1    Offline Edge layout checks and screenshot capture
tools/package.ps1         Windows installable ZIP creation and content checks
tests/                    Renderer, editor and build contracts; offline layout fixture
docs/validation.md        Evidence, acceptance checklist and outstanding checks
docs/superpowers/         Approved design and implementation plan/ledger
docs/images/              Actual offline template screenshots
```

### Implementation assumptions

- Selecting a category includes direct assignments, not child categories. Labels
  always use the selected category, even for articles with several categories.
- No category is selected automatically. Both display toggles default to enabled.
- PHP orders by publication date and ID descending. A scoped `aremedia_category`
  parameter on the existing posts REST endpoint applies the same shared query in
  the editor. Other REST requests are unchanged; no endpoint is added.
- Hero subtext uses WordPress's block-aware excerpt API (manual excerpt or
  generated text), capped at 30 words. The editor trims the REST excerpt to the same limit.
- Sponsored cards still obey the global category-label toggle. The reference
  omits a label on its sponsored example, but the written requirement makes that
  toggle apply above headlines; this interpretation was approved.
- The frontend uses an H2 hero and H3 cards, decorative linked images, a meaningful
  headline link, visible focus styling, and no frontend JavaScript.
- PHP output is escaped for its HTML context. User-facing strings use the
  `aremedia-gutenberg` text domain; editor script translations are registered.

## Validation and remaining limits

Passed here: seven Node tests, 47 PHP assertions, PHP syntax checks, production
build, watch smoke check, and 14 offline Edge layout assertions. See the full
[acceptance results](docs/validation.md) for scope and commands.

The local site is now reachable. Authenticated WordPress REST checks confirmed
the plugin is active, five demo articles have featured images, newest-first hero
selection works, sponsor metadata is saved, and the actual draft frontend renders
one hero and four cards. Demo category: **Gardening Demo**; example page ID: **25**.
Those sample posts are published only on the local development site; the example
page remains a draft.

Not verified: installation from the ZIP, interactive editor behavior and
saving/reopening, database privacy/category edge cases, refresh after another
publication, theme/keyboard/screen-reader behavior, and the minimum version
matrix. Npm installation failed with `EACCES`, so official JS/CSS lint and PHPCS
have not passed or been claimed as passed. Earlier HTTP 502 and localhost
restrictions prevented runtime checks; the subsequent real REST/frontend checks
above supersede that earlier blocker for those paths only.

Page/CDN caches may delay newly published articles until their cache is purged or
expires. Already-open pages do not poll for changes. Third-party excerpt filters
and shortcode-generated content can produce preview/frontend excerpt differences;
use a manual excerpt where exact editorial copy is important. The neutral
placeholder is intentional, not a stock photograph. Custom-fields availability
can be affected by other editorial plugins. Translations are ready to supply,
but no language packs are bundled.

Before production use, run the remaining WordPress/browser and standards checks
in an environment with access. Future work should prioritize the compatibility
matrix and an automated WordPress integration suite, rather than extra controls.
Plugin source and commits are local on `feat/category-articles`; no plugin code
has been pushed, merged, published as a release, or deployed to a remote site.

## AI tool usage

AI tools are permitted by the assessment. OpenAI Codex generated substantial
portions of the implementation, tests and documentation in this repository.
The changes listed below were also made with Codex assistance; they are not
presented as unaided human edits.

| Tool/workflow used | What it was used for |
| --- | --- |
| OpenAI Codex | Reading the task document/design reference; inspecting the project; proposing the approved design; writing PHP, JavaScript, CSS, tests, build/package tools and documentation; diagnosing the local 502; creating local demo content |
| Codex reviewer subagents | Checking the design and independently reviewing implementation; identifying REST-context and excerpt discrepancies; rechecking fixes |
| Superpowers skills | Requirements/design review, implementation planning, test-first checks, debugging, review and verification workflow |
| Ponytail and Caveman skills | Keeping implementation scope small and chat updates concise; these are instructions used within Codex, not separate coding assistants |
| Codex browsing and GitHub integration | Consulting official WordPress references and verifying repository/account access; no source push or hiring-team invitation was sent |

### Revisions to AI-generated output

- Changed category and post preview queries to explicit REST `context: view`, so
  Authors/Contributors are not blocked by taxonomy edit permissions.
- Replaced raw-content excerpt extraction with WordPress's block-aware
  `get_the_excerpt()` before plain-text trimming.
- Moved the frontend sponsor label into the card's text area to match the editor.
- Added explicit translation calls for the block title and description.
- Added failing regressions for REST context and block-aware excerpts before
  applying the fixes, then reran the affected suites.
- Corrected the offline mobile fixture after detecting headless Edge's minimum
  window width; added an intended-width assertion and recaptured screenshots.
- Updated documentation to distinguish offline checks, actual WordPress evidence
  and checks that could not be completed. No unrun standards checks are claimed.

The candidate must personally review and understand the final code, validate its
behavior and be able to explain and change it. A completed unaided walkthrough
or manual candidate code revision has not been recorded by this session.

## Assessment timeline and submission

- Task issued: **26 October 2026**, confirmed by the candidate as “today.”
- Allowed period: **five working days** from the issue date. The exact deadline,
  counting convention, timezone and holiday calendar were not supplied.
- Conservative working target: **Friday, 30 October 2026**, counting the issue
  date as day one of a Monday-Friday workweek. This is a planning target, not a
  deadline confirmed by the hiring team.
- Estimated effort in the brief: **4–6 hours**. This is the assessment's estimate,
  not a claim about actual time spent or an independently recorded timesheet.
- Repository: https://github.com/jlrampola/aremedia-gutenberg (private).
- Meaningful incremental commits are retained locally. History must not be
  rewritten to imply unaided authorship or a fabricated development sequence.
- Submission remains pending: finish the open validation items, obtain explicit
  push authorization, push the incremental history, verify hiring-team access,
  then send the repository link. The repository URL alone does not establish that
  current local source has been submitted.
- Hiring-team access is not confirmed. Their GitHub usernames or team identity
  are still needed, and the invited reviewers must be able to open the private
  repository before submission is marked complete.

## Live review preparation

The hiring team will schedule a **60-minute live review after submission**,
including code explanation and small live changes **without AI tools**. Use the
[walkthrough and practice checklist](docs/live-review.md) to prepare, then rehearse
with AI assistants and suggestions disabled. Personal readiness must be confirmed
by the candidate; this documentation does not claim that rehearsal has happened.

## Official references

- [Block metadata and registration](https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-json/)
- [Core data and REST preview](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-core-data/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [Escaping output](https://developer.wordpress.org/apis/security/escaping/)
- [Internationalization](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/)

License: GPL-2.0-or-later.
