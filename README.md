# Mfonte IMDb Scraper

A **PHP library** for scraping movie and TV show data from IMDb with ease. This package provides methods to retrieve detailed information about movies and TV shows using best matches or strict year-based queries, **with support for localized searches**.

> [!CAUTION]
> This package is intended for educational and personal use only. Users are responsible for ensuring their use complies with IMDb's Terms of Service and applicable laws. The author does not condone or encourage unauthorized scraping or other activities that violate legal agreements.
> Please, refer to the [IMDb Conditions of Use](https://www.imdb.com/conditions?utm_source) for more information.
> **It is your responsibility to use this package in compliance with IMDb's Terms of Service.**

## List of supported locales

- English (`en-US`) via `en`
- Italian (`it-IT`) via `it`
- Spanish (`es-ES`) via `es`
- French (`fr-FR`) via `fr`
- German (`de-DE`) via `de`
- Portuguese (`pt-BR`) via `pt`
- Indian (`hi-IN`) via `hi`

- - -

## Overview

Mfonte IMDb Scraper is a lightweight, object-oriented library to interact with IMDb. It provides functionalities to:

- Retrieve movie and TV show details by title and year, or IMDb ID, **with robust exception handling**, with support for **multiple locales**.
- Fetch data like plot, actors, genres, ratings, and similar titles.
- Narrow results using "best match" algorithms or strict filters.

Key features include:

- **Localized searches** using the `locale` option.
- Built-in **caching** for optimized performance.
- `v2` tag works from **PHP 8.1** onwards. `v1` tag Works from **PHP 7.3** onwards.

- - -

## Installation

Release tag `v1` is compatible with PHP versions `7.3`, `7.4` and `8.0` (monolog `^2.0`).

Install the package via Composer:

```bash
composer require mfonte/imdb-scraper "^1.0"
```

Release tag `v2` is compatible with PHP versions `8.1`, `8.2`, `8.3`, `8.4` (monolog `^3.0`).

Install the package via Composer:

```bash
composer require mfonte/imdb-scraper "^2.0"
```

- - -

## Usage

### Basic Example

```php
use Mfonte\ImdbScraper\Imdb;
use Mfonte\ImdbScraper\Exceptions\NoSearchResults;
use Mfonte\ImdbScraper\Exceptions\MultipleSearchResults;

// Create an IMDb scraper instance
$imdb = Imdb::new(['locale' => 'en']);

// Fetch movie details using best match
$movie = $imdb->movie('The Godfather');

// Output some details
echo $movie->title;  // The Godfather
echo $movie->year;   // 1972
echo $movie->plot;   // "The aging patriarch of an organized crime dynasty transfers control..."

// When using movie(), tvSeries(), movieByYear(), or tvSeriesByYear(), catch exceptions!
try {
    $movie = $imdb->movie('The Godfather');
} catch (NoSearchResults $e) {
    echo 'No results found!';
} catch (MultipleSearchResults $e) {
    echo 'Multiple results found!';
}

// you can also fetch a Title by its IMDB ID
$item = $imdb->id('tt0068646');
echo $item->title;  // The Godfather

// you can also only search for results
$results = $imdb->search('godfather');
```

All methods return a `Mfonte\ImdbScraper\Title` object with the following properties.

### The `Title` Object

The `Title` object represents detailed information about a movie or TV show fetched using the `ImdbScraper` library. It encapsulates all key attributes of a title, including metadata, cast, genres, and links to related content.

#### Properties of the `Title` Object

| **Property** | **Type** | **Description** |
| --- | --- | --- |
| **`id`** | `string` | The unique IMDb ID of the title (e.g., `"tt1234567"`). |
| **`isTvSeries`** | `bool` | Indicates whether the title is a TV series (`true`) or a movie (`false`). |
| **`link`** | `string` | The URL of the IMDb page for the title. |
| **`title`** | `string` | The official title of the movie or TV show. |
| **`originalTitle`** | `string` | The original title of the movie or TV show. |
| **`year`** | \`int | null\` |
| **`length`** | \`string | null\` |
| **`rating`** | \`float | null\` |
| **`ratingVotes`** | \`int | null\` |
| **`popularityScore`** | \`int | null\` |
| **`metaScore`** | \`int | null\` |
| **`genres`** | `array` | A list of genres associated with the title (e.g., `["Action", "Drama"]`). |
| **`posterUrl`** | \`string | null\` |
| **`trailerUrl`** | \`string | null\` |
| **`plot`** | \`string | null\` |
| **`actors`** | `Dataset` | A dataset containing the cast members of the title. (`Person` objects) |
| **`similars`** | `Dataset` | A dataset containing similar titles to the one fetched. (`Title` objects) |
| **`seasonRefs`** | `array` | A list of season numbers for a TV series (e.g., `[1, 2, 3, 4]`). |
| **`seasons`** | `Dataset` | A dataset containing detailed information about seasons for a TV series. (`Season` objects) |
| **`metadata`** | `array` | Raw metadata associated with the title. |

- - -

## Options

The scraper provides various configuration options during initialization:

| Option | Default | Description |
| --- | --- | --- |
| `cache` | `false` | Enables caching of results. |
| `locale` | `en` | Sets the locale for searches (e.g., `it` for Italian). |
| `seasons` | `false` | If `true`, fetches season data for TV shows. |
| `guzzleLogFile` | `null` | File path for logging HTTP requests (useful for debugging). |

### Example: Setting Options

```php
use Mfonte\ImdbScraper\Imdb;

// Enable caching and use Italian locale
$imdb = Imdb::new([
    'cache' => true,
    'locale' => 'it'
]);

// Fetch a localized movie
$movie = $imdb->movie('La ricerca della felicità');
echo $movie->title;  // La ricerca della felicità
```

- - -

## Methods

### Best Match Overview

The `movie()` and `tvSeries()` methods find the best match for a given title. The library uses a **Levenshtein algorithm** to rank results and selects the closest match.

#### Example with `movie()`

```php
use Mfonte\ImdbScraper\Imdb;

// Fetch the best match for a movie title
$movie = Imdb::new()->movie('godfather');
echo $movie->id;       // tt0068646
echo $movie->title;    // The Godfather
echo $movie->year;     // 1972
```

- - -

### By Year Overview

The `movieByYear()` and `tvSeriesByYear()` methods perform a strict search for titles matching the specified year. If no exact match is found, the library checks for movies released one year before or after the given year.

#### Example with `movieByYear()`

```php
use Mfonte\ImdbScraper\Imdb;

// Fetch a movie by title and year
$movie = Imdb::new()->movieByYear('from dusk dawn', 1996);
echo $movie->id;       // tt0116367
echo $movie->title;    // From Dusk Till Dawn
echo $movie->year;     // 1996
```

- - -

## Handling Exceptions in `movie()`, `tvSeries()`, `movieByYear()`, and `tvSeriesByYear()`

The methods `movie()`, `tvSeries()`, `movieByYear()`, and `tvSeriesByYear()` in the `Imdb` class throw exceptions when specific conditions are not met during the search:

1. **`NoSearchResults` Exception**:
    - Thrown when no results are found for the provided query.
    - Example:

        ```php
        use Mfonte\ImdbScraper\Imdb;
        use Mfonte\ImdbScraper\Exceptions\NoSearchResults;

        $imdb = Imdb::new(['locale' => 'en']);

        try {
            $movie = $imdb->movie('nonexistent title');
        } catch (NoSearchResults $e) {
            echo 'No results found for the query.';
        }
        ```

2. **`MultipleSearchResults` Exception**:
    - Thrown when the query returns multiple results but the method cannot narrow them down to a single title.
    - Example:

        ```php
        use Mfonte\ImdbScraper\Imdb;
        use Mfonte\ImdbScraper\Exceptions\MultipleSearchResults;

        $imdb = Imdb::new(['locale' => 'en']);

        try {
            $movie = $imdb->movie('godfather');
        } catch (MultipleSearchResults $e) {
            echo 'Multiple results found for the query.';
        }
        ```

3. **`BadMethodCall` Exception**:
    - Thrown when invalid input is provided to the `id()` method or other API methods.
    - Example:

        ```php
        use Mfonte\ImdbScraper\Imdb;
        use Mfonte\ImdbScraper\Exceptions\BadMethodCall;

        $imdb = Imdb::new(['locale' => 'en']);

        try {
            $movie = $imdb->id('invalid_id');
        } catch (BadMethodCall $e) {
            echo 'Invalid IMDb ID provided.';
        }
        ```

- - -

## The `id()` Method

The `id()` method allows you to fetch detailed information about a movie or TV show using its unique IMDb ID.

```php
use Mfonte\ImdbScraper\Imdb;

$imdb = Imdb::new(['locale' => 'en']);

$movie = $imdb->id('tt0110912'); // Pulp Fiction IMDb ID

echo $movie->title;  // Pulp Fiction
echo $movie->year;   // 1994
```

**Key Features**:

- Accepts only valid IMDb IDs in the format `tt1234567`.
- Throws a `BadMethodCall` exception if the input is invalid.

- - -

## The `search()` Method

The `search()` method performs a general search query and returns a `Dataset` containing `SearchResult` objects for all matches.

```php
use Mfonte\ImdbScraper\Imdb;

$imdb = Imdb::new(['locale' => 'en']);

$results = $imdb->search('godfather');
foreach ($results as $result) {
    echo $result->title . " (" . $result->year . ")" . PHP_EOL;
}
```

**Key Features**:

- Returns a `Dataset` of `SearchResult` objects.
- Each `SearchResult` includes fields like `id`, `title`, `year`, `type`, and more.

- - -

## Summary of Exceptions and Methods

| **Method** | **Description** | **Exceptions Thrown** |
| --- | --- | --- |
| `movie($title)` | Fetches the best match for a movie title. | `NoSearchResults`, `MultipleSearchResults` |
| `tvSeries($title)` | Fetches the best match for a TV series title. | `NoSearchResults`, `MultipleSearchResults` |
| `movieByYear($title, $year)` | Fetches a movie by title and year. | `NoSearchResults`, `MultipleSearchResults` |
| `tvSeriesByYear($title, $year)` | Fetches a TV series by title and year. | `NoSearchResults`, `MultipleSearchResults` |
| `id($imdbId)` | Fetches a movie or TV show by IMDb ID (e.g., `tt1234567`). | `BadMethodCall` |
| `search($query)` | Performs a general search and returns a `Dataset` of `SearchResult` objects. | \-  |

With these robust exception-handling mechanisms and versatile methods, the `Imdb` class offers both flexibility and reliability for your IMDb scraping needs.

## Advanced Features

### Caching

Enable caching for faster repeated lookups. Cache works seamlessly and stores results locally.

```php
$imdb = Imdb::new(['cache' => true]);
$movie = $imdb->movie('Inception');
```

### Locale

Retrieve localized movie titles, plots, and other data by setting the `locale` option.

```php
$imdb = Imdb::new(['locale' => 'it']);
$movie = $imdb->movie('Pursuit Happyness');
echo $movie->title;  // La ricerca della felicità
```

- - -

## Contributing

Contributions are welcome! Feel free to open an issue or submit a pull request.

- - -

## License

This project is licensed under the MIT License. See the LICENSE file for details.