<?php
/**
 * @file
 * Contains \Drupal\xmlrpc\XmlRpcTrait.
 */

namespace Drupal\xmlrpc;

use Drupal\Core\Url;

trait XmlRpcTrait {

  /**
   * The XML-RPC server endpoint.
   *
   * @var string
   */
  protected $endPoint;

  /**
   * Returns the URI of the server XML-RPC endpoint.
   *
   * @return string
   *   The server endpoint URI.
   */
  public function getEndpoint() {
    if (!isset($this->endPoint)) {
      $uri = Url::fromUri($GLOBALS['base_url'] . '/xmlrpc');
      $this->endPoint = $uri->toUriString();
    }

    return $this->endPoint;
  }

}
