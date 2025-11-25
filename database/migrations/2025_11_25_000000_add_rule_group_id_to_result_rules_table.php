<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answer_templates', function (Blueprint $table) {
            $table->uuid('rule_group_id')
                ->nullable()
                ->after('form_id')
                ->index();
        });

        Schema::table('result_rules', function (Blueprint $table) {
            $table->uuid('rule_group_id')
                ->nullable()
                ->after('form_id')
                ->index();
        });

        Schema::table('result_rule_texts', function (Blueprint $table) {
            $table->uuid('rule_group_id')
                ->nullable()
                ->after('result_rule_id')
                ->index();
        });

        // Backfill existing answer templates with unique rule groups
        DB::table('answer_templates')
            ->orderBy('id')
            ->chunkById(200, function ($templates) {
                foreach ($templates as $template) {
                    DB::table('answer_templates')
                        ->where('id', $template->id)
                        ->update([
                            'rule_group_id' => $template->rule_group_id ?? (string) Str::uuid(),
                        ]);
                }
            });

        // Backfill result rules with unique group ids
        DB::table('result_rules')
            ->orderBy('id')
            ->chunkById(200, function ($rules) {
                foreach ($rules as $rule) {
                    DB::table('result_rules')
                        ->where('id', $rule->id)
                        ->update([
                            'rule_group_id' => $rule->rule_group_id ?? (string) Str::uuid(),
                        ]);
                }
            });

        // Ensure result rule texts inherit their parent's group id
        DB::table('result_rule_texts as texts')
            ->leftJoin('result_rules as rules', 'texts.result_rule_id', '=', 'rules.id')
            ->select('texts.id', 'rules.rule_group_id')
            ->orderBy('texts.id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('result_rule_texts')
                        ->where('id', $row->id)
                        ->update([
                            'rule_group_id' => $row->rule_group_id ?? (string) Str::uuid(),
                        ]);
                }
            }, 'texts.id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('result_rule_texts', function (Blueprint $table) {
            $table->dropColumn('rule_group_id');
        });

        Schema::table('result_rules', function (Blueprint $table) {
            $table->dropColumn('rule_group_id');
        });

        Schema::table('answer_templates', function (Blueprint $table) {
            $table->dropColumn('rule_group_id');
        });
    }
};

