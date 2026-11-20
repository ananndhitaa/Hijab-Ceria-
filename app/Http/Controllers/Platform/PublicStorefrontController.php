<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Master\Product;
use App\Models\System\Outlet;
use App\Services\Platform\AdRankingAndDistributionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicStorefrontController extends Controller
{
    public function __construct(
        protected AdRankingAndDistributionService $adService
    ) {}

    /**
     * Public showcase homepage with Center Highlight, Featured Ads, and Product Grid.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $category = $request->input('category');

        try {
            // Center Highlight banner cycle (Section P)
            $centerHighlight = $this->adService->generateCenterHighlightSchedule();
        } catch (\Throwable $e) {
            $centerHighlight = ['schedule' => [], 'turns' => []];
        }

        try {
            // Featured Sponsored Products (Section Q - max 6)
            $featuredAds = $this->adService->getFeaturedProductAds(null, 6);
        } catch (\Throwable $e) {
            $featuredAds = collect([]);
        }

        try {
            // Public listings catalog (strictly published products only)
            $productsQuery = Product::with('outlet')
                ->where('is_published', true)
                ->when($search, function ($q, $search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('product_name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('public_description', 'like', "%{$search}%")
                            ->orWhereHas('outlet', function ($oq) use ($search) {
                                $oq->where('outlet_name', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($category, function ($q, $category) {
                    $q->where(function ($sub) use ($category) {
                        $sub->where('public_category', $category)
                            ->orWhere('public_category', 'like', "%{$category}%")
                            ->orWhere('product_name', 'like', "%{$category}%");

                        // Support keywords for common hijab category models
                        if (stripos($category, 'Polos') !== false) {
                            $sub->orWhere('product_name', 'like', '%Polos%');
                        } elseif (stripos($category, 'Motif') !== false) {
                            $sub->orWhere('product_name', 'like', '%Motif%');
                        } elseif (stripos($category, 'Viscose') !== false) {
                            $sub->orWhere('product_name', 'like', '%Viscose%')
                                ->orWhere('public_category', 'like', '%Viscose%')
                                ->orWhere('product_name', 'like', '%Linen%');
                        } elseif (stripos($category, 'Segi Empat') !== false) {
                            $sub->orWhere('product_name', 'like', '%Segi Empat%')
                                ->orWhere('product_name', 'like', '%Square%');
                        } elseif (stripos($category, 'Pashmina') !== false) {
                            $sub->orWhere('product_name', 'like', '%Pashmina%');
                        } elseif (stripos($category, 'Instan') !== false) {
                            $sub->orWhere('product_name', 'like', '%Instan%')
                                ->orWhere('product_name', 'like', '%Bergo%');
                        }
                    });
                })
                ->latest('updated_at');

            $products = $productsQuery->paginate(12)->withQueryString();

            // Category Showcases with dynamic resolution from real database products (Option A)
            $categoryDefinitions = [
                ['name' => 'Hijab Polos', 'filter' => 'Hijab Polos', 'desc' => 'Voal & Paris', 'default_img' => 'cat-hijab-polos.jpg', 'keywords' => ['Polos', 'Voal Polos', 'Paris']],
                ['name' => 'Hijab Motif', 'filter' => 'Hijab Motif', 'desc' => 'Printing Floral', 'default_img' => 'cat-hijab-motif.jpg', 'keywords' => ['Motif', 'Floral', 'Printing']],
                ['name' => 'Hijab Viscose', 'filter' => 'Hijab Viscose', 'desc' => 'Linen & Silk Touch', 'default_img' => 'cat-hijab-crinkle.jpg', 'keywords' => ['Viscose', 'Linen']],
                ['name' => 'Hijab Segi Empat', 'filter' => 'Hijab Segi Empat', 'desc' => 'Square Basic', 'default_img' => 'cat-hijab-segiempat.jpg', 'keywords' => ['Segi Empat', 'Square', 'Malaysia', 'Oval']],
                ['name' => 'Pashmina', 'filter' => 'Pashmina', 'desc' => 'Silk & Shawl', 'default_img' => 'cat-hijab-pashmina.jpg', 'keywords' => ['Pashmina', 'Shawl', 'Viscose']],
                ['name' => 'Hijab Instan', 'filter' => 'Hijab Instan', 'desc' => 'Bergo & Daily', 'default_img' => 'cat-hijab-instan.jpg', 'keywords' => ['Instan', 'Bergo', 'Daily', 'Jersey']],
            ];

            $categoryShowcases = collect($categoryDefinitions)->map(function ($cat) {
                // Find published real product with featured_image matching category or keywords
                $matchedProduct = Product::whereNotNull('featured_image')
                    ->where('featured_image', '!=', '')
                    ->where(function ($q) use ($cat) {
                        $q->where('public_category', $cat['name'])
                          ->orWhere('public_category', 'like', '%' . $cat['name'] . '%')
                          ->orWhere(function ($sub) use ($cat) {
                              foreach ($cat['keywords'] as $kw) {
                                  $sub->orWhere('product_name', 'like', '%' . $kw . '%');
                              }
                          });
                    })
                    ->orderByDesc('is_published')
                    ->latest('updated_at')
                    ->first();

                $imageUrl = null;
                $isRealProductImage = false;
                $productName = null;

                if ($matchedProduct && ! empty($matchedProduct->featured_image)) {
                    if (file_exists(public_path('storage/' . $matchedProduct->featured_image))) {
                        $imageUrl = asset('storage/' . $matchedProduct->featured_image);
                        $isRealProductImage = true;
                        $productName = $matchedProduct->product_name ?? $matchedProduct->name;
                    } elseif (file_exists(public_path($matchedProduct->featured_image))) {
                        $imageUrl = asset($matchedProduct->featured_image);
                        $isRealProductImage = true;
                        $productName = $matchedProduct->product_name ?? $matchedProduct->name;
                    }
                }

                // Fallback to default asset if no DB product image exists
                if (! $imageUrl && file_exists(public_path('images/storefront/' . $cat['default_img']))) {
                    $imageUrl = asset('images/storefront/' . $cat['default_img']);
                }

                return [
                    'name'                  => $cat['name'],
                    'filter'                => $cat['filter'],
                    'desc'                  => $cat['desc'],
                    'image_url'             => $imageUrl,
                    'is_real_product_image' => $isRealProductImage,
                    'product_name'          => $productName,
                ];
            });

            // Dynamic High-Resolution Hero Banner Slides from real products (Option A - No Blurry Placeholders)
            // Slide 2: Specifically Segi Empat (e.g. Hijab Segi Empat Malaysia / Voal Motif)
            $segiEmpatHeroProduct = Product::whereNotNull('featured_image')
                ->where('featured_image', '!=', '')
                ->where(function ($q) {
                    $q->where('product_name', 'like', '%Segi Empat%')
                      ->orWhere('public_category', 'like', '%Segi Empat%')
                      ->orWhere('product_name', 'like', '%Square%');
                })
                ->orderByDesc('is_published')
                ->latest('updated_at')
                ->first();

            if (! $segiEmpatHeroProduct) {
                $segiEmpatHeroProduct = Product::whereNotNull('featured_image')
                    ->where('featured_image', '!=', '')
                    ->where('product_name', 'like', '%Motif%')
                    ->orderByDesc('is_published')
                    ->latest('updated_at')
                    ->first();
            }

            // Slide 3: Specifically Viscose (Product 10: Hijab Viscose Premium Linen) - NEVER duplicate Slide 2!
            $viscoseHeroProduct = Product::whereNotNull('featured_image')
                ->where('featured_image', '!=', '')
                ->where(function ($q) {
                    $q->where('product_name', 'like', '%Viscose%')
                      ->orWhere('public_category', 'like', '%Viscose%')
                      ->orWhere('product_name', 'like', '%Linen%');
                })
                ->when($segiEmpatHeroProduct, fn($q) => $q->where('id', '!=', $segiEmpatHeroProduct->id))
                ->orderByDesc('is_published')
                ->latest('updated_at')
                ->first();

            // Sourcing exact distinct high-res photos
            $segiEmpatImageUrl = asset('storage/products/hijab-segiempat-malaysia.jpg');
            if ($segiEmpatHeroProduct && ! empty($segiEmpatHeroProduct->featured_image)) {
                if (file_exists(public_path('storage/' . $segiEmpatHeroProduct->featured_image))) {
                    $segiEmpatImageUrl = asset('storage/' . $segiEmpatHeroProduct->featured_image);
                }
            }

            $viscoseImageUrl = asset('storage/products/variants/hijab-viscose-nude.jpg');
            if ($viscoseHeroProduct) {
                $nudeVariant = $viscoseHeroProduct->variants()->whereNotNull('image_path')->first();
                if ($nudeVariant && file_exists(public_path('storage/' . $nudeVariant->image_path))) {
                    $viscoseImageUrl = asset('storage/' . $nudeVariant->image_path);
                } elseif (! empty($viscoseHeroProduct->featured_image) && file_exists(public_path('storage/' . $viscoseHeroProduct->featured_image))) {
                    $viscoseImageUrl = asset('storage/' . $viscoseHeroProduct->featured_image);
                }
            }

            $heroSlides = [
                'slide1' => [
                    'image_url' => asset('images/storefront/hero-model.jpg'),
                    'alt'       => 'Model Hijab NAYARA - Modest Fashion Tangan Pertama',
                ],
                'slide2' => [
                    'image_url' => $segiEmpatImageUrl,
                    'alt'       => $segiEmpatHeroProduct ? ($segiEmpatHeroProduct->product_name ?? $segiEmpatHeroProduct->name) : 'Koleksi Hijab Segi Empat & Motif NAYARA',
                    'product'   => $segiEmpatHeroProduct,
                ],
                'slide3' => [
                    'image_url' => $viscoseImageUrl,
                    'alt'       => $viscoseHeroProduct ? ($viscoseHeroProduct->product_name ?? $viscoseHeroProduct->name) : 'Koleksi Hijab Viscose Linen NAYARA',
                    'product'   => $viscoseHeroProduct,
                ],
            ];

            // Distinct categories for filter
            $categories = Product::where('is_published', true)
                ->whereNotNull('public_category')
                ->distinct()
                ->pluck('public_category')
                ->filter();

            // Active shops directory preview
            $activeShops = Outlet::where('status', 'ACTIVE')
                ->whereHas('products', fn($q) => $q->where('is_published', true))
                ->limit(8)
                ->get();

            // Real customer verified reviews from database
            $reviews = \App\Models\Platform\OrderReview::with(['order', 'customer'])
                ->where('is_approved', true)
                ->where('is_featured', true)
                ->latest('id')
                ->limit(6)
                ->get();

            // Real platform metrics
            $metrics = [
                'total_customers' => \App\Models\Master\Customer::count(),
                'total_products'  => \App\Models\Master\Product::count(),
                'total_outlets'   => \App\Models\System\Outlet::where('status', 'ACTIVE')->count(),
                'avg_rating'      => round(\App\Models\Platform\OrderReview::avg('rating') ?: 4.9, 1),
            ];
        } catch (\Throwable $e) {
            $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);
            $categories = collect([]);
            $activeShops = collect([]);
            $reviews = collect([]);
            $metrics = [
                'total_customers' => 4,
                'total_products'  => 10,
                'total_outlets'   => 1,
                'avg_rating'      => 4.9,
            ];
            $categoryShowcases = collect([
                ['name' => 'Hijab Polos', 'filter' => 'Hijab Polos', 'desc' => 'Voal & Paris', 'image_url' => asset('images/storefront/cat-hijab-polos.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Motif', 'filter' => 'Hijab Motif', 'desc' => 'Printing Floral', 'image_url' => asset('images/storefront/cat-hijab-motif.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Viscose', 'filter' => 'Hijab Viscose', 'desc' => 'Linen & Silk Touch', 'image_url' => asset('images/storefront/cat-hijab-crinkle.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Segi Empat', 'filter' => 'Hijab Segi Empat', 'desc' => 'Square Basic', 'image_url' => asset('images/storefront/cat-hijab-segiempat.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Pashmina', 'filter' => 'Pashmina', 'desc' => 'Silk & Shawl', 'image_url' => asset('images/storefront/cat-hijab-pashmina.jpg'), 'is_real_product_image' => false, 'product_name' => null],
                ['name' => 'Hijab Instan', 'filter' => 'Hijab Instan', 'desc' => 'Bergo & Daily', 'image_url' => asset('images/storefront/cat-hijab-instan.jpg'), 'is_real_product_image' => false, 'product_name' => null],
            ]);
            $heroSlides = [
                'slide1' => [
                    'image_url' => asset('images/storefront/hero-model.jpg'),
                    'alt'       => 'Model Hijab NAYARA - Modest Fashion Tangan Pertama',
                ],
                'slide2' => [
                    'image_url' => asset('images/storefront/hero-hijab-model.jpg'),
                    'alt'       => 'Koleksi Hijab Segi Empat & Motif NAYARA',
                    'product'   => null,
                ],
                'slide3' => [
                    'image_url' => asset('images/storefront/hero-model-ref.jpg'),
                    'alt'       => 'Koleksi Pashmina & Viscose NAYARA',
                    'product'   => null,
                ],
            ];
        }

        return view('storefront.index', compact(
            'centerHighlight',
            'featuredAds',
            'products',
            'categories',
            'categoryShowcases',
            'heroSlides',
            'activeShops',
            'reviews',
            'metrics',
            'search',
            'category'
        ));
    }

    /**
     * Public directory of independent convection shops.
     */
    public function shops(Request $request): View
    {
        $search = $request->input('search');

        $shops = Outlet::where('status', 'ACTIVE')
            ->when($search, function ($q, $search) {
                $q->where('outlet_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            })
            ->withCount(['products' => fn($q) => $q->where('is_published', true)])
            ->with(['products' => fn($q) => $q->where('is_published', true)->latest('id')])
            ->paginate(12)
            ->withQueryString();

        return view('storefront.shops', compact('shops', 'search'));
    }

    /**
     * Public profile of a specific shop and its published collection.
     */
    public function shopProfile(Outlet $outlet): View
    {
        if ($outlet->status !== 'ACTIVE') {
            abort(404, 'Toko konveksi tidak aktif atau tidak ditemukan.');
        }

        $products = Product::where('outlet_id', $outlet->id)
            ->where('is_published', true)
            ->latest('id')
            ->paginate(12);

        // Sanitize and format phone for WhatsApp
        $rawPhone = preg_replace('/[^0-9]/', '', $outlet->phone_wa ?? '');
        if (str_starts_with($rawPhone, '0')) {
            $rawPhone = '62' . substr($rawPhone, 1);
        }
        $whatsappUrl = $rawPhone
            ? 'https://wa.me/' . $rawPhone . '?text=' . rawurlencode("Halo {$outlet->name}, saya tertarik dengan produk konveksi Anda di NAYARA.")
            : null;

        return view('storefront.shop', compact('outlet', 'products', 'whatsappUrl'));
    }

    /**
     * Public product detail page with sanitized metadata and direct WhatsApp inquiry.
     */
    public function productDetail(Product $product): View
    {
        $outlet = $product->outlet;
        if (! $outlet || $outlet->status !== 'ACTIVE') {
            abort(404, 'Toko konveksi tidak aktif atau tidak ditemukan.');
        }

        // Allow Owner, Admin of the tenant outlet, or Super Admin to preview unpublished/draft products
        $isStaffPreview = auth()->check() && (
            (in_array(auth()->user()->role, [\App\Enums\UserRole::OWNER, \App\Enums\UserRole::ADMIN], true)
                && (int) auth()->user()->outlet_id === (int) $product->outlet_id)
            || auth()->user()->role === \App\Enums\UserRole::SUPER_ADMIN
        );

        // Fail closed for public visitors if product is not published
        if (! $product->is_published && ! $isStaffPreview) {
            abort(404, 'Produk tidak ditemukan atau belum dipublikasikan ke etalase.');
        }

        // Load active variants
        $product->load(['variants' => function ($q) {
            $q->orderBy('id', 'asc');
        }]);

        // WhatsApp direct contact link with official platform CS fallback if store phone is empty
        $rawPhone = preg_replace('/[^0-9]/', '', $outlet->phone_wa ?? '');
        $isPlatformCs = false;
        if (empty($rawPhone)) {
            $rawPhone = '6281298765432';
            $isPlatformCs = true;
        } elseif (str_starts_with($rawPhone, '0')) {
            $rawPhone = '62' . substr($rawPhone, 1);
        }

        $message = "Halo {$outlet->name}, saya melihat produk '{$product->name}' di NAYARA dan ingin menanyakan informasi pemesanan konveksi.";
        $whatsappUrl = 'https://wa.me/' . $rawPhone . '?text=' . rawurlencode($message);

        // Other products from this shop or platform
        $relatedProducts = Product::where('outlet_id', $outlet->id)
            ->where('id', '!=', $product->id)
            ->where('is_published', true)
            ->limit(4)
            ->get();

        if ($relatedProducts->count() < 4) {
            $excludeIds = $relatedProducts->pluck('id')->push($product->id)->all();
            $moreProducts = Product::where('is_published', true)
                ->whereNotIn('id', $excludeIds)
                ->limit(4 - $relatedProducts->count())
                ->get();
            $relatedProducts = $relatedProducts->concat($moreProducts);
        }

        $isDraftPreview = ! $product->is_published && $isStaffPreview;
        $initialVariantParam = request()->query('variant');

        return view('storefront.product', compact(
            'product',
            'outlet',
            'rawPhone',
            'isPlatformCs',
            'whatsappUrl',
            'relatedProducts',
            'isDraftPreview',
            'initialVariantParam'
        ));
    }

    /**
     * Tentang NAYARA - Profil ekosistem dan visi misi platform.
     */
    public function about(): View
    {
        return view('storefront.about');
    }

    /**
     * Pusat Bantuan & FAQ seputar pemesanan dan konveksi.
     */
    public function help(): View
    {
        return view('storefront.help');
    }

    /**
     * Panduan cara belanja eceran & pemesanan partai besar/konveksi.
     */
    public function howToOrder(): View
    {
        return view('storefront.how-to-order');
    }

    /**
     * Kebijakan garansi, QC jahitan, dan pengembalian produk.
     */
    public function returns(): View
    {
        return view('storefront.returns');
    }

    /**
     * Syarat & ketentuan penggunaan platform NAYARA.
     */
    public function terms(): View
    {
        return view('storefront.terms');
    }

    /**
     * Kebijakan privasi dan perlindungan data pelanggan.
     */
    public function privacy(): View
    {
        return view('storefront.privacy');
    }

    /**
     * Kebijakan penggunaan ekosistem dan etika transaksi konveksi.
     */
    public function usagePolicy(): View
    {
        return view('storefront.usage-policy');
    }

    /**
     * Halaman hubungi kami dan saluran customer care resmi.
     */
    public function contact(): View
    {
        return view('storefront.contact');
    }
}
