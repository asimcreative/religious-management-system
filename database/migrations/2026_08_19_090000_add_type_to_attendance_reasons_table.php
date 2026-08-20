<?php

use App\Enums\AttendanceReasonType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the shared attendance_reasons list into Salah/Jamaat and Quran scopes.
 *
 * Every existing row already has real history attached — salah_attendance,
 * jamaat_taleem, quran_attendance and quran_teacher_attendance all carry FKs
 * into this table — so nothing here may delete a row or change an existing
 * row's id. Instead:
 *
 *   - Every currently ACTIVE row is tagged 'salah' and cloned into a new
 *     'quran' row (same name/colour/icon/rules). Today's code offers every
 *     active reason to both attendance sheets equally, so tagging one side
 *     only would silently remove an option the other side already relies on.
 *   - Every currently soft-deleted row is tagged once, by which module's
 *     history actually references it (Quran-only usage -> 'quran',
 *     otherwise -> 'salah'). Dead rows are invisible either way, so cloning
 *     them would only add clutter.
 *
 * Written with the query builder, not the model or a service, for the same
 * reason as 2026_08_11_100000_...: a migration is a fixed record of what
 * happened at this point in the schema's history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_reasons', function (Blueprint $table) {
            $table->string('type', 10)->nullable()->after('company_id');
        });

        DB::transaction(function (): void {
            $this->tagActiveRowsAndCloneForQuran();
            $this->tagSoftDeletedRows();
        });

        Schema::table('attendance_reasons', function (Blueprint $table) {
            $table->string('type', 10)->nullable(false)->change();
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        // Attendance reasons are company data the moment they exist, and by
        // the time anyone rolls back an admin may have already edited or
        // recorded attendance against a cloned row. There is no safe way to
        // tell those apart from the untouched clone, so a rollback only drops
        // the column and leaves every row (original and cloned) in place —
        // matching 2026_08_11_100000_...'s own rollback reasoning.
        Schema::table('attendance_reasons', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    private function tagActiveRowsAndCloneForQuran(): void
    {
        $active = DB::table('attendance_reasons')->whereNull('deleted_at')->get();

        if ($active->isEmpty()) {
            return;
        }

        DB::table('attendance_reasons')
            ->whereIn('id', $active->pluck('id'))
            ->update(['type' => AttendanceReasonType::Salah->value]);

        $now = now();

        DB::table('attendance_reasons')->insert(
            $active->map(fn ($row): array => [
                'company_id' => $row->company_id,
                'type' => AttendanceReasonType::Quran->value,
                'reason_name' => $row->reason_name,
                'color' => $row->color,
                'icon' => $row->icon,
                'counts_as_absent' => $row->counts_as_absent,
                'counts_as_leave' => $row->counts_as_leave,
                'status' => $row->status,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    private function tagSoftDeletedRows(): void
    {
        $trashed = DB::table('attendance_reasons')->whereNotNull('deleted_at')->get();

        foreach ($trashed as $row) {
            $type = $this->referencedOnlyByQuran((int) $row->id)
                ? AttendanceReasonType::Quran
                : AttendanceReasonType::Salah;

            DB::table('attendance_reasons')->where('id', $row->id)->update(['type' => $type->value]);
        }
    }

    /**
     * Belt and braces on jamaat_taleem: by the time this migration runs it
     * always exists (2026_08_18_130000_create_jamaat_taleem_table.php sorts
     * earlier), but a missing-table guard costs nothing and this method has
     * no other reason to assume deployment order held.
     */
    private function referencedOnlyByQuran(int $reasonId): bool
    {
        $byQuran = DB::table('quran_attendance')->where('attendance_reason_id', $reasonId)->exists()
            || DB::table('quran_teacher_attendance')->where('attendance_reason_id', $reasonId)->exists();

        $bySalah = DB::table('salah_attendance')->where('attendance_reason_id', $reasonId)->exists()
            || (Schema::hasTable('jamaat_taleem')
                && DB::table('jamaat_taleem')->where('attendance_reason_id', $reasonId)->exists());

        return $byQuran && ! $bySalah;
    }
};
