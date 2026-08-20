<?php

namespace App\Repositories;

use App\Contracts\Repositories\QuranClassAdmissionRepositoryInterface;
use App\Models\QuranClassAdmission;

class QuranClassAdmissionRepository extends BaseRepository implements QuranClassAdmissionRepositoryInterface
{
    public function __construct(QuranClassAdmission $model)
    {
        parent::__construct($model);
    }

    public function findByMember(int $quranClassMemberId): ?QuranClassAdmission
    {
        /** @var QuranClassAdmission|null */
        return $this->model->newQuery()
            ->where('quran_class_member_id', $quranClassMemberId)
            ->first();
    }

    public function upsertForMember(int $quranClassMemberId, array $data): QuranClassAdmission
    {
        /** @var QuranClassAdmission */
        return $this->model->newQuery()->updateOrCreate(
            ['quran_class_member_id' => $quranClassMemberId],
            $data,
        );
    }
}
