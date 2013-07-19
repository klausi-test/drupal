<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\Validation\Constraint\UniqueValidator.
 */

namespace Drupal\user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the unique user proeprty constraint, such as name and e-mail.
 */
class UniqueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $field = $this->context->getMetadata()->getTypedData()->getParent();
    $uid = $field->getParent()->id();

    $value_taken = (bool) db_select('users')
      ->fields('users', array('uid'))
      ->condition('uid', (int) $uid, '<>')
      ->condition($field->getName(), db_like($value), 'LIKE')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($value_taken) {
      $this->context->addViolation($constraint->message, array("%value" => $value));
    }
  }
}
