<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $renames = [
        'App\\Models\\Announcement' => 'App\\Models\\AnnouncementModel',
        'App\\Models\\Competition' => 'App\\Models\\CompetitionModel',
        'App\\Models\\CompetitionSubmission' => 'App\\Models\\CompetitionSubmissionModel',
        'App\\Models\\Consultation' => 'App\\Models\\ConsultationModel',
        'App\\Models\\ConsultationReply' => 'App\\Models\\ConsultationReplyModel',
        'App\\Models\\Event' => 'App\\Models\\EventModel',
        'App\\Models\\Faq' => 'App\\Models\\FaqModel',
        'App\\Models\\Idea' => 'App\\Models\\IdeaModel',
        'App\\Models\\Innovation' => 'App\\Models\\InnovationModel',
        'App\\Models\\InnovationCategory' => 'App\\Models\\InnovationCategoryModel',
        'App\\Models\\LibraryDocument' => 'App\\Models\\LibraryDocumentModel',
        'App\\Models\\News' => 'App\\Models\\NewsModel',
        'App\\Models\\Organisasi' => 'App\\Models\\OrganisasiModel',
        'App\\Models\\Review' => 'App\\Models\\ReviewModel',
        'App\\Models\\Riset' => 'App\\Models\\RisetModel',
        'App\\Models\\User' => 'App\\Models\\UserModel',
    ];

    public function up(): void
    {
        $this->rewriteMorphTypes($this->renames);
    }

    public function down(): void
    {
        $this->rewriteMorphTypes(array_flip($this->renames));
    }

    /**
     * @param  array<string, string>  $renames
     */
    private function rewriteMorphTypes(array $renames): void
    {
        $columns = [
            'media' => ['model_type'],
            'activity_log' => ['subject_type', 'causer_type'],
            'reviews' => ['reviewable_type'],
            config('permission.table_names.model_has_roles') => ['model_type'],
            config('permission.table_names.model_has_permissions') => ['model_type'],
        ];

        foreach ($columns as $table => $fields) {
            foreach ($fields as $field) {
                foreach ($renames as $from => $to) {
                    DB::table($table)->where($field, $from)->update([$field => $to]);
                }
            }
        }
    }
};
