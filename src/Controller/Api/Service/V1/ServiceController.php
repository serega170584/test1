<?php

declare(strict_types=1);

namespace App\Controller\Api\Service\V1;

use App\Client\PROVIDER\ApiClient;
use App\Client\PROVIDER\v2\OrderDto;
use App\Dto\SaveMinimalRemainDto;
use App\Entity\MinimalRemain;
use App\Exception\ApiClientException;
use App\Manager\FeatureManager;
use App\Repository\MinimalRemainRepositoryInterface;
use App\Request\Service\V1\DeleteMinimalRemainRequest;
use App\Request\Service\V1\ImportPROVIDERGoodsRequest;
use App\Request\Service\V1\ProcessFailedMessagesRequest;
use App\Request\Service\V1\SaveMinimalRemainRequest;
use App\Response\Service\V1\DeleteMinimalRemainResponse;
use App\Response\Service\V1\ImportPROVIDERGoodsResponse;
use App\Response\Service\V1\MinimalRemainItem;
use App\Response\Service\V1\MinimalRemainsResponse;
use App\Response\Service\V1\ProcessFailedMessagesResponse;
use App\Response\Service\V1\SaveMinimalRemainResponse;
use App\Response\Service\V1\UpdatedOrderStatusResponse;
use App\Service\FailedMessagesService;
use App\Service\ImportPROVIDERGoodsService;
use App\Service\MinimalRemain\MinimalRemainManagerInterface;
use App\Service\OrderStatusService;
use DateTimeInterface;
use Exception;
use test1\Message\V2\ExportUpdatedOrder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ServiceController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/api/service/v1/process-failed-messages', methods: ['POST'])]
    public function processFailedMessages(
        ProcessFailedMessagesRequest $request,
        FailedMessagesService $failedMessagesService,
    ): JsonResponse {
        $startCountMessages = $failedMessagesService->getCountMessages();

        if (!$startCountMessages) {
            return $this->json(
                (new ProcessFailedMessagesResponse())
                    ->setMessage('Нет сообщений для обработки')
                    ->setCountProcessed(0)
                    ->setCountRemaining(0)
            );
        }

        if ($request->getLimit() && $request->getLimit() > $startCountMessages) {
            throw new UnprocessableEntityHttpException("Лимит не может быть больше общего количества сообщений. Всего сообщений в очереди - $startCountMessages");
        }

        $processLimit = $request->getLimit() ?? $startCountMessages;
        $failedMessagesService->processMessages($processLimit);

        $endCountMessages = $failedMessagesService->getCountMessages();

        return $this->json(
            (new ProcessFailedMessagesResponse())
                ->setMessage('Сообщения обработаны')
                ->setCountProcessed($processLimit)
                ->setCountRemaining($endCountMessages)
        );
    }

    #[Route('/api/service/v1/minimal-remains', methods: ['GET'])]
    public function getMinimalRemains(
        MinimalRemainRepositoryInterface $minimalRemainRepository
    ): JsonResponse {
        $items = [];

        $minimalRemains = $minimalRemainRepository->findAll();
        /** @var MinimalRemain $minimalRemain */
        foreach ($minimalRemains as $minimalRemain) {
            $items[] = (new MinimalRemainItem())
                ->setArticle($minimalRemain->getArticle())
                ->setMinimalRemainQuantity($minimalRemain->getMinimalRemainQuantity())
                ->setCreatedAt($minimalRemain->getCreatedAt()->format(DateTimeInterface::ATOM))
                ->setUpdatedAt($minimalRemain->getUpdatedAt()->format(DateTimeInterface::ATOM));
        }

        return $this->json(
            (new MinimalRemainsResponse())->setItems($items)
        );
    }

    #[Route('/api/service/v1/minimal-remains', methods: ['POST'])]
    public function saveMinimalRemain(
        SaveMinimalRemainRequest $request,
        MinimalRemainManagerInterface $minimalRemainManager
    ): JsonResponse {
        $dto = (new SaveMinimalRemainDto())
            ->setMinimalRemainQuantity($request->getMinimalRemainQuantity())
            ->setArticle($request->getArticle());

        $minimalRemain = $minimalRemainManager->saveMinimalRemain($dto);

        return $this->json(
            (new SaveMinimalRemainResponse())
                ->setMessage('Минимальный остаток успешно задан')
                ->setArticle($minimalRemain->getArticle())
                ->setMinimalRemainQuantity($minimalRemain->getMinimalRemainQuantity())
                ->setUpdatedAt($minimalRemain->getUpdatedAt()->format(DateTimeInterface::ATOM))
                ->setCreatedAt($minimalRemain->getCreatedAt()->format(DateTimeInterface::ATOM))
        );
    }

    #[Route('/api/service/v1/minimal-remains', methods: ['DELETE'])]
    public function deleteMinimalRemain(
        DeleteMinimalRemainRequest $request,
        MinimalRemainManagerInterface $minimalRemainManager
    ): JsonResponse {
        $minimalRemainManager->deleteByArticles($request->getArticles());

        return $this->json(
            (new DeleteMinimalRemainResponse())
                ->setMessage('Минимальный остаток успешно удален')
        );
    }

    #[Route('/api/service/v1/import-PROVIDER-goods', methods: ['PUT'])]
    public function importPROVIDERGoods(
        ImportPROVIDERGoodsRequest $request,
        ImportPROVIDERGoodsService $importPROVIDERGoodsService
    ): JsonResponse {
        $importPROVIDERGoodsService->dispatchPROVIDERGoodsRequests($request->getStoreIds());

        return $this->json(
            (new ImportPROVIDERGoodsResponse())
                ->setMessage('Задание на импорт товаров из УАС поставлено в очередь')
        );
    }

    #[Route('/api/service/v1/export-updated-order', methods: ['PUT'])]
    public function importUpdatedOrderStatus(
        Request $request,
        OrderStatusService $orderStatusService,
        ApiClient $PROVIDERClient,
        FeatureManager $featureManager,
        SerializerInterface $serializer
    ): JsonResponse {
        try {
            /** @var ExportUpdatedOrder $exportUpdatedOrder */
            $exportUpdatedOrder = $serializer->denormalize(
                $request->toArray(),
                ExportUpdatedOrder::class,
                'array',
                [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
            );

            $orderDto = (new OrderDto(
                $exportUpdatedOrder->getRequestId(),
                $exportUpdatedOrder->getNumber(),
                $orderStatusService->getPROVIDERStatusByImStatus($exportUpdatedOrder->getStatus()),
                $exportUpdatedOrder->getType()
            ));

            if ($featureManager->isEnabledDeliveryToCustomer()) {
                $orderDto->setAcceptCode($exportUpdatedOrder->getAcceptCode());
            }

            $PROVIDERClient->updateOrder($orderDto);

            return $this->json(
                (new UpdatedOrderStatusResponse())
                    ->setMessage('Заказ отправлен в УАС')
            );
        } catch (ApiClientException $exception) {
            return $this->json(
                (new UpdatedOrderStatusResponse())
                    ->setMessage($exception->getMessage()),
                $exception->getResponse()->getStatusCode()
            );
        } catch (Exception $exception) {
            return $this->json(
                (new UpdatedOrderStatusResponse())
                    ->setMessage($exception->getMessage()),
                500
            );
        }
    }
}
