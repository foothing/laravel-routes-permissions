<?php namespace Foothing\Wrappr\Installer;

use Foothing\Wrappr\Routes\Route;

class Parser {

	public function parseRouteString($path) {
		list($verb, $path) = explode(":", $path);
		if ( empty($verb) ) {
			throw new \Exception("Verb token not found");
		}
		if ( empty($path) ) {
			throw new \Exception("Path token not found");
		}
		return ['verb' => $verb, 'path' => $path];
	}

	/**
	 * Encodes a friendly url pattern to database implementation.
	 * It performs resource identifiers rewrite and wildcards handling,
	 * like the following:
	 *
	 *  -   'foo'                   will be encoded in 'foo'
	 *  -   'foo/bar'               will be encoded in 'foo/bar'
	 *  -   'foo/{id}'              will be encoded in 'foo/[0-9]+'
	 *  -   'foo/{id}/*'            will be encoded in 'foo/[0-9]+/'    ->  regex * will be appended
	 *  -   'foo/{id}/* /bar/baz'   will be encoded in 'foo/[0-9]+/'    ->  regex * will be appended
	 *  A route pattern can NOT start with a resource identifier.
	 *
	 * @param string $pattern
	 *  User friendly pattern.
	 *
	 * @return Route
	 *
	 * @throws \Exception
	 *  When route doesn't match a proper format.
	 */
	public function parsePattern($pattern) {
		// Explode it. We are using explode / foreach method instead
		// of a plain regex to match resources position.
		$chunks = explode("/", $pattern);

		// Initialize offset.
		$offset = 0;

		if (count($chunks) > 1) {
			$count = 0;
			foreach ($chunks as $key => $token) {

				// If token contains parameters in the form of {param},
				// we save the corresponding regex pattern. We use $offset
				// variable to check that rewritings take place one time only.
				if (preg_match("/\{(.*?)\}/", $token) && $offset == 0) {
					if ($count == 0) {
						throw new \Exception('Resource position in pattern not allowed: ' . $pattern);
					}
					$chunks[$key] = "[0-9]+";
					$offset = $count;
				}

				// Token is a wildcard: ignore following chunks.
				else if($token === '*') {
					$chunks[$key] = "\\*";
					$chunks = array_slice($chunks, 0, $count+1);

					// Break, since wildcard must be the very last token.
					break;
				}

				$count++;
			}
			$pattern = implode("/", $chunks);
		}

		return new Route(['pattern' => $pattern, 'resourceOffset' => $offset]);
	}

	/**
	 * Extract the resource identifier from the given path.
	 *
	 * @param Route $route
	 * @param       $path
	 *
	 * @return mixed resource identifier
	 */
	public function getResourceFromPath(Route $route, $path) {
		// Explode it.
		$chunks = explode("/", $path);
		return $route->resourceOffset ? $chunks[$route->resourceOffset ] : null;
	}
}