<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Solutions: the fixed "Pour qui ?" and "Avantages" blocks move into the free description,
 * where editors choose their own headings. Existing text is kept, under its former heading.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('products')->get(['id', 'description', 'audience', 'benefits']) as $row) {
            $extra = '';
            if (trim((string) $row->audience) !== '') {
                $extra .= '<h2>Pour qui ?</h2><p>'.e(trim($row->audience)).'</p>';
            }
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $row->benefits))));
            if ($lines) {
                $extra .= '<h2>Avantages</h2><ul>'.implode('', array_map(fn ($line) => '<li><p>'.e($line).'</p></li>', $lines)).'</ul>';
            }
            if ($extra !== '') {
                DB::table('products')->where('id', $row->id)->update(['description' => (string) $row->description.$extra]);
            }
        }

        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['audience', 'benefits']));
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('benefits')->nullable();
            $table->string('audience')->default('');
        });
    }
};
