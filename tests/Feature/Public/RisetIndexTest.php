<?php

namespace Tests\Feature\Public;

use App\Models\OrganisasiModel;
use App\Models\RisetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RisetIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_public_riset_page(): void
    {
        $this->get(route('riset'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Riset/Index'));
    }

    public function test_only_published_risets_are_listed(): void
    {
        RisetModel::factory()->create(['title' => 'Riset Tayang', 'status' => 'published']);
        RisetModel::factory()->create(['title' => 'Riset Draf', 'status' => 'draft']);

        $this->get(route('riset'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Riset Tayang'));
    }

    public function test_abstract_is_truncated_to_fifty_words(): void
    {
        $longAbstract = implode(' ', array_fill(0, 80, 'kata'));

        RisetModel::factory()->create([
            'status' => 'published',
            'abstrak' => $longAbstract,
        ]);

        $response = $this->get(route('riset'));
        $shown = $response->viewData('page')['props']['risets']['data'][0]['abstrak'];

        $this->assertSame(Str::words($longAbstract, 50), $shown);
        $this->assertStringEndsWith('...', $shown);
    }

    public function test_cover_url_is_included_when_present(): void
    {
        Storage::fake('public');

        $riset = RisetModel::factory()->create(['status' => 'published']);
        $riset->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover');

        $response = $this->get(route('riset'));
        $cover = $response->viewData('page')['props']['risets']['data'][0]['cover'];

        $this->assertNotNull($cover);
        $this->assertStringContainsString('/storage/', $cover);
        $this->assertStringContainsString('thumb', $cover);
    }

    public function test_cover_is_null_when_no_image_was_uploaded(): void
    {
        RisetModel::factory()->create(['status' => 'published']);

        $response = $this->get(route('riset'));
        $cover = $response->viewData('page')['props']['risets']['data'][0]['cover'];

        $this->assertNull($cover);
    }

    public function test_risets_can_be_filtered_by_title(): void
    {
        RisetModel::factory()->create(['title' => 'Kajian Pesisir', 'status' => 'published']);
        RisetModel::factory()->create(['title' => 'Kajian Pendidikan', 'status' => 'published']);

        $this->get(route('riset', ['search' => 'Pesisir']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Kajian Pesisir'));
    }

    public function test_risets_can_be_filtered_by_year(): void
    {
        RisetModel::factory()->create(['title' => 'Riset 2022', 'status' => 'published', 'tahun' => 2022]);
        RisetModel::factory()->create(['title' => 'Riset 2023', 'status' => 'published', 'tahun' => 2023]);

        $this->get(route('riset', ['tahun' => 2023]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Riset 2023'));
    }

    public function test_risets_can_be_filtered_by_organisasi(): void
    {
        $organisasiA = OrganisasiModel::factory()->create(['name' => 'Dinas A']);
        $organisasiB = OrganisasiModel::factory()->create(['name' => 'Dinas B']);
        RisetModel::factory()->create([
            'title' => 'Riset Dinas A',
            'status' => 'published',
            'organisasi_id' => $organisasiA->id,
        ]);
        RisetModel::factory()->create([
            'title' => 'Riset Dinas B',
            'status' => 'published',
            'organisasi_id' => $organisasiB->id,
        ]);

        $this->get(route('riset', ['organisasi_id' => $organisasiA->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('risets.data', 1)
                ->where('risets.data.0.title', 'Riset Dinas A'));
    }

    public function test_filter_options_only_include_published_years_and_organisasis(): void
    {
        $organisasiPublished = OrganisasiModel::factory()->create(['name' => 'Dinas Tayang']);
        $organisasiDraft = OrganisasiModel::factory()->create(['name' => 'Dinas Draf']);

        RisetModel::factory()->create([
            'status' => 'published',
            'tahun' => 2023,
            'organisasi_id' => $organisasiPublished->id,
        ]);
        RisetModel::factory()->create([
            'status' => 'draft',
            'tahun' => 2020,
            'organisasi_id' => $organisasiDraft->id,
        ]);

        $this->get(route('riset'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filterOptions.years', [2023])
                ->has('filterOptions.organisasis', 1)
                ->where('filterOptions.organisasis.0.name', 'Dinas Tayang'));
    }
}
