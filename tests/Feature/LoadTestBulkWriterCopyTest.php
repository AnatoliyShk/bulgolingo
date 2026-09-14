<?php

namespace Tests\Feature;

use App\Support\LoadTest\BulkWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Pdo\Pgsql;
use Tests\TestCase;

class LoadTestBulkWriterCopyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Round-trips rows through COPY on the real Postgres connection, which the
     * unit tests never reach: null must come back as SQL NULL rather than the
     * letter N an over-escaped null marker leaves behind, and the characters
     * COPY treats structurally must come back unchanged.
     */
    public function test_copy_writes_nulls_booleans_and_escaped_characters_back_as_given(): void
    {
        $this->assertInstanceOf(Pgsql::class, DB::connection()->getPdo());

        DB::statement('create temporary table bulk_writer_copy (id int, word text, flag boolean, note text)');

        $written = new BulkWriter(DB::connection())->write('bulk_writer_copy', ['id', 'word', 'flag', 'note'], [
            [1, "дума\tс\\знак", true, null],
            [2, "ред\nнов\rред", false, 'N'],
        ]);

        $rows = DB::table('bulk_writer_copy')->orderBy('id')->get();

        $this->assertSame(2, $written);
        $this->assertSame("дума\tс\\знак", $rows[0]->word);
        $this->assertTrue($rows[0]->flag);
        $this->assertNull($rows[0]->note);
        $this->assertSame("ред\nнов\rред", $rows[1]->word);
        $this->assertFalse($rows[1]->flag);
        $this->assertSame('N', $rows[1]->note);
    }
}
