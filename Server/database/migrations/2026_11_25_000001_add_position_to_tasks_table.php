<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->integer('position')->default(0)->index();
        });

        $position = 0;

        foreach (DB::table('tasks')->orderByDesc('created_at')->orderByDesc('id')->cursor() as $task) {
            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['position' => $position++]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('position');
        });
    }
};
