<?php

use App\DataTransfer\Definitions\BranchDefinition;
use App\DataTransfer\Definitions\CompanyDefinition;
use App\DataTransfer\Definitions\DepartmentDefinition;
use App\DataTransfer\Definitions\DesignationDefinition;
use App\DataTransfer\Definitions\EmployeeDefinition;
use App\DataTransfer\Definitions\JamaatDefinition;
use App\DataTransfer\Definitions\JamaatMemberDefinition;
use App\DataTransfer\Definitions\JamaatTaleemDefinition;
use App\DataTransfer\Definitions\LanguageDefinition;
use App\DataTransfer\Definitions\NotificationDefinition;
use App\DataTransfer\Definitions\QuranAttendanceDefinition;
use App\DataTransfer\Definitions\QuranAttendanceReasonDefinition;
use App\DataTransfer\Definitions\QuranClassDefinition;
use App\DataTransfer\Definitions\QuranClassMemberDefinition;
use App\DataTransfer\Definitions\QuranDepartmentDefinition;
use App\DataTransfer\Definitions\QuranProgressDefinition;
use App\DataTransfer\Definitions\QuranStatusDefinition;
use App\DataTransfer\Definitions\QuranTeacherAttendanceDefinition;
use App\DataTransfer\Definitions\SalahAttendanceDefinition;
use App\DataTransfer\Definitions\SalahAttendanceReasonDefinition;
use App\DataTransfer\Definitions\TaleemAttendanceReasonDefinition;
use App\DataTransfer\Definitions\TeacherDefinition;
use App\DataTransfer\Definitions\UserDefinition;

return [

    /*
    |--------------------------------------------------------------------------
    | Registered Resources
    |--------------------------------------------------------------------------
    |
    | Every module the import/export engine serves. Adding a module means
    | writing one definition class and adding one line here — no controller,
    | no import class and no export class per module.
    |
    | Keys must be unique; the registry refuses to boot on a collision.
    |
    */

    'resources' => [
        // People
        UserDefinition::class,
        EmployeeDefinition::class,
        TeacherDefinition::class,

        // Quran
        QuranClassDefinition::class,
        QuranClassMemberDefinition::class,
        QuranAttendanceDefinition::class,
        QuranProgressDefinition::class,

        // Salah
        JamaatDefinition::class,
        JamaatMemberDefinition::class,
        SalahAttendanceDefinition::class,

        // Master data
        BranchDefinition::class,
        DepartmentDefinition::class,
        DesignationDefinition::class,
        SalahAttendanceReasonDefinition::class,
        QuranAttendanceReasonDefinition::class,
        TaleemAttendanceReasonDefinition::class,
        QuranDepartmentDefinition::class,
        QuranStatusDefinition::class,
        LanguageDefinition::class,

        // Export only — records that must not be created from a file.
        NotificationDefinition::class,
        CompanyDefinition::class,
        QuranTeacherAttendanceDefinition::class,
        JamaatTaleemDefinition::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage
    |--------------------------------------------------------------------------
    |
    | Uploads, generated exports and error reports all live on a private disk,
    | partitioned per company, and are only ever served through a controller
    | that runs a policy check first. They must never reach the public disk.
    |
    */

    'disk' => env('DATA_TRANSFER_DISK', 'local'),

    'path' => 'data-transfer',

    'allowed_extensions' => ['xlsx', 'xls', 'csv'],

    'allowed_mimes' => [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
        'text/plain',
        'application/csv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [

        // Largest upload accepted, in kilobytes.
        'max_upload_kb' => (int) env('DATA_TRANSFER_MAX_UPLOAD_KB', 10240),

        // Hard ceiling on rows read from one file, as a denial-of-service guard.
        'max_rows' => (int) env('DATA_TRANSFER_MAX_ROWS', 100000),

        // At or below this row count an import runs inside the request.
        // Above it the work is queued and the user watches progress.
        'sync_import_rows' => (int) env('DATA_TRANSFER_SYNC_IMPORT_ROWS', 2000),

        // Same idea for exports.
        'sync_export_rows' => (int) env('DATA_TRANSFER_SYNC_EXPORT_ROWS', 5000),

        // dompdf cannot render an unbounded table. Exports beyond this are
        // truncated with an explicit on-screen notice rather than silently.
        'pdf_max_rows' => (int) env('DATA_TRANSFER_PDF_MAX_ROWS', 5000),

        // Rows read into memory at a time, and rows written per insert batch.
        'read_chunk' => 500,
        'write_batch' => 500,

        // Valid rows shown on the preview screen before importing.
        'preview_rows' => 50,

        // Errors kept in the log record for on-screen display. The complete
        // set always goes to the downloadable error file.
        'stored_errors' => 100,

        // Failed rows written into the downloadable error report. Bounded
        // because each one holds its original cells in memory until the file
        // is written; a truncated report says so on screen.
        'error_file_rows' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queues
    |--------------------------------------------------------------------------
    |
    | Heavy transfers get their own queues so a 50,000-row import cannot sit in
    | front of a password-reset email. See docs/PERFORMANCE_REVIEW.md.
    |
    */

    'queues' => [
        'imports' => env('DATA_TRANSFER_IMPORT_QUEUE', 'imports'),
        'exports' => env('DATA_TRANSFER_EXPORT_QUEUE', 'exports'),
    ],

    'job' => [
        'import_tries' => 2,
        'import_timeout' => 600,
        'export_tries' => 2,
        'export_timeout' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Generated files are pruned after this many days. Log rows are kept: they
    | are the audit trail of who moved data in and out of the tenant.
    |
    */

    'retention_days' => (int) env('DATA_TRANSFER_RETENTION_DAYS', 90),

];
