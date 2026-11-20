# Project Architecture & Structural Convention

Dokumen ini mendefinisikan standar struktur direktori dan arsitektur aplikasi Hijab ERP setelah refaktorisasi arsitektur per PRD v1.6 (Pasca Gudang Step G2).

---

## 1. Prinsip Utama Arsitektur

Struktur aplikasi Hijab ERP dipisahkan menjadi dua lapisan dengan logika pengelompokan yang berbeda:

1. **HTTP / UI Layer $\rightarrow$ ROLE-BASED**:
   - Controller, Form Request, dan View dikelompokkan berdasarkan **Role Pengguna** (`Admin`, `Gudang`, `Produksi`, `Owner`, `Auth`, `Customer`).
   - Setiap role memiliki interface, permission, dan lifecycle yang terisolasi.
2. **Domain Layer $\rightarrow$ BUSINESS DOMAIN-BASED**:
   - Model dan Service dikelompokkan berdasarkan **Domain Bisnis** (`Master`, `Sales`, `Inventory`, `Production`, `Finance`, `System`).
   - Model mewakili entitas bisnis bersama dan **TIDAK PERNAH** diduplikasi atau dikelompokkan berdasarkan role pengguna.

---

## 2. Struktur Direktori

```
app/
├── Enums/                              # Flat Enums (BatchStatus, OrderStatus, UserRole, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                      # Role Admin Controllers
│   │   │   ├── BomController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── InvoiceController.php
│   │   │   ├── KanbanController.php
│   │   │   ├── OrderController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ProductController.php
│   │   │   ├── ProductVariantController.php
│   │   │   ├── ProductionBatchController.php
│   │   │   └── SalesReportController.php
│   │   ├── Auth/
│   │   │   └── LoginController.php
│   │   ├── Gudang/                     # Role Gudang Controllers
│   │   │   ├── DashboardController.php
│   │   │   ├── MaterialController.php
│   │   │   ├── MaterialPlanningController.php
│   │   │   ├── SupplierController.php
│   │   │   └── UnitController.php
│   │   ├── Produksi/                   # (Role Produksi - Masa Datang)
│   │   ├── Owner/                      # (Role Owner - Masa Datang)
│   │   ├── Controller.php              # Base Controller
│   │   └── DashboardController.php     # Shared /dashboard redirect router
│   ├── Middleware/
│   │   ├── EnsureUserIsActive.php
│   │   └── RoleMiddleware.php
│   └── Requests/
│       ├── Admin/                      # Role Admin Form Requests
│       └── Gudang/                     # Role Gudang Form Requests
├── Models/
│   ├── Finance/                        # Domain Finansial & Biaya
│   │   ├── BatchCost.php
│   │   ├── BusinessExpense.php
│   │   └── OrderFinancial.php
│   ├── Inventory/                      # Domain Inventori & Bahan Baku
│   │   ├── MaterialStock.php
│   │   ├── Purchase.php
│   │   ├── PurchaseItem.php
│   │   └── StockMovement.php
│   ├── Master/                         # Domain Master Data
│   │   ├── BomItem.php
│   │   ├── Customer.php
│   │   ├── Material.php
│   │   ├── Product.php
│   │   ├── ProductVariant.php
│   │   ├── Supplier.php
│   │   └── Unit.php
│   ├── Production/                     # Domain Produksi & QC
│   │   ├── BatchMaterial.php
│   │   ├── ProductionBatch.php
│   │   ├── ProductionResult.php
│   │   └── QcResult.php
│   ├── Sales/                          # Domain Penjualan & Tagihan
│   │   ├── Invoice.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   └── Payment.php
│   └── System/                         # Domain Sistem & Transversal
│       ├── DocumentSequence.php
│       ├── OrderStatusHistory.php
│       ├── OrderTrackingToken.php
│       ├── Shipment.php
│       └── User.php
├── Services/
│   ├── Inventory/
│   │   └── BatchMaterialPlanningService.php
│   ├── Production/
│   │   └── BatchService.php
│   ├── Sales/
│   │   ├── InvoiceService.php
│   │   ├── OrderStatusService.php
│   │   ├── PaymentService.php
│   │   └── SalesReportService.php
│   ├── System/
│   │   └── DocumentNumberGenerator.php
│   └── Tracking/
│       └── TrackingTokenService.php
├── Support/
│   ├── CsvSanitizer.php
│   └── MoneyFormatter.php
└── View/
    └── Components/
        ├── AppLayout.php
        └── GuestLayout.php
```

---

## 3. Pemetaan Model & Service Berdasarkan Domain

| Domain | Namespace Model | Model Termasuk | Namespace Service | Service Termasuk |
|---|---|---|---|---|
| **Master** | `App\Models\Master` | `Customer`, `Supplier`, `Unit`, `Product`, `ProductVariant`, `Material`, `BomItem` | — | — |
| **Sales** | `App\Models\Sales` | `Order`, `OrderItem`, `Invoice`, `Payment` | `App\Services\Sales` | `InvoiceService`, `OrderStatusService`, `PaymentService`, `SalesReportService` |
| **Inventory** | `App\Models\Inventory` | `MaterialStock`, `Purchase`, `PurchaseItem`, `StockMovement` | `App\Services\Inventory` | `BatchMaterialPlanningService` |
| **Production** | `App\Models\Production` | `ProductionBatch`, `BatchMaterial`, `ProductionResult`, `QcResult` | `App\Services\Production` | `BatchService` |
| **Finance** | `App\Models\Finance` | `BatchCost`, `BusinessExpense`, `OrderFinancial` | — | *(Future Finance Services)* |
| **System** | `App\Models\System` | `User`, `OrderTrackingToken`, `OrderStatusHistory`, `Shipment`, `DocumentSequence` | `App\Services\System` | `DocumentNumberGenerator` |
| **Tracking** | — | *(Menggunakan Order & OrderTrackingToken)* | `App\Services\Tracking` | `TrackingTokenService` |

---

## 4. Struktur Routing

Routes dipisahkan secara modular ke dalam sub-file rute:
- `routes/web.php`: Rute publik (landing, tracking token, login/logout, route redirect `/dashboard`).
- `routes/admin.php`: Rute spesifik peran ADMIN dengan prefix `admin/` dan nama rute `admin.*`.
- `routes/gudang.php`: Rute spesifik peran GUDANG dengan prefix `gudang/` dan nama rute `gudang.*`.

---

## 5. Panduan Pengembangan Role Mendatang (Produksi & Owner)

Untuk implementasi role masa mendatang (Produksi/QC, Owner):
1. **Controllers**: Buat di `app/Http/Controllers/Produksi/` atau `app/Http/Controllers/Owner/`.
2. **Form Requests**: Buat di `app/Http/Requests/Produksi/` atau `app/Http/Requests/Owner/`.
3. **Views**: Tempatkan di `resources/views/produksi/` atau `resources/views/owner/`.
4. **Routes**: Buat `routes/produksi.php` dan `routes/owner.php`, lalu `require` di `routes/web.php`.
5. **Models**: Gunakan model domain yang ada (`App\Models\Production\*`, `App\Models\Finance\*`, dst). **JANGAN PERNAH** membuat subdirektori model per role (misalnya `App\Models\Produksi\Batch`).
6. **Services**: Tempatkan logika bisnis di namespace domain terkait (`App\Services\Production\*`, `App\Services\Finance\*`).
