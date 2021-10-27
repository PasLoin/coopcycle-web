<?php

namespace AppBundle\Api\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use AppBundle\Api\Resource\UrbantzWebhook;
use AppBundle\Entity\Urbantz\Delivery as UrbantzDelivery;
use AppBundle\Security\TokenStoreExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Hashids\Hashids;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UrbantzSubscriber implements EventSubscriberInterface
{
    private $urbantzClient;
    private $entityManager;
    private $logger;
    private $secret;

    public function __construct(
        HttpClientInterface $urbantzClient,
        EntityManagerInterface $entityManager,
        TokenStoreExtractor $storeExtractor,
        LoggerInterface $logger,
        string $secret)
    {
        $this->urbantzClient = $urbantzClient;
        $this->entityManager = $entityManager;
        $this->storeExtractor = $storeExtractor;
        $this->logger = $logger;
        $this->secret = $secret;
    }

    public static function getSubscribedEvents()
    {
        // @see https://api-platform.com/docs/core/events/#built-in-event-listeners
        return [
            KernelEvents::VIEW => [
                ['addToStore', EventPriorities::PRE_WRITE],
                ['setTrackingId', EventPriorities::POST_WRITE],
            ],
        ];
    }

    public function addToStore(ViewEvent $event)
    {
        $request = $event->getRequest();

        if ('api_urbantz_webhooks_receive_webhook_item' !== $request->attributes->get('_route')) {
            return;
        }

        $store = $this->storeExtractor->extractStore();

        if (null === $store) {
            return;
        }

        $webhook = $event->getControllerResult();

        if ($webhook->id !== UrbantzWebhook::TASKS_ANNOUNCED) {
            return;
        }

        foreach ($webhook->deliveries as $delivery) {
            $store->addDelivery($delivery);
        }
    }

    public function setTrackingId(ViewEvent $event)
    {
        $request = $event->getRequest();

        if ('api_urbantz_webhooks_receive_webhook_item' !== $request->attributes->get('_route')) {
            return;
        }

        $hashids = new Hashids($this->secret, 32);

        $webhook = $event->getControllerResult();

        if ($webhook->id !== UrbantzWebhook::TASKS_ANNOUNCED) {
            return;
        }

        foreach ($webhook->deliveries as $delivery) {

            $taskId = $delivery->getDropoff()->getRef();
            $hashid = $hashids->encode($delivery->getId());

            $extTrackId = "dlv_{$hashid}";

            // https://docs.urbantz.com/#tag/External-Carrier
            // https://api.urbantz.com/v2/carrier/external/task/id/XXXXXXX
            try {

                $response = $this->urbantzClient->request('POST', "carrier/external/task/id/{$taskId}", [
                    'json' => ['extTrackId' => $extTrackId],
                ]);

                // Need to invoke a method on the Response,
                // to actually throw the Exception here
                // https://github.com/symfony/symfony/issues/34281
                $statusCode = $response->getStatusCode();

                $urbantzDelivery = new UrbantzDelivery();
                $urbantzDelivery->setDelivery($delivery);

                $this->logger->info(
                    sprintf('Urbantz task "%s" is now linked to delivery with hashid "%s"', $taskId, $extTrackId)
                );

                $this->entityManager->persist($urbantzDelivery);
                $this->entityManager->flush();

            } catch (HttpExceptionInterface | TransportExceptionInterface $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
