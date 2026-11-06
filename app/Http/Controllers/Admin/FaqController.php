<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\FaqModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, ['question', 'category', 'order']);

        $faqs = FaqModel::query()
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($escaped): void {
                    $query->where('question', 'like', $escaped)
                        ->orWhere('answer', 'like', $escaped)
                        ->orWhere('category', 'like', $escaped);
                });
            })
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->orderBy('order')->orderBy('question'),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (FaqModel $faq): array => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
                'order' => $faq->order,
            ]);

        return Inertia::render('Admin/Faqs/A-Faqs-Index', [
            'meta' => ['title' => 'FAQ'],
            'faqs' => $faqs,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Faqs/A-Faqs-Form', [
            'meta' => ['title' => 'Tambah FAQ'],
            'faq' => null,
        ]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        FaqModel::query()->create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'] ?? null,
            'order' => $validated['order'],
        ]);

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ berhasil ditambahkan.');
    }

    public function edit(FaqModel $faq): Response
    {
        return Inertia::render('Admin/Faqs/A-Faqs-Form', [
            'meta' => ['title' => 'Ubah FAQ'],
            'faq' => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'category' => $faq->category,
                'order' => $faq->order,
            ],
        ]);
    }

    public function update(FaqRequest $request, FaqModel $faq): RedirectResponse
    {
        $validated = $request->validated();

        $faq->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'] ?? null,
            'order' => $validated['order'],
        ]);

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ berhasil diperbarui.');
    }

    public function destroy(FaqModel $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ berhasil dihapus.');
    }
}
