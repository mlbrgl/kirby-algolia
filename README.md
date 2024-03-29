# Kirby / Algolia integration
![kirby 2](https://img.shields.io/badge/kirby-2-black) ![kirby 3](https://img.shields.io/badge/kirby-3-black) 

This is a [Kirby](https://getkirby.com) plugin which enables incrementally sending new or updated content to [Algolia](https://www.algolia.com/) for indexing. Supports batch indexing all content through a CLI script.

The default indexing method is inspired by the rationale behind [DocSearch](https://community.algolia.com/docsearch/), featured in this [blog post](https://blog.algolia.com/how-to-build-a-helpful-search-for-technical-documentation-the-laravel-example). It will be referred as _fragment indexing_ in this readme and in the code.

## Principle

The basic idea behind fragment indexing is that when people search for a phrase, they expect results that show the searched terms in that phrase within relatively close proximity of each other. For instance, when looking for _red door_, I am more likely to be interested in matches where these two words are in the same sentence (for instance "this _door_, which I see across the room, has a _red_ handle") than matches where the words _red_ and _door_ appear in different paragraphs.

Algolia already gives access to that metric used in their tie-break algorithm. Through the correct configuration options, this algorithm enables matches with the closest proximity of each other to be pushed forward. Fragment indexing goes a step further by ensuring that matches can only be found within the same fragment. In this case, a fragment consists of a heading and the immediate following text. The fragment stops at the next heading (or the end of the file).

## Quick start

1. Go through the [setup](#Setup).
2. Create a piece of content in Kirby's panel and click 'Save'.
3. Check your Algolia dashboard to see your indexed content.

## Batch indexing

Run `php path/to/batch-index.php` from either the site root or the plugin folder. Uses the same [configuration options](#Configuration) as the incremental indexing.

## Setup

### Get the code

```
$ git clone https://github.com/mlbrgl/kirby-algolia [KIRBY_ROOT]/site/plugins
```

where KIRBY_ROOT is the folder where you Kirby site lives.

Then, run `composer install`.

### Configuration

In [KIRBY_ROOT]/site/config/config.php, add the configuration options:

```
// Example configuration options in config.php

$config = [
  "mlbrgl.kirby-algolia" => [
    "algolia" => [
      "application_id" => "[ALGOLIA_APP_ID]", // required
      "index" => "[ALGOLIA_INDEX_NAME]", // required
      "api_key" => "[ALGOLIA_API_KEY]" // required, needs to have write access to the ALGOLIA_INDEX_NAME
      ],
      "fields" => [
        "article" => [ // example, at least one blueprint required
          "meta" => ["title", "datetime", "author"], // example, optional. See description below.
          "boost" => ["teaser"], // example, optional. See description below.
          "main" => ["text"], // example, optional. See description below.
        ],
        ... // other blueprints, option
      ],
      "active" => true, // false to parse without sending to Algolia
  ],
  ... // other config
;
```

Each blueprint array is an array of field IDs (defined in the site blueprints):

- `meta` : if the whole content was indexed as a single record, each field would likely be an attribute on Algolia's side. However, in a fragmented context, we need to decide what fields are content and what fields are metadata attached to that content. This is the purpose of the `meta` array. As a starting point, title and author can be used here.
  - Expects raw text (no Markdown)
- `boost` : allows for a very basic priority system between fields. Typically used for teasers or semantically rich fields. The content in this field should be short and not contain headings as it will not be fragmented.
  - Expects raw text (no Markdown)
- `main` : this is where you main content lives. Fragmentation will happen when a new heading is found.
  - Expects Markdown or Kirby's markdown flavor

## Known limitations

- Date fields are converted to timestamp (only format supported by Algolia for sorting) only if the date field is either called `date` or `datetime` and if it is listed in the `meta` fields.
- ~~In case the content of a `main` field does not start with a heading, all content up to the first heading will be ignored.~~
- Headings are only recognized as such when they start on the first character of the line (no leading spaces)

## Testing

Run tests with `composer test`.

Tests inherit the configuration from the parent site. This can be overriden in `bootstrap.php`.

## Memory usage

Parsed 872 pages (8275 fragments) in 4.1703071594238 s.  
Memory usage: 21.64 MB

## Supported Kirby versions

- Kirby 3: v3.x.x releases
- Kirby 2: v1.x.x releases

## Unlicence

This work is unlicenced along the terms of <https://unlicense.org/>.
