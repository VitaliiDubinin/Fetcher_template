<?php

namespace Drupal\feeds_fetcher_headers\Feeds\Fetcher;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Feeds\Fetcher\HttpFetcher;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Response;
use Drupal\feeds\FeedInterface;
use GuzzleHttp\Client;

/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "httpfetcherheaders",
 *   title = @Translation("Download from URL additional Headers"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler with additional Headers."),
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm",
 *     "feed" = "Drupal\feeds_fetcher_headers\Feeds\Fetcher\Form\HttpFetcherHeadersFeedForm",
 *   }
 * )
 */
class HttpFetcherHeaders extends HTTPFetcher {



  public function defaultFeedConfiguration() {
    $default_configuration = parent::defaultConfiguration();
    $default_configuration['headers'] = '';
    return $default_configuration;
}

  public function fetch(FeedInterface $feed, StateInterface $state) {
    $sink = $this->fileSystem->tempnam('temporary://', 'feeds_http_fetcher');
    $sink = $this->fileSystem->realpath($sink);
//  dvm($feed);
    $response = $this->get($feed->getSource(), $sink, $this->getCacheKey($feed), $feed->getConfigurationFor($this)['headers']);

//  dvm($response);

    if ($response->getStatusCode() == Response::HTTP_NOT_MODIFIED) {
        $state->setMessage($this->t('The feed has not been updated.'));
        throw new EmptyFeedException();
    }
    return new HttpFetcherResult($sink, $response->getHeaders());
}

    /**
     * {@inheritdoc}
     */
  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   * @param string $sink
   *   The location where the downloaded content will be saved. This can be a
   *   resource, path or a StreamInterface object.
   * @param string $cache_key
   *   (optional) The cache key to find cached headers. Defaults to false.
   * @param string $token
   *   (optional) The AUthorization bearer token. Defaults to null.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   *
   * @see \GuzzleHttp\RequestOptions
   */
  // protected function get($url, $sink, $cache_key = FALSE, $scope = null) {
  protected function get($url, $sink, $cache_key = FALSE, $headers = null) {
    $url = Feed::translateSchemes($url);
    // dpm($headers);
    // $url = 'https://api.severa.visma.com/rest-api/v1/invoices';

    $options = [RequestOptions::SINK => $sink];
    // dpm($options);

 
    $resofrequesttoken = $this->getRequestToken($headers);
    $auth = 'Bearer ' . $resofrequesttoken; 
    $clientid='client_id';
    $clientid_key=\Drupal::service('key.repository')->getKey($clientid)->getKeyValue();

    $options[RequestOptions::HEADERS] = [
      'Client_Id' => $clientid_key,
      'Authorization' => $auth,
    ];
    try {
      $response = $this->client->get($url, $options);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error". %headers', $args));
    }

    if ($cache_key) {
      $this->cache->set($cache_key, array_change_key_case($response->getHeaders()));
    }
    return $response;
  }

  protected function getRequestToken($headers = null, $cache_key = FALSE){

    $client = new Client();

// dvm ($headers);
    if($headers !==null) {

      foreach(explode("\r\n", $headers) as $row) {

        if(preg_match('/(.*?): (.*)/', $row, $matches)) {
          $options[RequestOptions::HEADERS][$matches[1]] = $matches[2];

        }
      }
    }
    // dpm( $options[RequestOptions::HEADERS]);
    if ($cache_key && ($cache = $this->cache->get($cache_key))) {
      if (isset($cache->data['etag'])) {
        $options[RequestOptions::HEADERS]['If-None-Match'] = $cache->data['etag'];
      }
      if (isset($cache->data['last-modified'])) {
        $options[RequestOptions::HEADERS]['If-Modified-Since'] = $cache->data['last-modified'];
      }
    }

// dvm ($headers);
// dpm( $options[RequestOptions::HEADERS]);

    $clientid='client_id';
    $clientid_key=\Drupal::service('key.repository')->getKey($clientid)->getKeyValue();
    $clientsecret='client_secret';
    $clientid_secret=\Drupal::service('key.repository')->getKey($clientsecret)->getKeyValue();



    $message="Expired authorization code";
    $url = 'https://api.severa.visma.com/rest-api/v1.0/token';

    $tok_headers = [
      'Content-Type' => 'application/json',
    ];
    $tok_body = [
        
        'client_Id' => $clientid_key,
        'client_Secret' => $clientid_secret,
        // 'scope' => 'customers:read',
        'scope' => $headers,

    ];

    $result = \Drupal::httpClient()->post($url, [
      'headers' => $tok_headers,
      'body' => json_encode($tok_body),
    ]);




    if ($result->getStatusCode() == 200) {
      $data = json_decode($result->getBody());
      $access_token = $data->access_token;
      return $access_token;
    } elseif ( $result->getStatusCode() === 400) { $data = json_decode($result->getBody());}
     else {
      return [$message];
    }

  }






}
