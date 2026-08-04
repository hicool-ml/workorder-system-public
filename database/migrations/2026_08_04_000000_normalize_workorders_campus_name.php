<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalize the workorders.campus text column so it stores the readable
 * campus name (e.g. "新校区") instead of the legacy code (e.g. "new_campus").
 *
 * Earlier data only set campus_id via the foreign key; the denormalized
 * campus string was left holding the old code value, which surfaced as
 * "new_campus" in workorder displays and broke keyword searches by name.
 *
 * This migration re-syncs campus text from the campuses table via campus_id,
 * so the string column and the relation always agree regardless of history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $campuses = DB::table('campuses')->pluck('name', 'id');
            foreach ($campuses as $id => $name) {
                DB::table('workorders')
                    ->where('campus_id', $id)
                    ->update(['campus' => $name]);
            }
        } else {
            DB::statement(
                'UPDATE workorders w ' .
                'JOIN campuses c ON w.campus_id = c.id ' .
                'SET w.campus = c.name ' .
                'WHERE w.campus_id IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        // Non-destructive rollback: the campus_id relation remains the
        // source of truth, so restoring the old codes is unnecessary.
    }
};