<?php

namespace App\Services;

use App\Contracts\Repositories\LanguageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LanguageService extends BaseService
{
    private readonly LanguageRepositoryInterface $languageRepository;

    public function __construct(LanguageRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->languageRepository = $repository;
    }

    public function search(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->languageRepository->search($search, $perPage);
    }

    public function restore(int $id): bool
    {
        return $this->languageRepository->restore($id);
    }
}
