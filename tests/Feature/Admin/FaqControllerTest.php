<?php

namespace Tests\Feature\Admin;

use App\Models\FaqModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FaqControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_old_placeholder_address_redirects_to_the_faq_list(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->get('/admin/faqs/index')
            ->assertRedirect('/admin/faqs');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.faqs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_opd_cannot_open_the_faq_list(): void
    {
        $this->actingAs($this->userWithRole('admin-opd'))
            ->get(route('admin.faqs.index'))
            ->assertForbidden();
    }

    public function test_super_admin_sees_faqs_matching_an_answer(): void
    {
        FaqModel::factory()->create([
            'question' => 'Cara mengajukan inovasi',
            'answer' => 'Isi formulir di menu inovasi.',
            'category' => 'Inovasi',
            'order' => 1,
        ]);
        FaqModel::factory()->create([
            'question' => 'Jam layanan',
            'answer' => 'Setiap hari kerja.',
            'category' => 'Umum',
            'order' => 2,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.faqs.index', ['search' => 'formulir']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Faqs/A-Faqs-Index')
                ->has('faqs.data', 1)
                ->where('faqs.data.0.question', 'Cara mengajukan inovasi'));
    }

    public function test_search_treats_percent_as_a_literal(): void
    {
        FaqModel::factory()->create(['question' => 'Progres 100%']);
        FaqModel::factory()->create(['question' => 'Jam layanan']);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.faqs.index', ['search' => '%']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('faqs.data', 1)
                ->where('faqs.data.0.question', 'Progres 100%'));
    }

    public function test_unknown_sort_column_keeps_the_default_order(): void
    {
        FaqModel::factory()->create(['question' => 'Kedua', 'order' => 2]);
        FaqModel::factory()->create(['question' => 'Pertama', 'order' => 1]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->get(route('admin.faqs.index', ['sort' => 'id', 'direction' => 'desc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sort', null)
                ->where('faqs.data.0.question', 'Pertama')
                ->where('faqs.data.1.question', 'Kedua'));
    }

    public function test_super_admin_creates_a_faq(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->post(route('admin.faqs.store'), [
                'question' => 'Bagaimana cara mendaftar?',
                'answer' => 'Buat akun terlebih dahulu.',
                'category' => 'Akun',
                'order' => 3,
            ])
            ->assertRedirect(route('admin.faqs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faqs', [
            'question' => 'Bagaimana cara mendaftar?',
            'answer' => 'Buat akun terlebih dahulu.',
            'category' => 'Akun',
            'order' => 3,
        ]);
    }

    public function test_empty_faq_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.faqs.create'))
            ->post(route('admin.faqs.store'), [])
            ->assertRedirect(route('admin.faqs.create'))
            ->assertSessionHasErrors([
                'question' => 'Pertanyaan wajib diisi.',
                'answer' => 'Jawaban wajib diisi.',
                'order' => 'Urutan wajib diisi.',
            ]);
    }

    public function test_negative_order_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.faqs.create'))
            ->post(route('admin.faqs.store'), [
                'question' => 'Pertanyaan',
                'answer' => 'Jawaban',
                'order' => -1,
            ])
            ->assertRedirect(route('admin.faqs.create'))
            ->assertSessionHasErrors([
                'order' => 'Urutan tidak boleh kurang dari 0.',
            ]);
    }

    public function test_question_longer_than_255_characters_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.faqs.create'))
            ->post(route('admin.faqs.store'), [
                'question' => str_repeat('a', 256),
                'answer' => 'Jawaban',
                'order' => 0,
            ])
            ->assertRedirect(route('admin.faqs.create'))
            ->assertSessionHasErrors([
                'question' => 'Pertanyaan terlalu panjang.',
            ]);
    }

    public function test_answer_longer_than_10000_characters_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.faqs.create'))
            ->post(route('admin.faqs.store'), [
                'question' => 'Pertanyaan',
                'answer' => str_repeat('a', 10001),
                'order' => 0,
            ])
            ->assertRedirect(route('admin.faqs.create'))
            ->assertSessionHasErrors([
                'answer' => 'Jawaban terlalu panjang.',
            ]);
    }

    public function test_category_longer_than_255_characters_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.faqs.create'))
            ->post(route('admin.faqs.store'), [
                'question' => 'Pertanyaan',
                'answer' => 'Jawaban',
                'category' => str_repeat('a', 256),
                'order' => 0,
            ])
            ->assertRedirect(route('admin.faqs.create'))
            ->assertSessionHasErrors([
                'category' => 'Kategori terlalu panjang.',
            ]);
    }

    public function test_non_numeric_order_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('super-admin'))
            ->from(route('admin.faqs.create'))
            ->post(route('admin.faqs.store'), [
                'question' => 'Pertanyaan',
                'answer' => 'Jawaban',
                'order' => 'bukan-angka',
            ])
            ->assertRedirect(route('admin.faqs.create'))
            ->assertSessionHasErrors([
                'order' => 'Urutan harus berupa angka.',
            ]);
    }

    public function test_super_admin_updates_a_faq_and_clears_its_category(): void
    {
        $faq = FaqModel::factory()->create([
            'question' => 'Pertanyaan lama',
            'answer' => 'Jawaban lama',
            'category' => 'Akun',
            'order' => 1,
        ]);

        $this->actingAs($this->userWithRole('super-admin'))
            ->put(route('admin.faqs.update', $faq), [
                'question' => 'Pertanyaan baru',
                'answer' => 'Jawaban baru',
                'category' => '',
                'order' => 4,
            ])
            ->assertRedirect(route('admin.faqs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'Pertanyaan baru',
            'answer' => 'Jawaban baru',
            'category' => null,
            'order' => 4,
        ]);
    }

    public function test_super_admin_deletes_a_faq(): void
    {
        $faq = FaqModel::factory()->create();

        $this->actingAs($this->userWithRole('super-admin'))
            ->delete(route('admin.faqs.destroy', $faq))
            ->assertRedirect(route('admin.faqs.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($faq);
    }

    private function userWithRole(string $role): UserModel
    {
        $user = UserModel::factory()->create();
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
