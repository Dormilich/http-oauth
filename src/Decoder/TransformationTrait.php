<?php

namespace Dormilich\HttpOauth\Decoder;

use Dormilich\HttpClient\Decoder\DecoderInterface;
use Dormilich\HttpClient\Transformer\DataDecoderInterface;
use Psr\Http\Message\ResponseInterface;

trait TransformationTrait
{
    private DataDecoderInterface $transformer;

    protected function setTransformer(DataDecoderInterface $transformer)
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
