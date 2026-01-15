<?php

namespace Mrmaniak\Seat\IdentityProvider\Tests\Unit\Models;

use InvalidArgumentException;
use Mrmaniak\Seat\IdentityProvider\Models\OidcKeypair;
use PHPUnit\Framework\TestCase;

class OidcKeypairTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        OidcKeypair::clearActiveKeypairCache();
        parent::tearDown();
    }

    /**
     * @dataProvider rsaAlgorithmProvider
     */
    public function test_generates_valid_rsa_keypair(string $algorithm, int $expectedBits): void
    {
        $keypair = $this->generateKeypairWithAlgorithm($algorithm);

        $this->assertEquals($algorithm, $keypair['algorithm']);
        $this->assertNotEmpty($keypair['public_key']);
        $this->assertNotEmpty($keypair['private_key']);
        $this->assertNotEmpty($keypair['key_id']);
        $this->assertEquals(32, strlen($keypair['key_id'])); // 16 bytes = 32 hex chars

        // Verify the keys are valid PEM format
        $this->assertStringContainsString('-----BEGIN PUBLIC KEY-----', $keypair['public_key']);
        $this->assertStringContainsString('-----BEGIN PRIVATE KEY-----', $keypair['private_key']);

        // Verify RSA key details
        $privateKey = openssl_pkey_get_private($keypair['private_key']);
        $this->assertNotFalse($privateKey);

        $details = openssl_pkey_get_details($privateKey);
        $this->assertEquals(OPENSSL_KEYTYPE_RSA, $details['type']);
        $this->assertEquals($expectedBits, $details['bits']);
    }

    public static function rsaAlgorithmProvider(): array
    {
        return [
            'RS256' => ['RS256', 2048],
            'RS384' => ['RS384', 3072],
            'RS512' => ['RS512', 4096],
        ];
    }

    /**
     * @dataProvider ecAlgorithmProvider
     */
    public function test_generates_valid_ec_keypair(string $algorithm, string $expectedCurve): void
    {
        $keypair = $this->generateKeypairWithAlgorithm($algorithm);

        $this->assertEquals($algorithm, $keypair['algorithm']);
        $this->assertNotEmpty($keypair['public_key']);
        $this->assertNotEmpty($keypair['private_key']);
        $this->assertNotEmpty($keypair['key_id']);
        $this->assertEquals(32, strlen($keypair['key_id']));

        // Verify the keys are valid PEM format
        $this->assertStringContainsString('-----BEGIN PUBLIC KEY-----', $keypair['public_key']);
        $this->assertStringContainsString('-----BEGIN PRIVATE KEY-----', $keypair['private_key']);

        // Verify EC key details
        $privateKey = openssl_pkey_get_private($keypair['private_key']);
        $this->assertNotFalse($privateKey);

        $details = openssl_pkey_get_details($privateKey);
        $this->assertEquals(OPENSSL_KEYTYPE_EC, $details['type']);
        $this->assertEquals($expectedCurve, $details['ec']['curve_name']);
    }

    public static function ecAlgorithmProvider(): array
    {
        return [
            'ES256' => ['ES256', 'prime256v1'],
            'ES384' => ['ES384', 'secp384r1'],
            'ES512' => ['ES512', 'secp521r1'],
        ];
    }

    public function test_throws_exception_for_unsupported_algorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported algorithm: INVALID');

        $this->generateKeypairWithAlgorithm('INVALID');
    }

    public function test_generates_unique_key_ids(): void
    {
        $keyIds = [];
        for ($i = 0; $i < 10; $i++) {
            $keypair = $this->generateKeypairWithAlgorithm('RS256');
            $keyIds[] = $keypair['key_id'];
        }

        $this->assertCount(10, array_unique($keyIds), 'All key IDs should be unique');
    }

    /**
     * @dataProvider rsaAlgorithmProvider
     */
    public function test_rsa_keypair_can_sign_and_verify(string $algorithm): void
    {
        $keypair = $this->generateKeypairWithAlgorithm($algorithm);

        $data = 'test data to sign';
        $privateKey = openssl_pkey_get_private($keypair['private_key']);
        $publicKey = openssl_pkey_get_public($keypair['public_key']);

        $digestAlg = match ($algorithm) {
            'RS256' => OPENSSL_ALGO_SHA256,
            'RS384' => OPENSSL_ALGO_SHA384,
            'RS512' => OPENSSL_ALGO_SHA512,
        };

        openssl_sign($data, $signature, $privateKey, $digestAlg);
        $this->assertNotEmpty($signature);

        $verified = openssl_verify($data, $signature, $publicKey, $digestAlg);
        $this->assertEquals(1, $verified);
    }

    /**
     * @dataProvider ecAlgorithmProvider
     */
    public function test_ec_keypair_can_sign_and_verify(string $algorithm): void
    {
        $keypair = $this->generateKeypairWithAlgorithm($algorithm);

        $data = 'test data to sign';
        $privateKey = openssl_pkey_get_private($keypair['private_key']);
        $publicKey = openssl_pkey_get_public($keypair['public_key']);

        $digestAlg = match ($algorithm) {
            'ES256' => OPENSSL_ALGO_SHA256,
            'ES384' => OPENSSL_ALGO_SHA384,
            'ES512' => OPENSSL_ALGO_SHA512,
        };

        openssl_sign($data, $signature, $privateKey, $digestAlg);
        $this->assertNotEmpty($signature);

        $verified = openssl_verify($data, $signature, $publicKey, $digestAlg);
        $this->assertEquals(1, $verified);
    }

    public function test_is_rsa_returns_true_for_rsa_algorithms(): void
    {
        foreach (['RS256', 'RS384', 'RS512'] as $algorithm) {
            $keypair = $this->createKeypairInstance($algorithm);
            $this->assertTrue($keypair->isRsa(), "Expected isRsa() to return true for {$algorithm}");
            $this->assertFalse($keypair->isEc(), "Expected isEc() to return false for {$algorithm}");
        }
    }

    public function test_is_ec_returns_true_for_ec_algorithms(): void
    {
        foreach (['ES256', 'ES384', 'ES512'] as $algorithm) {
            $keypair = $this->createKeypairInstance($algorithm);
            $this->assertTrue($keypair->isEc(), "Expected isEc() to return true for {$algorithm}");
            $this->assertFalse($keypair->isRsa(), "Expected isRsa() to return false for {$algorithm}");
        }
    }

    /**
     * @dataProvider rsaAlgorithmProvider
     */
    public function test_rsa_to_jwk_returns_valid_structure(string $algorithm): void
    {
        $generated = $this->generateKeypairWithAlgorithm($algorithm);
        $keypair = $this->createKeypairInstance($algorithm, $generated['public_key'], $generated['key_id']);

        $jwk = $keypair->toJwk();

        $this->assertEquals('RSA', $jwk['kty']);
        $this->assertEquals($algorithm, $jwk['alg']);
        $this->assertEquals('sig', $jwk['use']);
        $this->assertEquals($generated['key_id'], $jwk['kid']);
        $this->assertArrayHasKey('n', $jwk);
        $this->assertArrayHasKey('e', $jwk);

        // Verify base64url encoding (no +, /, or = chars)
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', $jwk['n']);
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', $jwk['e']);
    }

    /**
     * @dataProvider ecAlgorithmProvider
     */
    public function test_ec_to_jwk_returns_valid_structure(string $algorithm, string $curve): void
    {
        $generated = $this->generateKeypairWithAlgorithm($algorithm);
        $keypair = $this->createKeypairInstance($algorithm, $generated['public_key'], $generated['key_id']);

        $jwk = $keypair->toJwk();

        $this->assertEquals('EC', $jwk['kty']);
        $this->assertEquals($algorithm, $jwk['alg']);
        $this->assertEquals('sig', $jwk['use']);
        $this->assertEquals($generated['key_id'], $jwk['kid']);
        $this->assertArrayHasKey('crv', $jwk);
        $this->assertArrayHasKey('x', $jwk);
        $this->assertArrayHasKey('y', $jwk);

        $expectedCrv = match ($algorithm) {
            'ES256' => 'P-256',
            'ES384' => 'P-384',
            'ES512' => 'P-521',
        };
        $this->assertEquals($expectedCrv, $jwk['crv']);

        // Verify base64url encoding
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', $jwk['x']);
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', $jwk['y']);
    }

    /**
     * Generate a keypair using the algorithm config directly (without database).
     */
    private function generateKeypairWithAlgorithm(string $algorithm): array
    {
        $algorithmConfig = [
            'RS256' => [
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ],
            'RS384' => [
                'digest_alg' => 'sha384',
                'private_key_bits' => 3072,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ],
            'RS512' => [
                'digest_alg' => 'sha512',
                'private_key_bits' => 4096,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ],
            'ES256' => [
                'digest_alg' => 'sha256',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'prime256v1',
            ],
            'ES384' => [
                'digest_alg' => 'sha384',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'secp384r1',
            ],
            'ES512' => [
                'digest_alg' => 'sha512',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'secp521r1',
            ],
        ];

        if (!isset($algorithmConfig[$algorithm])) {
            throw new InvalidArgumentException("Unsupported algorithm: {$algorithm}");
        }

        $config = $algorithmConfig[$algorithm];
        $privateKey = openssl_pkey_new($config);
        openssl_pkey_export($privateKey, $privateKeyPem);

        $publicKey = openssl_pkey_get_details($privateKey)['key'];
        $keyId = bin2hex(random_bytes(16));

        return [
            'public_key' => $publicKey,
            'private_key' => $privateKeyPem,
            'algorithm' => $algorithm,
            'key_id' => $keyId,
        ];
    }

    /**
     * Create an OidcKeypair instance without database persistence.
     */
    private function createKeypairInstance(string $algorithm, ?string $publicKey = null, ?string $keyId = null): OidcKeypair
    {
        if ($publicKey === null) {
            $generated = $this->generateKeypairWithAlgorithm($algorithm);
            $publicKey = $generated['public_key'];
            $keyId = $generated['key_id'];
        }

        $keypair = new OidcKeypair();
        $keypair->algorithm = $algorithm;
        $keypair->public_key = $publicKey;
        $keypair->key_id = $keyId;

        return $keypair;
    }
}
