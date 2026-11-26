<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('title');
        });

        $usedSlugs = [];
        DB::table('tasks')->orderBy('id')->chunkById(500, function ($tasks) use (&$usedSlugs): void {
            foreach ($tasks as $task) {
                $baseSlug = substr(Str::slug($task->title) ?: 'task', 0, 240);
                $slug = $baseSlug;
                $suffix = 2;

                while (isset($usedSlugs[$slug])) {
                    $suffixText = '-'.$suffix++;
                    $slug = substr($baseSlug, 0, 255 - strlen($suffixText)).$suffixText;
                }

                $usedSlugs[$slug] = true;
                DB::table('tasks')->where('id', $task->id)->update(['slug' => $slug]);
            }
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
