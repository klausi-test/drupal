<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\Derivative\EntityDerivative.
 */

namespace Drupal\rest\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource plugin definition for every entity type.
 */
class EntityDerivative implements ContainerDerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs an EntityDerivative object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Implements DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * Implements DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      // Add in the default plugin configuration and the resource type.
      foreach ($this->entityManager->getDefinitions() as $entity_type => $entity_info) {
        $this->derivatives[$entity_type] = array(
          'id' => 'entity:' . $entity_type,
          'entity_type' => $entity_type,
          'serialization_class' => $entity_info['class'],
          'label' => $entity_info['label'],
        );
        // Use the entity links as REST URL patterns if available.
        $this->derivatives[$entity_type]['links']['drupal:create'] = isset($entity_info['links']['drupal:create']) ? $entity_info['links']['drupal:create'] : "/entity/$entity_type";
        // Replace the default cannonical link pattern with a version that
        // directly uses the entity type, because we don't want to hand 2
        // parameters to the entity resource plugin.
        if ($entity_info['links']['canonical'] == '/entity/{entityType}/{id}') {
          $this->derivatives[$entity_type]['links']['canonical'] = "/entity/$entity_type/{id}";
        }
        else {
          $this->derivatives[$entity_type]['links']['canonical'] = $entity_info['links']['canonical'];
        }

        $this->derivatives[$entity_type] += $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }
}
