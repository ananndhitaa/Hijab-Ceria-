<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Master\Product;
use App\Services\Platform\ProductPublicationService;
use App\Services\Platform\TenantEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OwnerPublicationController extends Controller
{
    public function __construct(
        protected TenantEntitlementService $entitlementService,
        protected ProductPublicationService $publicationService
    ) {}

    /**
     * List products with publication status and quota usage.
     */
    public function index(Request $request): View
    {
        $outletId = (int) auth()->user()->outlet_id;

        $capacity = $this->entitlementService->getListingCapacity($outletId);
        $publishedCount = $this->entitlementService->getPublishedListingCount($outletId);
        $remainingSlots = $this->entitlementService->getRemainingListingSlots($outletId);
        $isOverQuota = $this->entitlementService->isOverListingQuota($outletId);
        $hasPremium = $this->entitlementService->hasActivePremium($outletId);

        $search = $request->input('search');
        $statusFilter = $request->input('filter'); // 'published', 'draft'

        $baseQuery = Product::where('outlet_id', $outletId);
        $totalProducts = (clone $baseQuery)->count();
        $draftCount = (clone $baseQuery)->where('is_published', false)->count();

        $products = Product::where('outlet_id', $outletId)
            ->with(['variants' => fn($q) => $q->orderBy('variant_name')])
            ->when($search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('product_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('public_category', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter === 'published', fn($q) => $q->where('is_published', true))
            ->when($statusFilter === 'draft', fn($q) => $q->where('is_published', false))
            ->orderByDesc('is_published')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $pageTitle = 'Etalase Publik';
        $breadcrumb = 'Executive Monitoring > Etalase Publik';

        return view('owner.publications.index', compact(
            'products',
            'capacity',
            'publishedCount',
            'remainingSlots',
            'isOverQuota',
            'hasPremium',
            'search',
            'statusFilter',
            'totalProducts',
            'draftCount',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Publish product to public storefront.
     */
    public function publish(Product $product): RedirectResponse
    {
        $outletId = (int) auth()->user()->outlet_id;

        if ((int) $product->outlet_id !== $outletId) {
            abort(403, 'Akses ditolak: Produk bukan milik outlet Anda.');
        }

        try {
            $this->publicationService->publish($product, $outletId);
            return back()->with('success', "Produk '{$product->name}' berhasil diterbitkan ke etalase publik.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unpublish product from public storefront.
     */
    public function unpublish(Product $product): RedirectResponse
    {
        $outletId = (int) auth()->user()->outlet_id;

        if ((int) $product->outlet_id !== $outletId) {
            abort(403, 'Akses ditolak: Produk bukan milik outlet Anda.');
        }

        try {
            $this->publicationService->unpublish($product, $outletId);
            return back()->with('success', "Produk '{$product->name}' berhasil ditarik dari etalase publik.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update public showcase details (price, description, category, image).
     */
    public function updateShowcase(Request $request, Product $product): RedirectResponse
    {
        $outletId = (int) auth()->user()->outlet_id;

        if ((int) $product->outlet_id !== $outletId) {
            abort(403, 'Akses ditolak: Produk bukan milik outlet Anda.');
        }

        $rules = [
            'public_description' => ['nullable', 'string', 'max:2000'],
            'public_price' => ['nullable', 'numeric', 'min:0'],
            'public_category' => ['nullable', 'string', 'max:100'],
            'remove_image' => ['nullable', 'boolean'],
        ];

        if ($request->hasFile('featured_image')) {
            $rules['featured_image'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'];
        } else {
            $rules['featured_image'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        $newUploadedPath = null;
        $oldImageToDelete = null;

        try {
            $data = [
                'public_description' => $validated['public_description'] ?? null,
                'public_price' => $validated['public_price'] ?? null,
                'public_category' => $validated['public_category'] ?? null,
            ];

            if ($request->hasFile('featured_image')) {
                $newUploadedPath = $request->file('featured_image')->store('products', 'public');
                $data['featured_image'] = $newUploadedPath;
                $oldImageToDelete = $product->featured_image;
            } elseif ($request->boolean('remove_image')) {
                $data['featured_image'] = null;
                $oldImageToDelete = $product->featured_image;
            } elseif (array_key_exists('featured_image', $validated) && is_string($validated['featured_image'])) {
                $data['featured_image'] = $validated['featured_image'];
            }

            // Persist showcase details to database first
            $this->publicationService->updateShowcaseDetails($product, $outletId, $data);

            // ONLY AFTER DB UPDATE SUCCEEDS: safely clean up old file if not referenced by other products
            if ($oldImageToDelete && $oldImageToDelete !== ($data['featured_image'] ?? null)) {
                $isStillUsedByOthers = Product::where('featured_image', $oldImageToDelete)
                    ->where('id', '!=', $product->id)
                    ->exists();

                if (! $isStillUsedByOthers && Storage::disk('public')->exists($oldImageToDelete)) {
                    Storage::disk('public')->delete($oldImageToDelete);
                }
            }

            return back()->with('success', "Informasi etalase produk '{$product->name}' berhasil diperbarui.");
        } catch (\Throwable $e) {
            // Clean up newly uploaded file if database persistence failed to avoid orphaned files
            if ($newUploadedPath && Storage::disk('public')->exists($newUploadedPath)) {
                Storage::disk('public')->delete($newUploadedPath);
            }

            if ($e instanceof ValidationException) {
                return back()->withErrors($e->errors())->with('error', $e->getMessage());
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update variant image from owner publication management.
     */
    public function updateVariantImage(Request $request, \App\Models\Master\ProductVariant $variant): RedirectResponse
    {
        $outletId = (int) auth()->user()->outlet_id;

        if ((int) $variant->outlet_id !== $outletId && (int) $variant->product?->outlet_id !== $outletId) {
            abort(403, 'Akses ditolak: Varian bukan milik outlet Anda.');
        }

        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('products/variants', 'public');
            if ($variant->image_path && Storage::disk('public')->exists($variant->image_path)) {
                Storage::disk('public')->delete($variant->image_path);
            }
            $variant->update(['image_path' => $newImage]);
        } elseif ($request->boolean('remove_image')) {
            if ($variant->image_path && Storage::disk('public')->exists($variant->image_path)) {
                Storage::disk('public')->delete($variant->image_path);
            }
            $variant->update(['image_path' => null]);
        }

        return back()->with('success', "Foto untuk varian '{$variant->variant_name}' berhasil diperbarui.");
    }
}
