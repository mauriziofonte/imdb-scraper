<?php
namespace Mfonte\ImdbScraper;

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDomInterface;
use voku\helper\SimpleHtmlDomNodeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
* Class Dom
*
* @package mfonte/imdb-scraper
* @author Maurizio Fonte
*/
class Dom
{
    private static $baseUrl = 'https://www.imdb.com/';
    private static $defaultUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0';
    private static $iso6391AcceptLanguage = [
        'en' => 'en-US,en;q=0.9',                                // English
        'es' => 'es-ES,es;q=0.9,en-US;q=0.5,en;q=0.3',           // Spanish
        'fr' => 'fr-FR,fr;q=0.9,en-US;q=0.5,en;q=0.3',           // French
        'de' => 'de-DE,de;q=0.9,en-US;q=0.5,en;q=0.3',           // German
        'it' => 'it-IT,it;q=0.9,en-US;q=0.5,en;q=0.3',           // Italian
        'pt' => 'pt-BR,pt;q=0.9,en-US;q=0.5,en;q=0.3',           // Portuguese
        'hi' => 'hi-IN,hi;q=0.9,en-US;q=0.5,en;q=0.3',           // Hindi
    ];

    private static $iso6391LcMainCookieValue = [
        'en' => 'en_US',
        'de' => 'de_DE',
        'fr' => 'fr_FR',
        'es' => 'es_ES',
        'it' => 'it_IT',
        'pt' => 'pt_BR',
        'hi' => 'hi_IN',
    ];

    private static $emptyHtml = '<!DOCTYPE html><html lang="en-US" xmlns:og="http://opengraphprotocol.org/schema/"><head><title>IMDb</title></head><body><p></p></body></html>';
    
    /**
     * Fetch and parse the DOM of a remote site
     *
     * @param string $uri
     * @param array $options - Customized "locale", "guzzleLogFile"
     *
     * @return HtmlDomParser
     */
    public function fetch(string $uri, array $options = [])
    {
        $url = $this->createUrl($uri, $options);
        $content = $this->getRemoteContent($url, $options);
        return HtmlDomParser::str_get_html($content);
    }

    /**
     * Fetch and return the raw content of a remote site
     *
     * @param string $url
     * @param array $options - Customized "locale", "guzzleLogFile"
     *
     * @return string
     */
    public function raw(string $url, array $options) : string
    {
        $content = $this->getRemoteContent($url, $options);
        return $content;
    }

    /**
     * Find one or more objects within DOM (if it exists) and return them
     *
     * @param $node
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface|SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface<SimpleHtmlDomInterface>
     */
    public function find($node, string $selector)
    {
        $found = $node->find($selector);
        if (count($found) > 0) {
            return $found;
        } else {
            return $this->emptyElement();
        }
    }

    /**
     * Find one object within DOM (if it exists) and return it
     *
     * @param $node
     * @param string $selector
     *
     * @return SimpleHtmlDomInterface
     */
    public function findOne($node, string $selector)
    {
        $found = $node->findOne($selector);
        if ($found) {
            $found;
        } else {
            return $this->emptyElement();
        }
    }

    /**
     * Find one node with a CSS selector or false, if no element is found.
     *
     * @param string $selector
     *
     * @return false|SimpleHtmlDomInterface
     */
    public function findOneOrFalse($node, string $selector)
    {
        return $node->findOneOrFalse($selector, 0);
    }

    /**
     * Create and parse an empty html string as a DOM element
     *
     * @return SimpleHtmlDomInterface
     */
    private function emptyElement()
    {
        $dom = HtmlDomParser::str_get_html(self::$emptyHtml)->find('p', 0);
        return $dom;
    }

    /**
     * Create a URL with the appropriate locale
     *
     * @param string $uri
     * @param array $options
     *
     * @return string
     */
    private function createUrl(string $uri, array $options)
    {
        if (
            stripos($uri, 'episodes/') === false &&
            isset($options['locale']) &&
            $options['locale'] === 'it'
        ) {
            // IMDB directly supports the Italian locale as a dedicated realm on titles (not on episodes)
            return self::$baseUrl . 'it/' . ltrim($uri, '/');
        }

        return self::$baseUrl . ltrim($uri, '/');
    }

    /**
     * Fetch and return the content of a remote URL
     *
     * @param string $url
     * @param array $options
     *
     * @return string
     */
    private function getRemoteContent(string $url, array $options) : string
    {
        $clientOpts = [
            'allow_redirects' => [
                'max'       => 10,    // Maximum number of redirects
                'strict'    => false, // Use relaxed HTTP RFC compliance
                'referer'   => true,  // Add a Referer header when redirecting
                'protocols' => ['http', 'https'], // Follow only these protocols
            ],
            'headers' => ['User-Agent' => null],
            'verify' => false,
        ];

        if (isset($options['guzzleLogFile']) && $options['guzzleLogFile'] !== null) {
            // if the file does not exist, try and create it
            if (!file_exists($options['guzzleLogFile'])) {
                @touch($options['guzzleLogFile']);

                // Check if the file was created
                if (!file_exists($options['guzzleLogFile'])) {
                    throw new \Exception("Mfonte\ImdbScraper\Dom:: Could not create Guzzle log file \"{$options['guzzleLogFile']}\"");
                }
            }

            // Create a Monolog logger instance
            $logger = new Logger('http_logger');
            $logger->pushHandler(new StreamHandler($options['guzzleLogFile'], Logger::DEBUG));

            // Set up Guzzle handler stack with logging middleware
            $stack = HandlerStack::create();
            $stack->push(Middleware::log($logger, new MessageFormatter(MessageFormatter::DEBUG)));

            // Create a Guzzle client with the handler stack
            $client = new Client(array_merge($clientOpts, ['handler' => $stack]));
        } else {
            // Create a Guzzle client (implements ClientInterface)
            $client = new Client($clientOpts);
        }

        // Add custom headers to the request
        $request = new Request('GET', $url, $this->createGuzzleHeaders($options));

        // Load the URL with the custom client and request
        $response = $client->sendRequest($request);

        // throw an exception if the request was an error (forbidden, server error, etc)
        if ($response->getStatusCode() >= 400) {
            throw new \Exception("Mfonte\ImdbScraper\Dom::fetch: Request to {$url} returned status code {$response->getStatusCode()}");
        } elseif ($response->getStatusCode() === 200) {
            $content = (string) $response->getBody();
        } else {
            // fallback with an empty DOM representation
            $content = self::$emptyHtml;
        }

        return $content;
    }

    /**
     * Return an array of headers for a Guzzle request
     *
     * @param array $options
     *
     * @return array
     */
    private function createGuzzleHeaders(array $options)
    {
        if (isset($options['guzzleHeaders']) && is_array($options['guzzleHeaders'])) {
            $headers = $options['guzzleHeaders'];
        } else {
            $headers = [];
            if (isset($options['locale']) && isset(self::$iso6391AcceptLanguage[$options['locale']])) {
                $headers['Accept-Language'] = self::$iso6391AcceptLanguage[$options['locale']];
            } else {
                $headers['Accept-Language'] = 'en-US,en;q=0.9';
            }

            if (isset($options['locale']) && isset(self::$iso6391LcMainCookieValue[$options['locale']])) {
                $lang = self::$iso6391LcMainCookieValue[$options['locale']];
                $headers['Cookie'] = "lc-main={$lang}; Domain=.imdb.com; Path=/; SameSite=None";
            }

            // add our default headers
            $headers['User-Agent'] = self::$defaultUserAgent;
            $headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
            $headers['Referer'] = 'https://www.imdb.com/';
            $headers['DNT'] = '1';
            $headers['TE'] = 'trailers';
            $headers['Upgrade-Insecure-Requests'] = '1';
            $headers['Sec-Fetch-Dest'] = 'document';
            $headers['Sec-Fetch-Mode'] = 'navigate';
            $headers['Sec-Fetch-Site'] = 'cross-site';
            $headers['Sec-Fetch-User'] = '?1';
            $headers['Sec-GPC'] = '1';
        }

        return $headers;
    }

}
