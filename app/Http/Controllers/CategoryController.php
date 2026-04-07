<?php

namespace App\Http\Controllers;

use App\Actions\Category\CreateCategory;
use App\Actions\Category\DeleteCategory;
use App\Actions\Category\UpdateCategory;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::forUser(auth()->id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('pages.category.index', compact('categories'));
    }

    public function store(StoreCategoryRequest $request, CreateCategory $action): RedirectResponse
    {
        $action->execute(auth()->user(), $request->validated());

        return redirect()->route('category.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategory $action): RedirectResponse
    {
        $this->authorizeOwner($category);

        $action->execute($category, $request->validated());

        return redirect()->route('category.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category, DeleteCategory $action): RedirectResponse
    {
        $this->authorizeOwner($category);

        $action->execute($category);

        return redirect()->route('category.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    private function authorizeOwner(Category $category): void
    {
        abort_unless($category->user_id === auth()->id(), 403);
    }
}
