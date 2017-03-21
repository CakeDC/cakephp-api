<?php

namespace CakeDC\Api\Middleware;

use Cake\Utility\Exception\XmlException;
use Cake\Utility\Xml;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Applies routing rules to the request and creates the controller
 * instance if possible.
 */
class RequestHandlerMiddleware
{

    /**
     * Request object
     *
     * @var \Cake\Http\ServerRequest
     */
    public $request;

    /**
     * Response object
     *
     * @var \Cake\Http\Response
     */
    public $response;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next The next middleware to call.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $inputTypeMap = [
            'json' => ['json_decode', true],
            'xml' => [[$this, 'convertXml']],
        ];

        $this->request = $request;
        $this->response = $response;
        $parsedBody = $request->getParsedBody();

        foreach ($inputTypeMap as $type => $handler) {
            if (!is_callable($handler[0])) {
                throw new RuntimeException(sprintf("Invalid callable for '%s' type.", $type));
            }
            if (empty($parsedBody) && $this->requestedWith($type)) {
                $input = call_user_func_array([$this->request, 'input'], $handler);

                return $next($request->withParsedBody($input), $response);
            }
        }

        return $next($request, $response);
    }

    /**
     * Determines the content type of the data the client has sent (i.e. in a POST request)
     *
     * @param string|array|null $type Can be null (or no parameter), a string type name, or an array of types
     * @return mixed If a single type is supplied a boolean will be returned. If no type is provided
     *   The mapped value of CONTENT_TYPE will be returned. If an array is supplied the first type
     *   in the request content type will be returned.
     */
    public function requestedWith($type = null)
    {
        $request = $this->request;
        if (!$request->is('post') &&
            !$request->is('put') &&
            !$request->is('patch') &&
            !$request->is('delete')
        ) {
            return null;
        }
        if (is_array($type)) {
            foreach ($type as $t) {
                if ($this->requestedWith($t)) {
                    return $t;
                }
            }

            return false;
        }

        list($contentType) = explode(';', $request->contentType());
        $response = $this->response;
        if ($type === null) {
            return $response->mapType($contentType);
        }
        if (is_string($type)) {
            return ($type === $response->mapType($contentType));
        }
    }

    /**
     * Helper method to parse xml input data, due to lack of anonymous functions
     * this lives here.
     *
     * @param string $xml XML string.
     * @return array Xml array data
     */
    public function convertXml($xml)
    {
        try {
            $xml = Xml::build($xml, ['readFile' => false]);
            if (isset($xml->data)) {
                return Xml::toArray($xml->data);
            }

            return Xml::toArray($xml);
        } catch (XmlException $e) {
            return [];
        }
    }
}
