<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 *
 */
class ProductController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $products = Product::query();

            $perPage = $request->get('perPage', 20);
            $search = $request->get('search', '');

            if ($search && $search !== '') {
                $products = $products->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            $categoryId = $request->get('category');

            if ($categoryId) {
                $products = $products->where('category_id', $categoryId);
            }

            $status = $request->get('status');

            if ($status) {
                $products = $products->where('status', $status);
            }

            $products = $products->paginate($perPage);

            $results = [
                'data' => $products->items(),
                'currentPage' => $products->currentPage(),
                'perPage' => $products->perPage(),
                'total' => $products->total(),
                'hasMorePages' => $products->hasMorePages()
            ];

            return $this->sendResponse($results);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:100',
                'category_id' => 'required|exists:categories,id',
                'description' => 'required',
                'quantity' => 'required|integer|min:0',
                'price' => 'required|numeric|min:0',
                'image' => 'nullable|image',
                'status' => 'nullable|in:0,1',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');
            $categoriId = $request->get('category_id');
            $description = $request->get('description');
            $quantity = $request->get('quantity');
            $price = $request->get('price');

            if ($request->has('image')) {
                $file = $request->file('image');

                $filename = 'P' . time() . '.' . $file->getClientOriginalExtension();

                $path = 'products/';

                Storage::putFileAs($path, $file, $filename);

                $image = $path . $filename;


            } else {
                $image = null;
            }

            $status = $request->get('status', 0);


            $product = new Product();
            $product->name = $name;
            $product->category_id = $categoriId;
            $product->description = $description;
            $product->quantity = $quantity;
            $product->price = $price;
            $product->price = $price;
            $product->status = $status;
            $product->save();

            return $this->sendResponse([], Response::HTTP_CREATED);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get($id): JsonResponse
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return $this->sendError('Product not found!', [], Response::HTTP_NOT_FOUND);
            }

            return $this->sendResponse($product->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update($id, Request $request): JsonResponse
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return $this->sendError('Product not found!', [], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|max:100',
                'category_id' => 'nullable|exists:categories,id',
                'description' => 'nullable',
                'quantity' => 'nullable|integer|min:0',
                'price' => 'nullable|numeric|min:0',
                'image' => 'nullable|image',
                'status' => 'nullable|in:0,1',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');
            $categoryId = $request->get('category_id');
            $description = $request->get('description');
            $quantity = $request->get('quantity');
            $price=$request->get('price');
            $image=$request->get('image');
            $status=$request->get('status');

            if ($name) {
                $product->name = $name;
            }
            if ($categoryId) {
                $product->category_id = $categoryId;
            }
            if ($description) {
                $product->description = $description;
            }
            if ($quantity) {
                $product->quantity = $quantity;
            }
            if ($price) {
                $product->price = $price;
            }
            if ($image) {
                $product->price = $price;
            }
            if ($status) {
                $product->status = $status;
            }
            $product->save();

            return $this->sendResponse($product->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return $this->sendError('Product not found!', [], Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            $product->delete();

            DB::commit();

            return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload(Request $request)
    {
        if ($request->has('image')) {
            $file = $request->file('image');

            $filename = 'P' . time() . '.' . $file->getClientOriginalExtension();

            $path = 'product/';

            Storage::putFileAs($path, $file, $filename);

            return $path . $filename;
        }
    }

    public function getAllProductsForCategory($categoryId)
    {
        $products = Product::where('category_id', $categoryId)
            ->orWhereHas('category', function ($query) use ($categoryId) {
                $query->where('parent_id', $categoryId)
                    ->orWhereHas('parent', function ($query) use ($categoryId) {
                        $query->where('parent_id', $categoryId);
                    });
            })->get();

//        $categories = [$categoryId];
//
//        $category = Category::find($categoryId);
//
//        if (count($category->childs) > 0) {
//            foreach ($category->childs as $subCategory) {
//                $categories[] = $subCategory->id;
//
//                if (count($subCategory->childs) > 0) {
//                    foreach ($subCategory->childs as $subSubCategory) {
//                        $categories[] = $subSubCategory->id;
//                    }
//                }
//            }
//        }
//
//        $products = Product::whereIn('category_id', $categories)->get();

        return $products->toArray();
    }
}
