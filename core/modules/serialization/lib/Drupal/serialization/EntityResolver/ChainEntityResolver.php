<?php

/**
 * @file
 * Contains \Drupal\serialization\EntityResolver\ChainEntityResolver
 */

namespace Drupal\serialization\EntityResolver;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Resolver delegating the entity resolution to a chain of resolvers.
 */
class ChainEntityResolver implements EntityResolverInterface {

  /**
   * The concrete resolvers.
   *
   * @var \Drupal\serialization\EntityResolver\EntityResolverInterface[]
   */
  protected $resolvers;

  /**
   * Constructs a ChainEntityResolver object.
   *
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface[] $resolvers
   *   The array of concrete resolvers.
   */
  public function __construct(array $resolvers = array()) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    foreach ($this->resolvers as $resolver) {
      if ($resolved = $resolver->resolve($normalizer, $data, $entity_type)) {
        return $resolved;
      }
    }
  }

}
