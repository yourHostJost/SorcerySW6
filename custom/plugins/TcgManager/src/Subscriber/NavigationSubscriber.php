<?php declare(strict_types=1);

namespace TcgManager\Subscriber;

use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NavigationSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onStorefrontRender'
        ];
    }

    public function onStorefrontRender(StorefrontRenderEvent $event): void
    {
        // Add TCG navigation items to template variables
        $tcgNavigation = [
            'catalog' => [
                'label' => '🃏 TCG Katalog',
                'url' => $this->urlGenerator->generate('tcg.shop.catalog'),
                'active' => $this->isCurrentRoute($event, 'tcg.shop.catalog')
            ],
            'categories' => [
                'label' => '📚 Kategorien',
                'url' => $this->urlGenerator->generate('tcg.shop.categories'),
                'active' => $this->isCurrentRoute($event, 'tcg.shop.categories')
            ],
            'collections' => [
                'label' => '📦 Meine Sammlung',
                'url' => $this->urlGenerator->generate('frontend.account.tcg.collections'),
                'active' => $this->isCurrentRoute($event, 'frontend.account.tcg.collections')
            ]
        ];

        $event->setParameter('tcgNavigation', $tcgNavigation);
    }

    private function isCurrentRoute(StorefrontRenderEvent $event, string $routeName): bool
    {
        $request = $event->getRequest();
        return $request->attributes->get('_route') === $routeName;
    }
}
