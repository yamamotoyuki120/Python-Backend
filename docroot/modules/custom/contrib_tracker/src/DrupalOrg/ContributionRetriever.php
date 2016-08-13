<?php

namespace Drupal\contrib_tracker\DrupalOrg;

use Drupal\contrib_tracker\ContributionRetrieverInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Hussainweb\DrupalApi\Request\Collection\CommentCollectionRequest;
use Hussainweb\DrupalApi\Request\Collection\UserCollectionRequest;
use Hussainweb\DrupalApi\Request\NodeRequest;

class ContributionRetriever implements ContributionRetrieverInterface {

  /**
   * @var \Drupal\contrib_tracker\DrupalOrg\Client
   */
  protected $client;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public function __construct(Client $client, CacheBackendInterface $cacheBackend) {
    $this->client = $client;
    $this->cache = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInformation($username) {
    $cid = 'contrib_tracker:user:' . $username;

    if ($cache = $this->cache->get($cid)) {
      $user = $cache->data;
    }
    else {
      $request = new UserCollectionRequest([
        'name' => 'hussainweb',
      ]);

      /** @var \Hussainweb\DrupalApi\Entity\Collection\UserCollection $users */
      $users = $this->client->getEntity($request);
      if (count($users) != 1) {
        throw new \RuntimeException("No user found");
      }

      $user = $users->current();
      $this->cache->set($cid, $user);
    }

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalOrgNode($nid, $skip_cache = FALSE, $cache_expiry = Cache::PERMANENT) {
    $cid = 'contrib_tracker:node:' . $nid;

    if (!$skip_cache && $cache = $this->cache->get($cid)) {
      $node = $cache->data;
    }
    else {
      $req = new NodeRequest($nid);
      $node = $this->client->getEntity($req);

      // Save to cache only if $skip_cache was set to FALSE.
      if (!$skip_cache) {
        $this->cache->set($cid, $node, $cache_expiry);
      }
    }

    return $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalOrgCommentsByAuthor($uid) {
    // @TODO: Support multiple pages and use generators to return results.
    $req = new CommentCollectionRequest([
      'author' => $uid,
    ]);
    /** @var \Hussainweb\DrupalApi\Entity\Collection\CommentCollection $comments */
    $comments = $this->client->getEntity($req);
    return $comments;
  }

}
