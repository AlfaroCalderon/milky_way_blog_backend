<?php
namespace App\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class JWTService {
    private  $secretKey;
    private $refreshSecretkey;
    private string $algorithm = 'HS256';


    public function __construct()
    {
        $this->secretKey = config('app.key');
        $this->refreshSecretkey = config('app.key').'_refresh';
    }

    public function generateToken(array $payload, int $expiresIn = 3600): string
    {
        $issuedAt = time();
        $expires = $issuedAt + $expiresIn;

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expires,
            'type' => 'access_token'
        ]);

        return JWT::encode($tokenPayload, $this->secretKey, $this->algorithm);

    }

    public function generateRefreshToken(array $payload, int $expiresIn=604800):string{
        $issuedAt = time();
        $expires = $issuedAt + $expiresIn;

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expires,
            'type' => 'refresh_access_token'
        ]);

        return JWT::encode($tokenPayload, $this->refreshSecretkey, $this->algorithm);
    }



    public function generateTokenPair(array $payload): array{
        return [
            'access_token' => $this->generateToken($payload),
            'refresh_token' => $this->generateRefreshToken($payload)
        ];
    }

    public function decodeAccessToken(string $token){
        try {
            return JWT::decode($token, new Key($this->secretKey, $this->algorithm));
        } catch (Exception $error) {
            throw new Exception('Invalid or expired access token '.$error);
        }
    }

    public function decodeRefreshToken(string $token){
        try {
            return JWT::decode($token, new Key($this->refreshSecretkey, $this->algorithm));
        } catch (Exception $error) {
            throw new Exception('Invalid or expired refresh token '.$error);
        }
    }



}
