<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $items = Category::orderBy('name')->paginate(25);
        return view('admin.categories.index', compact('items'));
    }

    public function create()
    {
        $parents = Category::orderBy('name')->get();
        return view('admin.categories.create', compact('parents'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => ['required','string','max:120'],
            'parent_id' => ['nullable','exists:categories,id'],
        ]);
        Category::create($data);
        return to_route('admin.categories.index')->with('success','Created.');
    }

    public function edit(Category $category)
    {
        $parents = Category::where('id','!=',$category->id)->orderBy('name')->get();
        return view('admin.categories.edit', compact('category','parents'));
    }

    public function update(Request $r, Category $category)
    {
        $data = $r->validate([
            'name' => ['required','string','max:120'],
            'parent_id' => ['nullable','exists:categories,id'],
        ]);
        $category->update($data);
        return back()->with('success','Updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return to_route('admin.categories.index')->with('success','Deleted.');
    }
}
