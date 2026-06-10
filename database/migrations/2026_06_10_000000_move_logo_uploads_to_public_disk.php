<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['partners', 'verenigingen'] as $table) {
            DB::table($table)
                ->whereNotNull('logo')
                ->orderBy('id')
                ->chunkById(100, function ($records): void {
                    foreach ($records as $record) {
                        $logo = $record->logo;

                        if (! $logo || $this->isAlreadyPublicUrl($logo)) {
                            continue;
                        }

                        if (
                            Storage::disk('public')->exists($logo)
                            || ! Storage::disk('local')->exists($logo)
                        ) {
                            continue;
                        }

                        Storage::disk('public')->put(
                            $logo,
                            Storage::disk('local')->get($logo),
                            ['visibility' => 'public'],
                        );
                    }
                });
        }
    }

    public function down(): void
    {
        //
    }

    private function isAlreadyPublicUrl(string $logo): bool
    {
        return str_starts_with($logo, 'http://')
            || str_starts_with($logo, 'https://')
            || str_starts_with($logo, '/');
    }
};
