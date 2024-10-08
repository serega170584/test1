<?php

namespace App\Response;

use App\Response\Result\AbstractResult;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Ответ API.
 */
class ApiResponse extends JsonResponse
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        parent::__construct();
    }

    /**
     * Возвращает json-ответ.
     *
     * @return $this
     */
    public function setResult(AbstractResult $result): self
    {
        $this->setJson($this->serializer->serialize($result, 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]));

        return $this;
    }
}
