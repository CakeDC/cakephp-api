<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Renderer;

use CakeDC\Api\Service\Action\Result;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;
use Exception;
use League\Flysystem\File;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Zend\Diactoros\Stream;

/**
 * Class FlysystemRenderer to render file using Flysystem library
 */
class FlysystemRenderer extends FileRenderer
{
    use LogTrait;
    /**
     * Builds the HTTP response.
     *
     * @param Result $result The result object returned by the Service.
     * @return bool
     */
    public function response(Result $result = null)
    {
        $data = $result->getData();
        try {
            $file = $this->getFile(
                Hash::get($data, 'filesystem'),
                Hash::get($data, 'path')
            );
            $name = Hash::get($data, 'name');

            $this->_service->setResponse(
                $this->deliverAsset($this->_service->getResponse(), $file, $name)
            );
        } catch (FileNotFoundException $e) {
            $response = $this->_service->getResponse()
                ->withStatus(404);

            $this->_service->setResponse($response);
        }

        return true;
    }

    /**
     * Get flystem file object
     *
     * @param Filesystem $filesystem custom filesystem
     * @param string $path of file at filesystem
     * @return File
     */
    protected function getFile(Filesystem $filesystem, $path)
    {
        return $filesystem->get($path);
    }

    /**
     * Deliver the asset stream in body
     *
     * @param Response $response service response
     * @param File $file file object
     * @param string $name file name shown to user
     * @return Response
     */
    public function deliverAsset(Response $response, File $file, $name)
    {
        $contentType = $file->getType();
        $modified = $file->getTimestamp();
        $expire = strtotime(Configure::read('Api.Flysystem.expire'));
        $maxAge = $expire - time();
        $stream = new Stream($file->readStream());

        return $response->withBody($stream)
            // Content
            ->withHeader('Content-Type', $contentType)
            //Name
            ->withDownload($name)
            // Cache
            ->withHeader('Cache-Control', 'public,max-age=' . $maxAge)
            ->withHeader('Date', gmdate('D, j M Y G:i:s \G\M\T', time()))
            ->withHeader('Last-Modified', gmdate('D, j M Y G:i:s \G\M\T', $modified))
            ->withHeader('Expires', gmdate('D, j M Y G:i:s \G\M\T', $expire));
    }

    /**
     * Handle error setting a response status
     *
     * @param Exception $exception thrown at service or action
     *
     * @return void
     */
    public function error(Exception $exception)
    {
        $code = $exception->getCode();
        $response = $this->_service->getResponse()
            ->withStatus($code ? $code : 500);

        $this->log($exception);

        $this->_service->setResponse($response);
    }
}
