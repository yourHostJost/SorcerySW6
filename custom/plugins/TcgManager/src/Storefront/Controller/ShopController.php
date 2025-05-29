<?php declare(strict_types=1);

namespace TcgManager\Storefront\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use TcgManager\Service\CardService;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class ShopController extends StorefrontController
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;
    private CardService $cardService;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository,
        CardService $cardService
    ) {
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
        $this->cardService = $cardService;
    }

    #[Route(path: '/tcg/shop', name: 'tcg.shop.catalog', methods: ['GET'])]
    public function catalog(Request $request, SalesChannelContext $context): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 24; // Cards per page
        $offset = ($page - 1) * $limit;

        // Get filter parameters
        $searchTerm = $request->query->get('q');
        $edition = $request->query->get('edition');
        $rarity = $request->query->get('rarity');
        $cardType = $request->query->get('type');
        $elements = $request->query->get('elements');
        $sortBy = $request->query->get('sort', 'name');

        // Build criteria for cards with products
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);

        // Only show cards that have been synced to products
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));

        // Apply filters
        if ($searchTerm) {
            $criteria->addFilter(new ContainsFilter('title', $searchTerm));
        }

        if ($edition) {
            $criteria->addFilter(new EqualsFilter('edition', $edition));
        }

        if ($rarity) {
            $criteria->addFilter(new EqualsFilter('rarity', $rarity));
        }

        if ($cardType) {
            $criteria->addFilter(new EqualsFilter('cardType', $cardType));
        }

        if ($elements) {
            $criteria->addFilter(new ContainsFilter('elements', $elements));
        }

        // Apply sorting
        switch ($sortBy) {
            case 'price_asc':
                $criteria->addSorting(new FieldSorting('marketPrice', FieldSorting::ASCENDING));
                break;
            case 'price_desc':
                $criteria->addSorting(new FieldSorting('marketPrice', FieldSorting::DESCENDING));
                break;
            case 'rarity':
                $criteria->addSorting(new FieldSorting('rarity', FieldSorting::ASCENDING));
                break;
            case 'edition':
                $criteria->addSorting(new FieldSorting('edition', FieldSorting::ASCENDING));
                break;
            default:
                $criteria->addSorting(new FieldSorting('title', FieldSorting::ASCENDING));
        }

        // Add aggregations for filters
        $criteria->addAggregation(new TermsAggregation('editions', 'edition'));
        $criteria->addAggregation(new TermsAggregation('rarities', 'rarity'));
        $criteria->addAggregation(new TermsAggregation('cardTypes', 'cardType'));
        $criteria->addAggregation(new TermsAggregation('elements', 'elements'));

        $result = $this->cardRepository->search($criteria, $context->getContext());
        $cards = $result->getEntities();
        $aggregations = $result->getAggregations();

        // Load product media for each card
        $cardsWithMedia = [];
        foreach ($cards as $card) {
            $cardData = [
                'card' => $card,
                'productMedia' => []
            ];

            if ($card->getShopwareProductId()) {
                $productCriteria = new Criteria([$card->getShopwareProductId()]);
                $productCriteria->addAssociation('media.media');
                $productResult = $this->productRepository->search($productCriteria, $context->getContext());
                $product = $productResult->first();

                if ($product && $product->getMedia()) {
                    foreach ($product->getMedia() as $media) {
                        $mediaEntity = $media->getMedia();
                        if ($mediaEntity) {
                            $finishCode = $media->getCustomFields()['tcg_finish_code'] ?? 'unknown';
                            $finishName = $media->getCustomFields()['tcg_finish_name'] ?? 'Unknown';

                            $cardData['productMedia'][$finishCode] = [
                                'url' => $mediaEntity->getUrl(),
                                'finish' => $finishName,
                                'alt' => $mediaEntity->getAlt() ?? $card->getTitle(),
                                'title' => $mediaEntity->getTitle() ?? $card->getTitle()
                            ];
                        }
                    }
                }
            }

            $cardsWithMedia[] = $cardData;
        }

        // Calculate pagination
        $totalCards = $result->getTotal();
        $totalPages = ceil($totalCards / $limit);

        return $this->renderStorefront('@TcgManager/storefront/page/shop/catalog.html.twig', [
            'cardsWithMedia' => $cardsWithMedia,
            'aggregations' => $aggregations,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalCards' => $totalCards,
                'limit' => $limit
            ],
            'filters' => [
                'searchTerm' => $searchTerm,
                'edition' => $edition,
                'rarity' => $rarity,
                'cardType' => $cardType,
                'elements' => $elements,
                'sortBy' => $sortBy
            ],
            'page' => [
                'title' => 'TCG Kartenkatalog'
            ]
        ]);
    }

    #[Route(path: '/tcg/shop/card/{cardId}', name: 'tcg.shop.card.detail', methods: ['GET'])]
    public function cardDetail(string $cardId, Request $request, SalesChannelContext $context): Response
    {
        // Get card details
        $criteria = new Criteria([$cardId]);
        $result = $this->cardRepository->search($criteria, $context->getContext());
        $card = $result->first();

        if (!$card) {
            throw $this->createNotFoundException('Card not found');
        }

        // Get related product if exists
        $product = null;
        $productMedia = [];
        if ($card->getShopwareProductId()) {
            $productCriteria = new Criteria([$card->getShopwareProductId()]);
            $productCriteria->addAssociation('media.media');
            $productResult = $this->productRepository->search($productCriteria, $context->getContext());
            $product = $productResult->first();

            // Extract media URLs for the card
            if ($product && $product->getMedia()) {
                foreach ($product->getMedia() as $media) {
                    $mediaEntity = $media->getMedia();
                    if ($mediaEntity) {
                        $finishCode = $media->getCustomFields()['tcg_finish_code'] ?? 'unknown';
                        $finishName = $media->getCustomFields()['tcg_finish_name'] ?? 'Unknown';

                        $productMedia[$finishCode] = [
                            'url' => $mediaEntity->getUrl(),
                            'finish' => $finishName,
                            'alt' => $mediaEntity->getAlt() ?? $card->getTitle(),
                            'title' => $mediaEntity->getTitle() ?? $card->getTitle()
                        ];
                    }
                }
            }
        }

        // Get similar cards (same edition or rarity)
        $similarCriteria = new Criteria();
        $similarCriteria->setLimit(6);
        $similarCriteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('id', $cardId)]
        ));
        $similarCriteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));

        // Prefer same edition, then same rarity
        if ($card->getEdition()) {
            $similarCriteria->addFilter(new EqualsFilter('edition', $card->getEdition()));
        } elseif ($card->getRarity()) {
            $similarCriteria->addFilter(new EqualsFilter('rarity', $card->getRarity()));
        }

        $similarResult = $this->cardRepository->search($similarCriteria, $context->getContext());
        $similarCards = $similarResult->getEntities();

        // Load media for similar cards
        $similarCardsWithMedia = [];
        foreach ($similarCards as $similarCard) {
            $similarCardData = [
                'card' => $similarCard,
                'productMedia' => []
            ];

            if ($similarCard->getShopwareProductId()) {
                $productCriteria = new Criteria([$similarCard->getShopwareProductId()]);
                $productCriteria->addAssociation('media.media');
                $productResult = $this->productRepository->search($productCriteria, $context->getContext());
                $product = $productResult->first();

                if ($product && $product->getMedia()) {
                    foreach ($product->getMedia() as $media) {
                        $mediaEntity = $media->getMedia();
                        if ($mediaEntity) {
                            $finishCode = $media->getCustomFields()['tcg_finish_code'] ?? 'unknown';
                            $finishName = $media->getCustomFields()['tcg_finish_name'] ?? 'Unknown';

                            $similarCardData['productMedia'][$finishCode] = [
                                'url' => $mediaEntity->getUrl(),
                                'finish' => $finishName,
                                'alt' => $mediaEntity->getAlt() ?? $similarCard->getTitle(),
                                'title' => $mediaEntity->getTitle() ?? $similarCard->getTitle()
                            ];
                        }
                    }
                }
            }

            $similarCardsWithMedia[] = $similarCardData;
        }

        return $this->renderStorefront('@TcgManager/storefront/page/shop/card-detail.html.twig', [
            'card' => $card,
            'product' => $product,
            'productMedia' => $productMedia,
            'similarCardsWithMedia' => $similarCardsWithMedia,
            'page' => [
                'title' => $card->getTitle()
            ]
        ]);
    }

    #[Route(path: '/tcg/shop/api/search', name: 'tcg.shop.api.search', methods: ['GET'], defaults: ['XmlHttpRequest' => true])]
    public function searchApi(Request $request, SalesChannelContext $context): JsonResponse
    {
        $searchTerm = $request->query->get('q');
        $limit = $request->query->getInt('limit', 10);

        if (!$searchTerm || strlen($searchTerm) < 2) {
            return new JsonResponse(['cards' => []]);
        }

        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->addFilter(new ContainsFilter('title', $searchTerm));
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));
        $criteria->addSorting(new FieldSorting('title', FieldSorting::ASCENDING));

        $result = $this->cardRepository->search($criteria, $context->getContext());
        $cards = [];

        foreach ($result->getEntities() as $card) {
            $cards[] = [
                'id' => $card->getId(),
                'title' => $card->getTitle(),
                'edition' => $card->getEdition(),
                'rarity' => $card->getRarity(),
                'marketPrice' => $card->getMarketPrice(),
                'url' => $this->generateUrl('tcg.shop.card.detail', ['cardId' => $card->getId()])
            ];
        }

        return new JsonResponse(['cards' => $cards]);
    }

    #[Route(path: '/tcg/shop/categories', name: 'tcg.shop.categories', methods: ['GET'])]
    public function categories(Request $request, SalesChannelContext $context): Response
    {
        // Get all available editions and rarities for category navigation
        $criteria = new Criteria();
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));
        $criteria->addAggregation(new TermsAggregation('editions', 'edition'));
        $criteria->addAggregation(new TermsAggregation('rarities', 'rarity'));
        $criteria->addAggregation(new TermsAggregation('cardTypes', 'cardType'));

        $result = $this->cardRepository->search($criteria, $context->getContext());
        $aggregations = $result->getAggregations();

        return $this->renderStorefront('@TcgManager/storefront/page/shop/categories.html.twig', [
            'aggregations' => $aggregations,
            'totalCards' => $result->getTotal(),
            'page' => [
                'title' => 'TCG Kategorien'
            ]
        ]);
    }

    #[Route(path: '/tcg/shop/add-to-cart/{cardId}', name: 'tcg.shop.add.to.cart', methods: ['POST'], defaults: ['XmlHttpRequest' => true])]
    public function addToCart(string $cardId, Request $request, SalesChannelContext $context): JsonResponse
    {
        // Get card and its product
        $criteria = new Criteria([$cardId]);
        $result = $this->cardRepository->search($criteria, $context->getContext());
        $card = $result->first();

        if (!$card || !$card->getShopwareProductId()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Karte nicht verfügbar'
            ], 404);
        }

        $quantity = $request->request->getInt('quantity', 1);

        // Here you would integrate with Shopware's cart system
        // For now, return success response
        return new JsonResponse([
            'success' => true,
            'message' => 'Karte wurde zum Warenkorb hinzugefügt',
            'data' => [
                'cardId' => $cardId,
                'cardTitle' => $card->getTitle(),
                'quantity' => $quantity
            ]
        ]);
    }
}
