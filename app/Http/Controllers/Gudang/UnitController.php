<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gudang\StoreUnitRequest;
use App\Http\Requests\Gudang\UpdateUnitRequest;
use App\Models\Master\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $units = Unit::query()
            ->withCount('materials')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('unit_name', 'like', "%{$search}%")
                      ->orWhere('symbol', 'like', "%{$search}%");
                });
            })
            ->orderBy('unit_name')
            ->paginate(15)
            ->withQueryString();

        return view('gudang.units.index', compact('units', 'search'));
    }

    public function create(): View
    {
        return view('gudang.units.create');
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        Unit::create($request->validated());

        return redirect()->route('gudang.units.index')
            ->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function edit(Unit $unit): View
    {
        return view('gudang.units.edit', compact('unit'));
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return redirect()->route('gudang.units.index')
            ->with('success', 'Perubahan satuan berhasil disimpan.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($unit->materials()->exists()) {
            return redirect()->route('gudang.units.index')
                ->with('error', 'Satuan tidak dapat dihapus karena sedang digunakan oleh data material.');
        }

        $unit->delete();

        return redirect()->route('gudang.units.index')
            ->with('success', 'Satuan berhasil dihapus.');
    }
}
