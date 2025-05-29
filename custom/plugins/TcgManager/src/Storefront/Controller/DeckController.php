<?php declare(strict_types=1);

namespace TcgManager\Storefront\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Shopware\Core\Checkout\Cart\CartException;
use TcgManager\Service\DeckService;
use TcgManager\Service\CollectionService;
use TcgManager\Service\ShopIntegrationService;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class DeckController extends StorefrontController
{
    private DeckService $deckService;
    private CollectionService $collectionService;
    private ShopIntegrationService $shopIntegrationService;

    public function __construct(
        DeckService $deckService,
        CollectionService $collectionService,
        ShopIntegrationService $shopIntegrationService
    ) {
        $this->deckService = $deckService;
        $this->collectionService = $collectionService;
        $this->shopIntegrationService = $shopIntegrationService;
    }

    private function denyAccessUnlessLoggedIn(SalesChannelContext $context): void
    {
        if (!$context->getCustomer()) {
            throw CartException::customerNotLoggedIn();
        }
    }

    #[Route(path: '/account/tcg/decks', name: 'frontend.account.tcg.decks', methods: ['GET'])]
    public function decksPage(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn($context);

        $customerId = $context->getCustomer()->getId();
        $decks = $this->deckService->getCustomerDecks($customerId, $context->getContext());

        // Debug: Log deck count
        error_log("DEBUG: Customer ID: " . $customerId);
        error_log("DEBUG: Decks count: " . $decks->count());

        // Convert decks to array for Twig
        $decksArray = [];
        foreach ($decks as $deck) {
            error_log("DEBUG: Deck: " . $deck->getName() . " (ID: " . $deck->getId() . ")");
            $decksArray[] = [
                'id' => $deck->getId(),
                'name' => $deck->getName(),
                'description' => $deck->getDescription(),
                'format' => $deck->getFormat(),
                'archetype' => $deck->getArchetype(),
                'colors' => $deck->getColors(),
                'isPublic' => $deck->getIsPublic(),
                'isComplete' => $deck->getIsComplete(),
                'totalCards' => $deck->getTotalCards(),
                'mainDeckSize' => $deck->getMainDeckSize(),
                'sideboardSize' => $deck->getSideboardSize(),
                'createdAt' => $deck->getCreatedAt(),
                'deckCards' => $deck->getDeckCards(),
            ];
        }

        return $this->renderStorefront('@TcgManager/storefront/page/account/decks.html.twig', [
            'decks' => $decksArray,
            'page' => [
                'title' => 'Meine Decks'
            ]
        ]);
    }

    #[Route(path: '/account/tcg/deck/{deckId}', name: 'frontend.account.tcg.deck.detail', methods: ['GET'])]
    public function deckDetail(string $deckId, Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn($context);

        // TODO: Add security check to ensure customer owns this deck

        return $this->renderStorefront('@TcgManager/storefront/page/account/deck-detail.html.twig', [
            'deckId' => $deckId,
            'page' => [
                'title' => 'Deck Details'
            ]
        ]);
    }

    #[Route(path: '/account/tcg/decks/{deckId}/api', name: 'frontend.account.tcg.decks.api', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    public function getDeckDetail(string $deckId, Request $request, SalesChannelContext $context): JsonResponse
    {
        try {
            // Check if user is logged in
            if (!$context->getCustomer()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Nicht angemeldet'
                ], 401);
            }

            $deck = $this->deckService->getDeckById($deckId, $context->getContext());

            if (!$deck) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Deck nicht gefunden'
                ], 404);
            }

            // Security check: ensure customer owns this deck
            $customerId = $context->getCustomer()->getId();
            if ($deck->getCustomerId() !== $customerId) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Zugriff verweigert'
                ], 403);
            }

            $deckData = [
                'id' => $deck->getId(),
                'name' => $deck->getName(),
                'description' => $deck->getDescription(),
                'format' => $deck->getFormat(),
                'archetype' => $deck->getArchetype(),
                'colors' => $deck->getColors(),
                'isPublic' => $deck->getIsPublic(),
                'isComplete' => $deck->getIsComplete(),
                'totalCards' => $deck->getTotalCards(),
                'mainDeckSize' => $deck->getMainDeckSize(),
                'sideboardSize' => $deck->getSideboardSize(),
                'createdAt' => $deck->getCreatedAt()->format('Y-m-d H:i:s'),
            ];

            return new JsonResponse([
                'success' => true,
                'data' => $deckData
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Laden des Decks: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route(path: '/account/tcg/deck-feed', name: 'frontend.account.tcg.deck.feed', methods: ['GET'])]
    public function deckFeed(Request $request, SalesChannelContext $context): Response
    {
        $publicDecks = $this->deckService->getPublicDecks(20, $context->getContext());

        return $this->renderStorefront('@TcgManager/storefront/page/account/deck-feed.html.twig', [
            'publicDecks' => $publicDecks,
            'page' => [
                'title' => 'Deck-Feed'
            ]
        ]);
    }

    #[Route(path: '/api/tcg/decks', name: 'api.tcg.decks.list', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    public function getDecks(Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn($context);

        $customerId = $context->getCustomer()->getId();
        $decks = $this->deckService->getCustomerDecks($customerId, $context->getContext());

        $decksData = [];
        foreach ($decks as $deck) {
            $decksData[] = [
                'id' => $deck->getId(),
                'name' => $deck->getName(),
                'description' => $deck->getDescription(),
                'format' => $deck->getFormat(),
                'archetype' => $deck->getArchetype(),
                'colors' => $deck->getColors(),
                'isPublic' => $deck->getIsPublic(),
                'isComplete' => $deck->getIsComplete(),
                'totalCards' => $deck->getTotalCards(),
                'mainDeckSize' => $deck->getMainDeckSize(),
                'sideboardSize' => $deck->getSideboardSize(),
                'createdAt' => $deck->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'success' => true,
            'data' => $decksData
        ]);
    }

    #[Route(path: '/account/tcg/decks/create', name: 'frontend.account.tcg.decks.create', methods: ['POST'], defaults: ['_routeScope' => ['storefront'], 'XmlHttpRequest' => true])]
    public function createDeck(Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn($context);

        // Handle both JSON and form data
        $contentType = $request->headers->get('Content-Type', '');
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($request->getContent(), true);
        } else {
            // Handle form data
            $data = [
                'name' => $request->request->get('name'),
                'description' => $request->request->get('description'),
                'format' => $request->request->get('format'),
                'archetype' => $request->request->get('archetype'),
                'colors' => $request->request->get('colors'),
                'isPublic' => $request->request->get('isPublic') === 'on',
            ];
        }
        $customerId = $context->getCustomer()->getId();

        $name = $data['name'] ?? '';
        $description = $data['description'] ?? null;
        $format = $data['format'] ?? null;
        $archetype = $data['archetype'] ?? null;
        $colors = $data['colors'] ?? null;
        $isPublic = $data['isPublic'] ?? false;

        if (empty($name)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Name ist erforderlich'
            ], 400);
        }

        try {
            error_log("DEBUG: Creating deck for customer: " . $customerId);
            error_log("DEBUG: Deck name: " . $name);

            $deckId = $this->deckService->createDeck(
                $customerId,
                $name,
                $description,
                $format,
                $archetype,
                $colors,
                $isPublic,
                $context->getContext()
            );

            error_log("DEBUG: Deck created with ID: " . $deckId);

            return new JsonResponse([
                'success' => true,
                'data' => ['id' => $deckId],
                'message' => 'Deck erfolgreich erstellt'
            ]);
        } catch (\Exception $e) {
            error_log("DEBUG: Error creating deck: " . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Erstellen des Decks: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route(path: '/api/tcg/decks/{deckId}/compare', name: 'api.tcg.decks.compare', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    public function compareDeckWithCollection(string $deckId, Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn($context);

        $customerId = $context->getCustomer()->getId();

        try {
            $comparison = $this->deckService->compareDeckWithCollection(
                $deckId,
                $customerId,
                $context->getContext()
            );

            // Calculate pricing for missing cards
            $pricing = $this->shopIntegrationService->calculateMissingCardsPrice(
                $comparison['missingCards'],
                $context->getContext()
            );

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'comparison' => $comparison,
                    'pricing' => $pricing,
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Vergleichen des Decks: ' . $e->getMessage()
            ], 500);
        }
    }
}
