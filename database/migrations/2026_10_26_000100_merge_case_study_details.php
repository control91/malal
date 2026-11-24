<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Case studies: the fixed "Problématique / Solution apportée / Résultats" blocks become one free rich text,
 * where editors choose their own headings. Existing text is kept, under its former heading.
 */
return new class extends Migration
{
    private const SECTIONS = ['challenge' => 'Problématique', 'solution' => 'Solution apportée', 'results' => 'Résultats'];

    public function up(): void
    {
        Schema::table('case_studies', fn (Blueprint $table) => $table->longText('body')->nullable()->after('summary'));

        foreach (DB::table('case_studies')->get(['id', 'challenge', 'solution', 'results']) as $row) {
            $body = '';
            foreach (self::SECTIONS as $column => $heading) {
                if (trim(strip_tags((string) $row->{$column}, '<img>')) !== '') {
                    $body .= '<h2>'.$heading.'</h2>'.$row->{$column};
                }
            }
            DB::table('case_studies')->where('id', $row->id)->update(['body' => $body]);
        }

        Schema::table('case_studies', fn (Blueprint $table) => $table->dropColumn(array_keys(self::SECTIONS)));
    }

    public function down(): void
    {
        Schema::table('case_studies', function (Blueprint $table) {
            $table->longText('challenge')->nullable();
            $table->longText('solution')->nullable();
            $table->longText('results')->nullable();
        });
        DB::table('case_studies')->update(['challenge' => DB::raw('body')]);
        Schema::table('case_studies', fn (Blueprint $table) => $table->dropColumn('body'));
    }
};
