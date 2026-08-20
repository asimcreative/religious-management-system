<?php

namespace App\Contracts\Repositories;

use App\Models\QuranClassAdmission;

interface QuranClassAdmissionRepositoryInterface extends BaseRepositoryInterface
{
    public function findByMember(int $quranClassMemberId): ?QuranClassAdmission;

    /**
     * Create the admission record for a membership, or overwrite it if one
     * already exists (re-filling a previously-submitted form is allowed).
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertForMember(int $quranClassMemberId, array $data): QuranClassAdmission;
}
