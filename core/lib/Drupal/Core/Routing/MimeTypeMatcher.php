<?php

/**
 * @file
 * Contains Drupal\Core\Routing\MimeTypeMatcher.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\ContentNegotiation;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * This class filters routes based on the media type.
 *
 * Two HTTP headers are examined to match routes:
 *  - Accept: routes specifying a _format requirement.
 *  - Content-type: routes specifying a _content_type_format requirement.
 */
class MimeTypeMatcher implements RouteFilterInterface {

  /**
   * The content negotiation library.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $contentNegotiation;

  /**
   * Constructs a new MimeTypeMatcher.
   *
   * @param \Drupal\Core\ContentNegotiation $cotent_negotiation
   *   The content negotiation library.
   */
  public function __construct(ContentNegotiation $content_negotiation) {
    $this->contentNegotiation = $content_negotiation;
  }

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    $collection = $this->filterAcceptHeaders($collection, $request);
    return $this->filterContentTypeHeaders($collection, $request);
  }

  /**
   * Filters routes based on the HTTP Accept header.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection against which to match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A non-empty RouteCollection of matched routes.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
   *   If no routes could be matched against the Accept header.
   */
  protected function filterAcceptHeaders(RouteCollection $collection, Request $request) {
    // Generates a list of Symfony formats matching the acceptable MIME types.
    // @todo replace by proper content negotiation library.
    $acceptable_mime_types = $request->getAcceptableContentTypes();
    $acceptable_formats = array_map(array($request, 'getFormat'), $acceptable_mime_types);
    $primary_format = $this->contentNegotiation->getContentType($request);

    // Collect a list of routes that match the primary request content type.
    $primary_matches = new RouteCollection();
    // List of routes that match any of multiple specified content types in the
    // request.
    $somehow_matches = new RouteCollection();

    foreach ($collection as $name => $route) {
      // _format could be a |-delimited list of supported formats.
      $supported_formats = array_filter(explode('|', $route->getRequirement('_format')));

      // HTML is the default format if the route does not specify it. We also
      // need to add those other weird Drupal AJAX formats here, otherwise we
      // would exclude the AJAX routes.
      // @todo Figure out why adding "_format: drupal_ajax" to AJAX routes does
      // not work.
      if (empty($supported_formats)) {
        $supported_formats = array('html', 'drupal_ajax', 'drupal_modal', 'drupal_dialog');
      }

      if (in_array($primary_format, $supported_formats)) {
        $primary_matches->add($name, $route);
      }
      // The route partially matches if it doesn't care about format, if it
      // explicitly allows any format, or if one of its allowed formats is
      // in the request's list of acceptable formats.
      elseif (in_array('*/*', $acceptable_mime_types) || array_intersect($acceptable_formats, $supported_formats)) {
        $somehow_matches->add($name, $route);
      }
    }

    if (count($primary_matches)) {
      return $primary_matches;
    }

    if (count($somehow_matches)) {
      return $somehow_matches;
    }

    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 406.
    throw new NotAcceptableHttpException(format_string('No route found for the specified formats @formats.', array('@formats' => implode(' ', $acceptable_mime_types))));
  }

  /**
   * Filters routes based on the HTTP Content-type header.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The collection against which to match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A non-empty RouteCollection of matched routes.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   If no routes could be matched against the Content-type header.
   */
  protected function filterContentTypeHeaders(RouteCollection $collection, Request $request) {
    $format = $request->getContentType();
    if ($format === NULL) {
      // Even if the request has no Content-type header we initialize it here
      // with a default so that we can filter out routes that require a
      // different one later.
      $format = 'html';
    }
    foreach ($collection as $name => $route) {
      $supported_formats = array_filter(explode('|', $route->getRequirement('_content_type_format')));
      if (empty($supported_formats)) {
        // The route has not specified any Content-Type restrictions, so we
        // assume default restrictions.
        $supported_formats = array('html', 'drupal_ajax', 'drupal_modal', 'drupal_dialog');
      }
      if (!in_array($format, $supported_formats)) {
        $collection->remove($name);
      }
    }
    if (count($collection)) {
      return $collection;
    }
    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 400.
    throw new BadRequestHttpException('No route found that matches the Content-Type header.');
  }

}
