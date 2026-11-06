<?php

namespace Tests\Feature\Public;

use App\Models\FaqModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FaqIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_faqs_grouped_by_category_and_order(): void
    {
        FaqModel::factory()->create([
            'question' => 'Bagaimana cara mendaftar?',
            'answer' => 'Buat akun terlebih dahulu.',
            'category' => 'Akun',
            'order' => 2,
        ]);
        FaqModel::factory()->create([
            'question' => 'Apa itu Delima?',
            'answer' => 'Pusat inovasi daerah.',
            'category' => null,
            'order' => 1,
        ]);
        FaqModel::factory()->create([
            'question' => 'Siapa yang bisa masuk?',
            'answer' => 'ASN dan publik.',
            'category' => 'Akun',
            'order' => 3,
        ]);

        $this->get(route('faq'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Faqs/Index')
                ->has('groups', 2)
                ->where('groups.0.category', null)
                ->where('groups.0.items.0.question', 'Apa itu Delima?')
                ->where('groups.0.items.0.answer', 'Pusat inovasi daerah.')
                ->where('groups.1.category', 'Akun')
                ->where('groups.1.items.0.question', 'Bagaimana cara mendaftar?')
                ->where('groups.1.items.1.question', 'Siapa yang bisa masuk?'));
    }
}
