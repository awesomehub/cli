## Instructions

Analyze the source files under `lists-src/` into structured JSON files under `lists/` that looks like `lists/list.json.sample`:

Each source file defines a list of sources from which you should fetch links, the source types are:

### github.repos

It doesn't need to be fetched or analyzed, add the repo slugs under `data` field to the result file, after merging/deduping the slugs with any existing ones under the same category name (if any).

### github.list

The `data` field represents either a GitHub repo with `README.md` file under repo root or a url to a `README.md` file with a curated list of links/repos that need to fetched then processed as follows:
 - You should parse/fetch every section in the readme and their repos, section headers represent category names.
 - While collecting repo links, you may find off-page references to sections in another curated list, in that case you should follow the link and fetch the repos from there and list it under that category.
 - All category names should be normalized to a standard format like `parent/child` name format, with a maximum of two segments:
     eg. `Communication - Email - Complete Solutions` -> `Email/Complete Solutions`
     eg. `Communication - Social Networks and Forums` -> `Communication/Social Networks and Forums`
 - If you can come up with a better category name that represent the repos listed under the section header, it would be better.
 - Sometimes category names can be long, you should normalize those into a better name that is descriptive and SEO friendly so users can easily locate what they are looking for.
 - If a top-level heading would only produce a single subcategory, collapse it into a single category and give it an SEO-friendly name instead of keeping a parent/child split.
 - Categories should be organized in a sane and logical order, so for example, for an angular list, official repos should be listed first, then other important sections.
 - Sometimes there is a `markdownCategories` field which defines some unneeded headings to ignore or rename, that shouldn't prevent you from parsing all other headers.

### Workflow Summary

To onboard future agents quickly, follow this repeatable process for every collection:

1. Read the relevant spec in `lists-src/<list>.json` and copy any pre-seeded `github.repos` entries into your working set, deduping by slug.
2. For each `github.list`, fetch the referenced README, follow any off-page sections, and harvest every GitHub repository link while ignoring non-GitHub URLs.
3. Normalize headings into `Parent/Child` category names (maximum two segments), applying any `markdownCategories` overrides and reordering categories so important sections surface first.
4. Merge the newly harvested repos with the seeds, remove duplicates across categories, then ensure every category holds at least four repos by rolling undersized buckets into the closest matching category.
5. Emit the final data to `lists/<list>.json`, mirroring the sample structure and updating `options.categoryOrder` to reflect the normalized sequence you produced.

### github.author

Represents a GitHub author profile from which you should fetch all their repos while respecting the rules defined under `options` field like `exclude` regex.
Sometimes there a `categories` option which defines the target categories for those repos, it should be respected as well, it can also a regex to be matched against the repo name, or `*` to match all repos.
The third option is the `category` field which defines the target category for those repos.

Notes:
 - Don't write any code, just do the analyzation yourself and write the result to `lists/`.
 - There is a `config.json` file next to this file with different GitHub auth tokens you can use, in case a token hits the rate limit, you can use the next one.
 - You can use the `cache/` directory to store any temporary data you need to avoid re-fetching the same data multiple times.
 - Your job is to collect github repo slugs like `author/repo`, nothing more, no need to collect any details about those repos.
 - Any links that are not strictly under `github.com` should be ignored (eg. `help.github.com`).
 - If a project entry uses a `*.github.io` URL, convert it to an `owner/repo` slug by mapping the GitHub Pages host and first path segment (for example `https://foo.github.io/bar/` -> `foo/bar`). If the mapping is unclear, treat it as an external link instead.
 - For other external project URLs, fetch the page once; when you can confidently extract a GitHub repository link from the HTML, add that slug, otherwise skip it.
 - Category names should not contain `/` since we are using it as a delimiter to separate main category from sub category.
 - Its fine if repos appear multiple times in different categories, as long as they actually belong to that category. eg. `angular/cli` repo can appear under `official` category and also under `cli tools` category. this is completely fine.
 - If you think a github repo is irrelevant or a bot repo, please exclude it.
 - After processing is finished, if you find a category with less than 4 repos, move those repos to a category to best-matches their name/desc.
 - When rebuilding lists next time, repeat the same flow: merge any existing `sources` seed data, pull GitHub slugs from the referenced awesome list, normalize category names per the rules above, and re-run the "at least four repos" consolidation before writing to `lists/`.
