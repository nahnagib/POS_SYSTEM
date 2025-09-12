<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;



class DashboardController extends Controller
{

    public function index()
    {
        // ---- KPIs
        $totalRevenue   = (float) Order::sum('total'); // keep if orders table has `total`
        $ordersCount    = (int)   Order::count();
        $itemsSold      = (int)   OrderDetail::sum('quantity');
        $avgOrderValue  = $ordersCount ? round($totalRevenue / $ordersCount, 2) : 0.0;

        $todayRevenue   = (float) Order::whereDate('created_at', today())->sum('total');
        $monthRevenue   = (float) Order::whereYear('created_at', now()->year)
                                       ->whereMonth('created_at', now()->month)
                                       ->sum('total');

        // ---- Revenue last 30 days (for a line chart)
        $revenueDaily = Order::selectRaw('DATE(created_at) as d, SUM(total) as revenue')
            ->whereDate('created_at', '>=', now()->subDays(29)->toDateString())
            ->groupBy('d')->orderBy('d')
            ->get();

        $revLabels = $revenueDaily->pluck('d')->map(fn($d)=>date('M d', strtotime($d)))->all();
        $revValues = $revenueDaily->pluck('revenue')->map(fn($v)=>(float)$v)->all();

        // ---- Top 10 products by revenue
        // join using order_details.variant_id -> product_variants.id
        $topProducts = OrderDetail::query()
            ->join('product_variants as pv', 'pv.id', '=', 'order_details.variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->selectRaw('p.name as product,
                         COALESCE(pv.size_ml, 0) as size_ml,
                         SUM(order_details.quantity) as qty_sold,
                         SUM(order_details.line_total) as revenue')
            ->groupBy('p.name','pv.size_ml')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $topProdLabels = $topProducts->map(fn($r)=>$r->product . ($r->size_ml ? " ({$r->size_ml}ml)" : ""))->all();
        $topProdValues = $topProducts->pluck('revenue')->map(fn($v)=>(float)$v)->all();

        // ---- Low stock (products that track stock)
        // product_variants.stock <= products.low_stock_threshold
        $lowStock = ProductVariant::query()
            ->whereHas('product', fn($q)=>$q->where('track_stock', true))
            ->whereColumn('product_variants.stock', '<=', DB::raw('(select low_stock_threshold from products where products.id = product_variants.product_id)'))
            ->with('product:id,name,low_stock_threshold')
            ->orderBy('stock') // matches your column name
            ->limit(10)
            ->get(['id','product_id','size_ml','stock']);

        // ---- Top categories by revenue (optional)
        $topCategories = OrderDetail::query()
            ->join('product_variants as pv', 'pv.id', '=', 'order_details.variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->selectRaw('COALESCE(c.name, "Uncategorized") as category,
                         SUM(order_details.line_total) as revenue')
            ->groupBy('category')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        $kpis = compact('totalRevenue','ordersCount','itemsSold','avgOrderValue','todayRevenue','monthRevenue');

        return view('admin.dashboard', compact(
            'kpis',
            'revLabels','revValues',
            'topProdLabels','topProdValues','topProducts',
            'lowStock',
            'topCategories'
        ));
    }
}
