<?php

namespace Tests\Feature\Public;

use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InnovationIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_public_innovation_page(): void
    {
        $this->get(route('inovasi'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Innovations/Index'));
    }

    public function test_only_published_innovations_are_listed(): void
    {
        InnovationModel::factory()->create(['title' => 'Inovasi Tayang', 'status' => 'published']);
        InnovationModel::factory()->create(['title' => 'Inovasi Draf', 'status' => 'draft']);

        $this->get(route('inovasi'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Inovasi Tayang'));
    }

    public function test_description_is_truncated_to_fifty_words(): void
    {
        $longDescription = implode(' ', array_fill(0, 80, 'kata'));

        InnovationModel::factory()->create([
            'status' => 'published',
            'description' => $longDescription,
        ]);

        $response = $this->get(route('inovasi'));
        $shown = $response->viewData('page')['props']['innovations']['data'][0]['description'];

        $this->assertSame(Str::words($longDescription, 50), $shown);
        $this->assertStringEndsWith('...', $shown);
    }

    public function test_cover_url_is_included_when_present(): void
    {
        Storage::fake('public');

        $innovation = InnovationModel::factory()->create(['status' => 'published']);
        $innovation->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

        $response = $this->get(route('inovasi'));
        $cover = $response->viewData('page')['props']['innovations']['data'][0]['cover'];

        $this->assertNotNull($cover);
        $this->assertStringContainsString('/storage/', $cover);
        $this->assertStringContainsString('thumb', $cover);
    }

    public function test_cover_is_null_when_no_image_was_uploaded(): void
    {
        InnovationModel::factory()->create(['status' => 'published']);

        $response = $this->get(route('inovasi'));
        $cover = $response->viewData('page')['props']['innovations']['data'][0]['cover'];

        $this->assertNull($cover);
    }

    public function test_innovations_can_be_filtered_by_title(): void
    {
        InnovationModel::factory()->create(['title' => 'Aplikasi Posyandu', 'status' => 'published']);
        InnovationModel::factory()->create(['title' => 'Sistem Irigasi', 'status' => 'published']);

        $this->get(route('inovasi', ['search' => 'Posyandu']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Aplikasi Posyandu'));
    }

    public function test_innovations_can_be_filtered_by_year(): void
    {
        InnovationModel::factory()->create(['title' => 'Inovasi 2022', 'status' => 'published', 'tahun' => 2022]);
        InnovationModel::factory()->create(['title' => 'Inovasi 2023', 'status' => 'published', 'tahun' => 2023]);

        $this->get(route('inovasi', ['tahun' => 2023]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Inovasi 2023'));
    }

    public function test_innovations_can_be_filtered_by_organisasi(): void
    {
        $organisasiA = OrganisasiModel::factory()->create(['name' => 'Dinas A']);
        $organisasiB = OrganisasiModel::factory()->create(['name' => 'Dinas B']);
        InnovationModel::factory()->create([
            'title' => 'Inovasi Dinas A',
            'status' => 'published',
            'organisasi_id' => $organisasiA->id,
        ]);
        InnovationModel::factory()->create([
            'title' => 'Inovasi Dinas B',
            'status' => 'published',
            'organisasi_id' => $organisasiB->id,
        ]);

        $this->get(route('inovasi', ['organisasi_id' => $organisasiA->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('innovations.data', 1)
                ->where('innovations.data.0.title', 'Inovasi Dinas A'));
    }

    public function test_filter_options_only_include_published_years_and_organisasis(): void
    {
        $organisasiPublished = OrganisasiModel::factory()->create(['name' => 'Dinas Tayang']);
        $organisasiDraft = OrganisasiModel::factory()->create(['name' => 'Dinas Draf']);

        InnovationModel::factory()->create([
            'status' => 'published',
            'tahun' => 2023,
            'organisasi_id' => $organisasiPublished->id,
        ]);
        InnovationModel::factory()->create([
            'status' => 'draft',
            'tahun' => 2020,
            'organisasi_id' => $organisasiDraft->id,
        ]);

        $this->get(route('inovasi'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filterOptions.years', [2023])
                ->has('filterOptions.organisasis', 1)
                ->where('filterOptions.organisasis.0.name', 'Dinas Tayang'));
    }
}
