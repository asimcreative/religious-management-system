<?php

namespace App\Enums;

/**
 * Which attendance module an Attendance Reason belongs to.
 *
 * Salah/Jamaat and Quran keep one shared attendance_reasons table rather than
 * two — every existing row and FK stays intact this way — so this is purely a
 * management-scope discriminator. Historical display of a record's reason
 * never depends on the current value of this column.
 */
enum AttendanceReasonType: string
{
    case Salah = 'salah';
    case Quran = 'quran';
}
