<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::query()
            ->withCount('variants')
            ->when($r->q, fn($w)=>$w->where(fn($x)=>$x
                ->where('name','like',"%{$r->q}%")
                ->orWhere('brand','like',"%{$r->q}%")
                ->orWhere('sku','like',"%{$r->q}%")
            ))
            ->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.products.index', ['products'=>$q]);
    }

    public function create()
    {
        return view('admin.products.create');
    }

     public function store(Request $r)
    {
        $data = $r->validate([
            'name' => ['required','string','max:255'],
            'brand'=> ['nullable','string','max:255'],
            'sku'  => ['nullable','string','max:64','unique:products,sku'],
            'category_id' => ['nullable','exists:categories,id'],
            'track_stock' => ['boolean'],
            'low_stock_threshold' => ['integer','min:0'],
            'is_active' => ['boolean'],
            // variants[]
            'variants' => ['array'],
            'variants.*.size_ml'    => ['nullable','numeric','min:0'],
            'variants.*.sku'        => ['nullable','string','max:64','unique:product_variants,sku'],
            'variants.*.barcode'    => ['nullable','string','max:20','unique:product_variants,barcode'],
            'variants.*.price'      => ['required','numeric','min:0'],
            'variants.*.cost_price' => ['nullable','numeric','min:0'],
            'variants.*.stock_qty'  => ['nullable','integer','min:0'],
            'variants.*.is_active'  => ['sometimes','boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $product = Product::create([
                'name' => $data['name'],
                'brand'=> $data['brand'] ?? null,
                'sku'  => $data['sku'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'track_stock' => $data['track_stock'] ?? true,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 2,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['variants'] ?? [] as $row) {
                ProductVariant::create([
                    'product_id'  => $product->id,
                    'size_ml'     => $row['size_ml'] ?? null,
                    'sku'         => $row['sku'] ?? null,
                    'barcode'     => $row['barcode'] ?? str()->random(13),
                    'price'       => $row['price'],
                    'cost_price'  => $row['cost_price'] ?? 0,
                    'stock_qty'   => $row['stock_qty'] ?? 0,
                    'is_active'   => $row['is_active'] ?? true,
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('success','Product created.');
    }
    public function show(Product $product)
    {
        $product->load('variants');
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $r, Product $product)
    {
        $data = $r->validate([
            'name' => ['required','string','max:255'],
            'brand'=> ['nullable','string','max:255'],
            'sku'  => ['nullable','string','max:64','unique:products,sku,'.$product->id],
            'category_id' => ['nullable','exists:categories,id'],
            'track_stock' => ['boolean'],
            'low_stock_threshold' => ['integer','min:0'],
            'is_active' => ['boolean'],
            // variants upsert
            'variants' => ['array'],
            'variants.*.id'         => ['nullable','integer','exists:product_variants,id'],
            'variants.*.size_ml'    => ['nullable','numeric','min:0'],
            'variants.*.sku'        => ['nullable','string','max:64'],
            'variants.*.barcode'    => ['nullable','string','max:20'],
            'variants.*.price'      => ['required','numeric','min:0'],
            'variants.*.cost_price' => ['nullable','numeric','min:0'],
            'variants.*.stock_qty'  => ['nullable','integer','min:0'],
            'variants.*.is_active'  => ['sometimes','boolean'],
        ]);

        DB::transaction(function () use ($data, $product) {
            $product->update([
                'name' => $data['name'],
                'brand'=> $data['brand'] ?? null,
                'sku'  => $data['sku'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'track_stock' => $data['track_stock'] ?? true,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 2,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $keep = [];
            foreach ($data['variants'] ?? [] as $row) {
                if (!empty($row['id'])) {
                    $v = ProductVariant::where('product_id',$product->id)->findOrFail($row['id']);
                    $v->update([
                        'size_ml'     => $row['size_ml'] ?? null,
                        'sku'         => $row['sku'] ?? null,
                        'barcode'     => $row['barcode'] ?? $v->barcode,
                        'price'       => $row['price'],
                        'cost_price'  => $row['cost_price'] ?? 0,
                        'stock_qty'   => $row['stock_qty'] ?? 0,
                        'is_active'   => $row['is_active'] ?? true,
                    ]);
                    $keep[] = $v->id;
                } else {
                    $keep[] = ProductVariant::create([
                        'product_id'  => $product->id,
                        'size_ml'     => $row['size_ml'] ?? null,
                        'sku'         => $row['sku'] ?? null,
                        'barcode'     => $row['barcode'] ?? str()->random(13),
                        'price'       => $row['price'],
                        'cost_price'  => $row['cost_price'] ?? 0,
                        'stock_qty'   => $row['stock_qty'] ?? 0,
                        'is_active'   => $row['is_active'] ?? true,
                    ])->id;
                }
            }

            ProductVariant::where('product_id',$product->id)
                ->when($keep, fn($q)=>$q->whereNotIn('id',$keep))
                ->delete();
        });

        return back()->with('success','Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return to_route('admin.products.index')->with('success','Deleted.');
    }

}
