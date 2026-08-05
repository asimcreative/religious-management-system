<?php

namespace Tests\Unit;

use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HashingConfigurationTest extends TestCase
{
    public function test_argon2id_configuration_accepts_and_upgrades_legacy_bcrypt_passwords(): void
    {
        $user = $this->createUserWithCompany();
        $legacyHash = password_hash('LegacyPassword@2026', PASSWORD_BCRYPT, ['cost' => 4]);
        $user->forceFill(['password' => $legacyHash])->save();

        $originalHashingConfig = config('hashing');

        try {
            config([
                'hashing.driver' => 'argon2id',
                'hashing.argon.verify' => false,
            ]);
            Hash::forgetDrivers();

            $result = app(AuthService::class)->attemptLogin($user->email, 'LegacyPassword@2026');

            $this->assertTrue($result['success']);
            $this->assertSame('argon2id', Hash::getDefaultDriver());
            $this->assertSame('argon2id', password_get_info($user->fresh()->password)['algoName']);
        } finally {
            config(['hashing' => $originalHashingConfig]);
            Hash::forgetDrivers();
        }
    }
}
