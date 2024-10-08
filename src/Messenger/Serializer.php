<?php

namespace App\Messenger;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

/**
 * Custom messenger serializer.
 */
class Serializer implements SerializerInterface
{
    // Envelope keys
    public const ENVELOPE_KEYS = ['body'];
    private const STAMP_HEADER_PREFIX = 'X-Message-Stamp-';

    /**
     * @var SymfonySerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $format;

    /**
     * @var array
     */
    private $context;

    /**
     * Deserializes data into the given type.
     *
     * @var string
     */
    private $type;

    public function __construct(
        string $type = null,
        string $format = 'json',
        array $context = []
    ) {
        $this->format = $format;
        $this->context = $context;
        $this->type = $type;
    }

    /**
     * Create default symfony serializer.
     */
    protected function getSerializer(): SymfonySerializerInterface
    {
        if ($this->serializer) {
            return $this->serializer;
        }

        if (!class_exists(SymfonySerializer::class)) {
            throw new LogicException(sprintf('The "%s" class requires Symfony\'s Serializer component. Try running "composer require symfony/serializer" or use "%s" instead.', __CLASS__, PhpSerializer::class));
        }

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);

        $objectNormalizer = new ObjectNormalizer($classMetadataFactory, null, null, $extractor);

        $normalizers = [
            new DateTimeNormalizer(),
            $objectNormalizer,
            new ArrayDenormalizer(),
            new GetSetMethodNormalizer(),
        ];

        $this->serializer = new SymfonySerializer($normalizers, [new JsonEncoder(), new XmlEncoder()]);

        return $this->serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        foreach (self::ENVELOPE_KEYS as $key) {
            if (empty($encodedEnvelope[$key])) {
                throw new MessageDecodingFailedException(sprintf('Encoded envelope should have at least a "%s", or maybe you should implement your own serializer.', $key));
            }
        }

        $stamps = [];
        if ($encodedEnvelope['headers'] ?? null) {
            $stamps = $this->decodeStamps($encodedEnvelope);
        }

        $serializerStamp = $this->findFirstSerializerStamp($stamps);

        $context = $this->context;
        if (null !== $serializerStamp) {
            $context = $serializerStamp->getContext() + $context;
        }

        try {
            $message = $this->getSerializer()->deserialize(
                $encodedEnvelope['body'],
                $this->type ?? 'array',
                $this->format,
                $context
            );
        } catch (ExceptionInterface $e) {
            throw new MessageDecodingFailedException('Could not decode message: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return new Envelope($message, $stamps);
    }

    /**
     * {@inheritdoc}
     */
    public function encode(Envelope $envelope): array
    {
        $context = $this->context;
        /** @var SerializerStamp|null $serializerStamp */
        if ($serializerStamp = $envelope->last(SerializerStamp::class)) {
            $context = $serializerStamp->getContext() + $context;
        }

        $envelope = $envelope
            ->withoutStampsOfType(NonSendableStampInterface::class)
            // Убираем штамп с ошибкой, чтобы не было лишнего мусора в заголовках очередей
            ->withoutStampsOfType(ErrorDetailsStamp::class);

        $headers = $this->encodeStamps($envelope) + $this->getContentTypeHeader();

        return [
            'body' => $this->getSerializer()->serialize($envelope->getMessage(), $this->format, $context),
            'headers' => $headers,
        ];
    }

    private function decodeStamps(array $encodedEnvelope): array
    {
        $stamps = [];
        foreach ($encodedEnvelope['headers'] as $name => $value) {
            if (0 !== strpos($name, self::STAMP_HEADER_PREFIX)) {
                continue;
            }

            try {
                $stamps[] = $this->getSerializer()->deserialize(
                    $value,
                    substr($name, \strlen(self::STAMP_HEADER_PREFIX)) . '[]',
                    $this->format,
                    $this->context
                );
            } catch (ExceptionInterface $e) {
                throw new MessageDecodingFailedException('Could not decode stamp: ' . $e->getMessage(), $e->getCode(), $e);
            }
        }
        if ($stamps) {
            $stamps = array_merge(...$stamps);
        }

        return $stamps;
    }

    private function encodeStamps(Envelope $envelope): array
    {
        if (!$allStamps = $envelope->all()) {
            return [];
        }

        $headers = [];
        foreach ($allStamps as $class => $stamps) {
            $headers[self::STAMP_HEADER_PREFIX . $class] = $this->getSerializer()->serialize(
                $stamps,
                $this->format,
                $this->context
            );
        }

        return $headers;
    }

    /**
     * @param StampInterface[] $stamps
     */
    private function findFirstSerializerStamp(array $stamps): ?SerializerStamp
    {
        foreach ($stamps as $stamp) {
            if ($stamp instanceof SerializerStamp) {
                return $stamp;
            }
        }

        return null;
    }

    private function getContentTypeHeader(): array
    {
        $mimeType = $this->getMimeTypeForFormat();

        return null === $mimeType ? [] : ['Content-Type' => $mimeType];
    }

    private function getMimeTypeForFormat(): ?string
    {
        switch ($this->format) {
            case 'json':
                return 'application/json';
            case 'xml':
                return 'application/xml';
            case 'yml':
            case 'yaml':
                return 'application/x-yaml';
            case 'csv':
                return 'text/csv';
        }

        return null;
    }
}
