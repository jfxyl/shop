<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Extensions\ProductEs;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\CategoryService;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page',1);
        $perPage = 16;

        $es = (new ProductEs())->from(($page - 1) * $perPage)
                ->size($perPage)
                ->filter('on_sale',true);

        if($order = $request->input('order','')){
            if(preg_match('/^(.+)_(asc|desc)$/',$order,$m)){
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $es->orderBy($m[1], $m[2]);
                }
            }
        }

        if($request->input('category_id') && $category = Category::find($request->input('category_id'))){
            if($category->is_directory){
                $es->filter(function(ProductEs $query)use($category){
                    $query->wherePrefix('category_path',$category->path.$category->id.'-');
                });
            }else{
                $es->where('category_id',$category->id);
            }
        }

        if($search = $request->input('search','')){
            $keywords = array_filter(explode(' ',$search));
            $es->where(function(ProductEs $query)use($keywords){
                foreach($keywords as $keyword){
                    $query->where(function(ProductEs $query)use($keyword){
                        $query->whereMultiMatch([
                            'title^3',
                            'long_title^2',
                            'category^2', // 类目名称
                            'description',
                            'skus_title',
                            'skus_description',
                            'properties_value'
                        ],$keyword);
                    });
                }
            });
        }

        $propertyFilters = [];
        if ($filterString = $request->input('filters')) {
            $filterArray = explode('|', $filterString);
            foreach ($filterArray as $filter) {
                list($name, $value) = explode(':', $filter);
                $propertyFilters[$name] = $value;
                $es->filter(function(ProductEs $query)use($name, $value){
                    $query->whereNested('properties',[
                        'properties.name' => $name,
                        'properties.value' => $value
                    ]);
                });
            }
        }

        if ($search || isset($category)) {
            $es->aggs('properties','nested',[
                'path' => 'properties',
            ],function(ProductEs $query){
                $query->groupBy('properties.name',[],function(ProductEs $query){
                    $query->groupBy('properties.value');
                });
            });
        }
        $result = $es->get();

        $productIds = collect($result['list'])->pluck('id')->all();
        $products = Product::query()
            ->byIds($productIds)
            ->get();
        $pager = new LengthAwarePaginator($products,$result['total'],$perPage,$page,[
            'path' => route('products.index',false)
        ]);
        $properties = [];
        // 如果返回结果里有 aggregations 字段，说明做了分面搜索
        if (isset($result['aggs'])) {
            // 使用 collect 函数将返回值转为集合
            $properties = collect($result['aggs']['properties']['properties.name_terms']['buckets'])
                ->map(function ($bucket) {
                    // 通过 map 方法取出我们需要的字段
                    return [
                        'key'    => $bucket['key'],
                        'values' => collect($bucket['properties.value_terms']['buckets'])->pluck('key')->all(),
                    ];
                })
                ->filter(function ($property) use ($propertyFilters) {
                    // 过滤掉只剩下一个值 或者 已经在筛选条件里的属性
                    return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]) ;
                });;
        }

        return view('products.index', [
            'products' => $pager,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
            'category' => $category ?? null,
            'properties' => $properties,
            'propertyFilters' => $propertyFilters,
        ]);
    }

    public function show(Product $product,Request $request)
    {
        if(!$product->on_sale){
            throw new InvalidRequestException('商品未上架');
        }
        $favored = false;
        if($user = $request->user()){
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku']) // 预先加载关联关系
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at') // 筛选出已评价的
            ->orderBy('reviewed_at', 'desc') // 按评价时间倒序
            ->limit(10) // 取出 10 条
            ->get();

        $es = new ProductEs();
        foreach($product->properties as $property){
            $es->orFilter(function(ProductEs $query)use($property){
                $query->whereNested('properties',['properties.search_value'=>$property->name.':'.$property->value]);
            });
        }
        $similarProducts = $es->minimumShouldMatch(intval(ceil(count($product->properties) / 2)))
            ->whereNot('id',$product->id)
            ->size(4)
            ->get();

        $similarProductIds = collect($similarProducts['list'])->pluck('_id')->all();
        // 根据 Elasticsearch 搜索出来的商品 ID 从数据库中读取商品数据
        $similarProducts   = Product::query()
            ->byIds($similarProductIds)
            ->get();

        return view('products.show',['product' => $product,'favored' => $favored,'reviews' => $reviews,'similar' => $similarProducts]);
    }

    public function favor(Product $product,Request $request)
    {
        $user = $request->user();
        if($user->favoriteProducts()->find($product->id)){
            return [];
        }
        $user->favoriteProducts()->attach($product);
        return [];
    }

    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }

    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', ['products' => $products]);
    }
}
