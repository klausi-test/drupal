<?php

/**
 * @file
 * Contains \Drupal\history\Tests\HistoryTest.
 */

namespace Drupal\history\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the History endpoints.
 */
class HistoryTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'history');

  /**
   * The main user for testing.
   *
   * @var objec
   */
  protected $user;

  /**
   * A page node for which to check content statistics.
   *
   * @var object
   */
  protected $test_node;

  public static function getInfo() {
    return array(
      'name' => 'History endpoints',
      'description' => 'Tests the History endpoints',
      'group' => 'History'
    );
  }

  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->user = $this->drupalCreateUser(array('create page content', 'access content'));
    $this->drupalLogin($this->user);
    $this->test_node = $this->drupalCreateNode(array('type' => 'page', 'uid' => $this->user->id()));
  }

  /**
   * Get node read timestamps from the server for the current user.
   *
   * @param array $node_ids
   *   An array of node IDs.
   *
   * @return string
   *   The response body.
   */
  protected function getNodeReadTimestamps(array $node_ids) {
    // Build POST values.
    $post = array();
    for ($i = 0; $i < count($node_ids); $i++) {
      $post['node_ids[' . $i . ']'] = $node_ids[$i];
    }

    // Serialize POST values.
    foreach ($post as $key => $value) {
      // Encode according to application/x-www-form-urlencoded
      // Both names and values needs to be urlencoded, according to
      // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    $post = implode('&', $post);

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => url('history/get_node_read_timestamps', array('absolute' => TRUE)),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Mark a node as read for the current user.
   *
   * @param int $node_id
   *   A node ID.
   *
   * @return string
   *   The response body.
   */
  protected function markNodeAsRead($node_id) {
    return $this->curlExec(array(
      CURLOPT_URL => url('history/' . $node_id . '/read', array('absolute' => TRUE)),
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
      ),
    ));
  }

  /**
   * Verifies that the history endpoints work.
   */
  function testHistory() {
    $nid = $this->test_node->id();

    // Retrieve "last read" timestamp for test node, for the current user.
    $response = $this->getNodeReadTimestamps(array($nid));
    $this->assertResponse(200);
    $json = drupal_json_decode($response);
    $this->assertIdentical(array(1 => 0), $json, 'The node has not yet been read.');

    // View the node.
    $this->drupalGet('node/' . $nid);
    // JavaScript present to record the node read.
    $settings = $this->drupalGetSettings();
    $this->assertTrue(isset($settings['ajaxPageState']['js']['core/modules/history/js/history.js']), 'drupal.history library is present.');
    $this->assertRaw('Drupal.history.markAsRead(' . $nid . ')', 'History module JavaScript API call to mark node as read present on page.');

    // Simulate JavaScript: perform HTTP request to mark node as read.
    $response = $this->markNodeAsRead($nid);
    $this->assertResponse(200);
    $timestamp = drupal_json_decode($response);
    $this->assertTrue(is_numeric($timestamp), 'Node has been marked as read. Timestamp received.');

    // Retrieve "last read" timestamp for test node, for the current user.
    $response = $this->getNodeReadTimestamps(array($nid));
    $this->assertResponse(200);
    $json = drupal_json_decode($response);
    $this->assertIdentical(array(1 => $timestamp), $json, 'The node has been read.');

    // Failing to specify node IDs for the first endpoint should return a 404.
    $this->getNodeReadTimestamps(array());
    $this->assertResponse(404);

    // Accessing either endpoint as the anonymous user should return a 403.
    $this->drupalLogout();
    $this->getNodeReadTimestamps(array($nid));
    $this->assertResponse(403);
    $this->getNodeReadTimestamps(array());
    $this->assertResponse(403);
    $this->markNodeAsRead($nid);
    $this->assertResponse(403);
  }
}
