<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Crypt;

/**
 * CNIC encryption trait.
 *
 * Encrypts the CNIC value using Laravel Crypt and maintains
 * a SHA-256 hash column (cnic_hash) for searchable lookups.
 *
 * Architecture Decision #7: CNIC stored encrypted with searchable hash.
 *
 * Requires columns: cnic (TEXT), cnic_hash (VARCHAR 64, indexed)
 */
trait HasEncryptedCnic
{
    public function setCnicAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['cnic'] = null;
            $this->attributes['cnic_hash'] = null;

            return;
        }

        // Strip dashes for consistent storage
        $normalized = str_replace('-', '', $value);

        $this->attributes['cnic'] = Crypt::encryptString($normalized);
        $this->attributes['cnic_hash'] = hash('sha256', $normalized);
    }

    public function getCnicAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::decryptString($value);
    }

    /**
     * Find a record by CNIC number.
     * Uses the hash column for efficient lookup.
     *
     * @return static|null
     */
    public static function findByCnic(string $cnic): ?self
    {
        $normalized = str_replace('-', '', $cnic);
        $hash = hash('sha256', $normalized);

        /** @var static|null */
        return static::where('cnic_hash', $hash)->first();
    }
}
