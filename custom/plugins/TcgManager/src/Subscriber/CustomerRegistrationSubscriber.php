<?php declare(strict_types=1);

namespace TcgManager\Subscriber;

use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TcgManager\Service\CollectionService;

class CustomerRegistrationSubscriber implements EventSubscriberInterface
{
    private CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerRegisterEvent::class => 'onCustomerRegister',
        ];
    }

    public function onCustomerRegister(CustomerRegisterEvent $event): void
    {
        $customer = $event->getCustomer();
        $context = $event->getContext();

        // Create default collection for new customer
        try {
            $this->collectionService->createDefaultCollectionForCustomer(
                $customer->getId(),
                $context
            );
        } catch (\Exception $e) {
            // Log error but don't fail registration
            // TODO: Add proper logging
        }
    }
}
