<?php

namespace app\modules\v1\controllers;

/**
 * Yii required components
 */
use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\helpers\Constants;
use app\core\CoreController;

/**
 * Model required components
 */
use app\models\search\ProductSearch;
use app\models\search\CategorySearch;
use app\models\search\BannerSearch;
use app\models\search\ProductRatingSearch;
use app\models\FlashSale;

class DashboardController extends CoreController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        #add your action here
        $behaviors['verbs']['actions'] = ArrayHelper::merge(
            $behaviors['verbs']['actions'],
            [
                'index' => ['get'],
            ]
        );

        $behaviors['authenticator']['except'] = ArrayHelper::merge(
            $behaviors['authenticator']['except'],
            [
                'data',
            ]
        );

        return $behaviors;
    }

    public function actionData()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search($params);

        CoreController::validateProvider($dataProvider, $searchModel);
        
        $getVariantPricing = function ($variant): array {
            $price = null;
            $originalPrice = null;

            if (is_array($variant)) {
                $price = $variant['price'] ?? null;
                $originalPrice = $variant['original_price'] ?? null;
            } else {
                $price = $variant->price ?? null;
                $originalPrice = $variant->original_price ?? null;
            }

            if ($price === null || $originalPrice === null) {
                $detailSpecs = is_array($variant)
                    ? ($variant['detail_specs'] ?? [])
                    : ($variant->detail_specs ?? []);
                if (!is_array($detailSpecs)) {
                    $detailSpecs = json_decode($detailSpecs, true) ?: [];
                }

                $detailInfo = is_array($variant)
                    ? ($variant['detail_info'] ?? [])
                    : ($variant->detail_info ?? []);
                if (!is_array($detailInfo)) {
                    $detailInfo = json_decode($detailInfo, true) ?: [];
                }

                if ($price === null) {
                    $price = $detailSpecs['price'] ?? ($detailInfo['price'] ?? 0);
                }
                if ($originalPrice === null) {
                    $originalPrice = $detailSpecs['original_price'] ?? ($detailInfo['original_price'] ?? 0);
                }
            }

            return [
                'price' => (int) round((float) ($price ?? 0)),
                'original_price' => (int) round((float) ($originalPrice ?? 0)),
            ];
        };

        $getCheapestVariantPricing = function ($product) use ($getVariantPricing): array {
            $variants = $product->variants ?? [];

            if (empty($variants)) {
                return [
                    'price' => 0,
                    'original_price' => 0,
                ];
            }

            $cheapest = null;
            foreach ($variants as $variant) {
                $pricing = $getVariantPricing($variant);
                if (($pricing['price'] ?? 0) <= 0) {
                    continue;
                }
                if ($cheapest === null || $pricing['price'] < $cheapest['price']) {
                    $cheapest = $pricing;
                }
            }

            return $cheapest ?? [
                'price' => 0,
                'original_price' => 0,
            ];
        };

        $products = $dataProvider->getModels();
        $productItems = array_map(function ($product) use ($getCheapestVariantPricing) {
            $pricing = $getCheapestVariantPricing($product);
            $rating  = $product->productRating ?? null;

            return [
                'id'             => $product->id,
                'name'           => $product->name,
                'badges'         => $product->badges,
                'thumbnail'      => $product->thumbnail,
                'rating'         => floatval($rating->rating ?? 0),
                'sold'           => intval($rating->sold ?? 0),
                'price'          => intval($pricing['price'] ?? 0),
                'original_price' => intval($pricing['original_price'] ?? 0),
            ];
        }, $products);

        $calculateDiscountPercent = function (array $item): float {
            $price = $item['price'] ?? 0;
            $originalPrice = $item['original_price'] ?? 0;

            if ($originalPrice <= 0 || $price <= 0 || $originalPrice <= $price) {
                return 0.0;
            }

            return ($originalPrice - $price) / $originalPrice;
        };

        $specialPromoItems = array_filter($productItems, function (array $item) use ($calculateDiscountPercent) {
            return $calculateDiscountPercent($item) > 0;
        });

        usort($specialPromoItems, function (array $a, array $b) use ($calculateDiscountPercent) {
            $discountA = $calculateDiscountPercent($a);
            $discountB = $calculateDiscountPercent($b);

            if ($discountA === $discountB) {
                return 0;
            }

            return ($discountA < $discountB) ? 1 : -1;
        });

        $specialPromoItems = array_slice($specialPromoItems, 0, 5);

        $todayBestDealItems = array_filter($productItems, function (array $item) use ($calculateDiscountPercent) {
            $rating = $item['rating'] ?? 0;
            $discount = $calculateDiscountPercent($item);

            return $rating > 0 && $discount > 0;
        });

        usort($todayBestDealItems, function (array $a, array $b) use ($calculateDiscountPercent) {
            $scoreA = ($a['rating'] ?? 0) * $calculateDiscountPercent($a);
            $scoreB = ($b['rating'] ?? 0) * $calculateDiscountPercent($b);

            if ($scoreA === $scoreB) {
                return 0;
            }

            return ($scoreA < $scoreB) ? 1 : -1;
        });

        $todayBestDealItems = array_slice($todayBestDealItems, 0, 5);

        $flashSaleItems = [];

        $activeFlashSales = FlashSale::find()
            ->where(Constants::STATUS_NOT_DELETED)
            ->activeNow()
            ->with(['product.variants', 'product.productRating'])
            ->all();

        foreach ($activeFlashSales as $flashSale) {
            $product = $flashSale->product;

            if ($product === null) {
                continue;
            }

            $rating  = $product->productRating ?? null;

            $pricing = $getCheapestVariantPricing($product);
            $originalPrice = intval($pricing['original_price'] ?? 0);

            $flashSaleItems[] = [
                'id'             => $product->id,
                'name'           => $product->name,
                'badges'         => $product->badges,
                'thumbnail'      => $product->thumbnail,
                'rating'         => floatval($rating->rating ?? 0),
                'sold'           => intval($rating->sold ?? 0),
                'price'          => intval($flashSale->flash_price),
                'original_price' => $originalPrice,
                'flash_sale'     => [
                    'id'         => $flashSale->id,
                    'flash_price'=> intval($flashSale->flash_price),
                    'stock'      => intval($flashSale->stock),
                    'start_at'   => $flashSale->start_at,
                    'end_at'     => $flashSale->end_at,
                ],
            ];
        }

        $bannerSearchModel = new BannerSearch();
        $bannerDataProvider = $bannerSearchModel->search([]);

        CoreController::validateProvider($bannerDataProvider, $bannerSearchModel);

        $bannerModels = $bannerDataProvider->getModels();

        $bannerItems = array_map(function ($banner) {
            return [
                'id'          => $banner->id,
                'name'        => $banner->name,
                'image'       => $banner->image,
            ];
        }, $bannerModels);

        $categorySearchModel = new CategorySearch();
        $categoryDataProvider = $categorySearchModel->search([]);

        CoreController::validateProvider($categoryDataProvider, $categorySearchModel);

        $featuredCategories = $categoryDataProvider->getModels();

        $featuredItems = array_map(function ($category) {
            return [
                'id'   => $category->id,
                'name' => $category->name,
                'image' => $category->image,
            ];
        }, $featuredCategories);

        $ratingSearchModel = new ProductRatingSearch();
        $ratingDataProvider = $ratingSearchModel->search([]);

        CoreController::validateProvider($ratingDataProvider, $ratingSearchModel);

        $ratingDataProvider->query->ratingBetween(4, 5);

        $ratingModels = $ratingDataProvider->getModels();

        $ratingItems = [];

        foreach ($ratingModels as $rating) {
            $detailInfo = $rating->detail_info ?? [];
            if (!is_array($detailInfo)) {
                $detailInfo = json_decode($detailInfo, true) ?: [];
            }

            $productInfo = $detailInfo['product'] ?? [];
            $productName = is_array($productInfo) ? ($productInfo['name'] ?? null) : null;

            $reviews = $rating->review ?? [];
            if (!is_array($reviews)) {
                $reviews = json_decode($reviews, true) ?: [];
            }

            foreach ($reviews as $review) {
                $reviewRating = $review['rating'] ?? null;
                if ($reviewRating === null) {
                    continue;
                }

                $reviewRatingFloat = floatval($reviewRating);
                if ($reviewRatingFloat < 4 || $reviewRatingFloat > 5) {
                    continue;
                }

                $ratingItems[] = [
                    'product_id'   => $rating->product_id,
                    'product_name' => $productName,
                    'name'         => $review['name'] ?? null,
                    'rating'       => $reviewRatingFloat,
                    'comment'      => $review['comment'] ?? null,
                ];
            }
        }

        $data = [
            'banner'            => $bannerItems,
            'special_promo'     => $specialPromoItems,
            'flash_sale'        => $flashSaleItems,
            'today_best_deal'   => $todayBestDealItems,
            'featured_products' => $featuredItems,
            'products'          => $productItems,
            'ratings'           => $ratingItems,
        ];

        return CoreController::coreCustomData($data);
    }
}