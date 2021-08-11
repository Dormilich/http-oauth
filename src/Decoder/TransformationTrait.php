<?php

namespace Dormilich\HttpOauth\Decoder;

use Dormilich\HttpClient\Decoder\DecoderInterface;
use Dormilich\HttpClient\Transformer\TransformerInterface;
use Psr\Http\Message\ResponseInterface;

trait TransformationTrait
{
    private TransformerInterface $transformer;

    protected function setTransformer(TransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * @see DecoderInterface::getContentType()
     */
    public function getContentType(): string
    {
        return $this->transformer->contentType();
    }

    /**
     * Parse the response body.
     *
     * @param ResponseInterface $response
     * @return mixed
     */
    protected function getData(ResponseInterface $response)
    {
        $content = (string) $response->getBody();
        return $this->transformer->decode($content);
    }
}
