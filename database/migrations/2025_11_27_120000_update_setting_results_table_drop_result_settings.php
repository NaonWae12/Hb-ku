<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('setting_results', function (Blueprint $table) {
            if (!Schema::hasColumn('setting_results', 'card_image')) {
                $table->string('card_image')->nullable()->after('image');
            }

            if (!Schema::hasColumn('setting_results', 'card_order')) {
                $table->integer('card_order')->default(0)->after('order');
            }
        });

        if (Schema::hasTable('result_settings')) {
            $ruleGroupByRuleId = DB::table('result_rules')
                ->pluck('rule_group_id', 'id');

            DB::table('result_settings')
                ->orderBy('id')
                ->chunkById(100, function ($resultSettings) use ($ruleGroupByRuleId) {
                    foreach ($resultSettings as $setting) {
                        $ruleGroupId = $setting->rule_group_id
                            ?? ($ruleGroupByRuleId[$setting->result_rule_id] ?? null);

                        if (!$ruleGroupId) {
                            continue;
                        }

                        DB::table('setting_results')
                            ->where('form_id', $setting->form_id)
                            ->where('rule_group_id', $ruleGroupId)
                            ->update([
                                'card_order' => $setting->order ?? 0,
                                'card_image' => $setting->image,
                                'image_alignment' => $setting->image_alignment ?? DB::raw('image_alignment'),
                                'text_alignment' => $setting->text_alignment ?? DB::raw('text_alignment'),
                            ]);
                    }
                });

            Schema::dropIfExists('result_settings');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('result_settings')) {
            Schema::create('result_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('form_id')->constrained()->onDelete('cascade');
                $table->foreignId('result_rule_id')->nullable()->constrained('result_rules')->onDelete('cascade');
                $table->string('title')->nullable();
                $table->string('image')->nullable();
                $table->string('image_alignment')->default('center');
                $table->text('result_text')->nullable();
                $table->string('text_alignment')->default('center');
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('setting_results', function (Blueprint $table) {
            if (Schema::hasColumn('setting_results', 'card_order')) {
                $table->dropColumn('card_order');
            }
            if (Schema::hasColumn('setting_results', 'card_image')) {
                $table->dropColumn('card_image');
            }
        });
    }
};

