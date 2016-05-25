# Kirby / Algolia integration
This is a [Kirby](https://getkirby.com) plugin which enables sending content to [Algolia](https://www.algolia.com/) for indexing.

The default indexing method is inspired by the rationale behind [DocSearch](https://community.algolia.com/docsearch/),  featured in this [blog post](https://blog.algolia.com/how-to-build-a-helpful-search-for-technical-documentation-the-laravel-example). It will be referred as *fragment indexing* in this readme and in the code.

## Principle

The basic idea behind fragment indexing is that when people search for a phrase, they expect results that show the searched terms in that phrase within relatively close proximity of each other. For instance, when looking for *red door*, I am more likely to be interested in matches where these two words are in the same sentence (for instance "this *door*, which I see across the room, has a *red* handle")  than matches where the words *red* and *door* appear in different paragraphs.

Algolia already gives access to that metric used in their tie-break algorithm. Through the correct configuration options, this algorithm enables matches with the closest proximity of each other to be pushed forward. Fragment indexing goes a step further by ensuring that matches can only be found within the same fragment. In this case, a fragment consists of a heading and the immediate following text. The fragment stops at the next heading (or the end of the file).

## Setup

### Get the code

   ```
$ git clone [URL] [KIRBY_ROOT]/site/plugins
   ```
where 

- [URL] is this repository URL
- KIRBY_ROOT is the folder where you Kirby site lives

### Configuration

In [KIRBY_ROOT]/site/config/config.php, add the configuration options:

```
c::set('kirby-algolia', array(
  'algolia' => array(
    'application_id' => '[ALGOLIA_APP_ID]', // required
    'index' => '[ALGOLIA_INDEX_NAME]', // required
    'api_key' => '[ALGOLIA_API_KEY]' // required
    ),
  'fields', array(
    'meta' => array('title', 'author'), // example, optional.
    'boost' => array('teaser'), // example, optional
    'main' => array('text') // example, required
  ),
  'content' => array(
    'types' => array('article') //example, required
  )
));
```

ALGOLIA_API_KEY needs to have write access to the ALGOLIA_INDEX_NAME.

Each `fields` array is an array of field ids (defined in the site blueprints): 

- `meta` : if the whole content was indexed as a single record, each field would likely be an attribute on Algolia's side. However, in a fragmented context, we need to decide what fields are content and what fields are metadata attached to that content. This is the purpose of the `meta` array. As a starting point, title and author can be used here.
  - Expects raw text (no Markdown)
- `boost` : allows for a very basic priority system between fields. Typically used for teasers or semantically rich fields. The content in this field should be short and not contain headings as it will not be fragmented.  
  - Expects raw text (no Markdown)
- `main` : this is where you main content lives. Fragmentation will happen when a new heading is found.
  - Expects Markdown or Kirby's markdown flavor

`content`:

- `types`: what content types to index 

## Quick start

1. Set the configuration options.
2. Create a piece of content in Kirby's panel and click 'Save'.
3. Check your Algolia dashboard to see you indexed content.

## Roadmap

- ~~Delete fragments before indexing new ones~~
- ~~Move settings to config file~~
- Non changeable (auto-increment?) page ID to be able to delete fragments when the path to the page (currently page ID) is being changed  
- ~~Batch indexing~~
- ~~Config option to select the type of content that gets indexed~~


## Known limitations

- In case the content of a `main` field does not start with a heading, all content up to the first heading will be ignored.