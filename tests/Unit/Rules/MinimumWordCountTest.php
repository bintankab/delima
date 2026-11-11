<?php

namespace Tests\Unit\Rules;

use App\Rules\MinimumWordCount;
use PHPUnit\Framework\TestCase;

class MinimumWordCountTest extends TestCase
{
    public function test_html_shorter_than_200_words_is_rejected(): void
    {
        $message = $this->messageFor('<p>'.implode('</p><p>', array_fill(0, 199, 'kata')).'</p>');

        $this->assertSame('Deskripsi minimal 200 kata.', $message);
    }

    public function test_empty_editor_markup_is_rejected(): void
    {
        $message = $this->messageFor('<p><br></p>');

        $this->assertSame('Deskripsi minimal 200 kata.', $message);
    }

    public function test_html_with_200_words_passes(): void
    {
        $message = $this->messageFor('<p>'.implode('</p><p>', array_fill(0, 200, 'kata')).'</p>');

        $this->assertNull($message);
    }

    private function messageFor(string $value): ?string
    {
        $message = null;

        (new MinimumWordCount(200))->validate('description', $value, function (string $failure) use (&$message): void {
            $message = $failure;
        });

        return $message;
    }
}
