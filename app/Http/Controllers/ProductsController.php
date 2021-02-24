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
                        $query->whereMultiMatch(['title^3','long_title^2','category^2','description'],$keyword)
                            ->orWhere(function(ProductEs $query)use($keyword){
                                $query->whereNested('skus',function(ProductEs $query)use($keyword){
                                    $query->whereMatch('skus.title',$keyword)->orWhereMatch('skus.description',$keyword);
                                });
                            })
                            ->orWhere(function(ProductEs $query)use($keyword){
                                $query->whereNested('properties',['skus.value'=>$keyword]);
                            });
                    });
                }
            });
        }
        $result = $es->get();

        $productIds = collect($result['list'])->pluck('id')->all();
        $products = Product::query()
            ->whereIn('id',$productIds)
            ->orderByRaw(sprintf("find_in_set(id,'%s')",join(',', $productIds)))->get();
        $pager = new LengthAwarePaginator($products,$result['total'],$perPage,$page,[
            'path' => route('products.index',false)
        ]);

        return view('products.index', [
            'products' => $pager,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
            'category' => $category ?? null,
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

        return view('products.show',['product' => $product,'favored' => $favored,'reviews' => $reviews]);
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
