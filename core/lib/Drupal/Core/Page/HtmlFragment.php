<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HtmlFragment.
 */

namespace Drupal\Core\Page;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Utility\Title;

/**
 * Response object that contains variables for injection into the html template.
 *
 * @todo Should we have this conform to an interface?
 *   https://drupal.org/node/1871596#comment-7134686
 * @todo Add method replacements for *all* data sourced by html.tpl.php.
 */
class HtmlFragment {

  /**
   * HTML content string.
   *
   * @var string
   */
  protected $content;

  /**
   * The title of this HtmlFragment.
   *
   * @var string
   */
  protected $title = '';

  /**
   * Constructs a new HtmlFragment.
   *
   * @param string $content
   *   The content for this fragment.
   */
  public function __construct($content = '') {
    $this->content = $content;
  }

  /**
   * Sets the response content.
   *
   * This should be the bulk of the page content, and will ultimately be placed
   * within the <body> tag in final HTML output.
   *
   * Valid types are strings, numbers, and objects that implement a __toString()
   * method.
   *
   * @param mixed $content
   *   The content for this fragment.
   *
   * @return self
   *   The fragment.
   */
  public function setContent($content) {
    $this->content = $content;
    return $this;
  }

  /**
   * Gets the main content of this HtmlFragment.
   *
   * @return string
   *   The content for this fragment.
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * Sets the title of this HtmlFragment.
   *
   * Handling of this title varies depending on what is consuming this
   * HtmlFragment object. If it's a block, it may only be used as the block's
   * title; if it's at the page level, it will be used in a number of places,
   * including the html <head> title.
   *
   * @param string $title
   *   Value to assign to the page title.
   * @param int $output
   *   (optional) normally should be left as Title::CHECK_PLAIN. Only set to
   *   PASS_THROUGH if you have already removed any possibly dangerous code
   *   from $title using a function like
   *   \Drupal\Component\Utility\String::checkPlain() or
   *   \Drupal\Component\Utility\Xss::filterAdmin(). With this flag the string
   *   will be passed through unchanged.
   */
  public function setTitle($title, $output = Title::CHECK_PLAIN) {
    if ($output == Title::CHECK_PLAIN) {
      $this->title = String::checkPlain($title);
    }
    else if ($output == Title::FILTER_XSS_ADMIN) {
      $this->title = Xss::filterAdmin($title);
    }
    else {
      $this->title = $title;
    }
  }

  /**
   * Indicates whether or not this HtmlFragment has a title.
   *
   * @return bool
   */
  public function hasTitle() {
    return !empty($this->title);
  }

  /**
   * Gets the title for this HtmlFragment, if any.
   *
   * @return string
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

}
