<?php
declare(strict_types=1);

namespace SG_AI_Studio\HelperAuth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SG_AI_Studio\Helper\Helper;
class SignApiAuthException extends \Exception
{
	private ?int $statusCode;

	public function __construct(string $message, ?int $statusCode = null)
	{
		parent::__construct($message);
		$this->statusCode = $statusCode;
	}

	public function getStatusCode(): ?int
	{
		return $this->statusCode;
	}
}

class SignApiServerException extends \Exception
{
	private ?int $statusCode;

	public function __construct(string $message, ?int $statusCode = null)
	{
		parent::__construct($message);
		$this->statusCode = $statusCode;
	}

	public function getStatusCode(): ?int
	{
		return $this->statusCode;
	}
}

class Claims
{
	public int $cts;
	public int $vsec;

	public string $client_id;

	public function __construct(int $cts, int $vsec, string $client_id)
	{
		$this->cts = $cts;
		$this->vsec = $vsec;
		$this->client_id = $client_id;
	}

	public function toArray(): array
	{
		return [
			'cts'  => $this->cts,
			'vsec' => $this->vsec,
			'_c'   => $this->client_id,
		];
	}
}

class TokenRequest
{
	public string $token;
	public string $service;
	public string $client;
	public string $clientKey;

	public function __construct(string $token, string $service, string $client, string $clientKey)
	{
		$this->token = $token;
		$this->service = $service;
		$this->client = $client;
		$this->clientKey = $clientKey;
	}

	public function toArray(): array
	{
		return [
			'token'      => $this->token,
			'service'    => $this->service,
			'client'     => $this->client,
			'client_key' => $this->clientKey,
		];
	}
}

class SignApiClient
{
	private string $apikeysPath;
	private string $clientName;
	private float $timeout;
	private string $serviceName;
	private string $signServerUrl;
	private bool $verifyCertificate;
	private int $token_valid_seconds;
	private string $algorithm;
	private ?string $token = null;
	private ?float $token_expiry = null;

	public function __construct(
		string $clientName,
		string $serviceName,
		string $signService = 'dynamic-keys',
		string $apikeysPath = '/etc/apikeys',
		float $timeout = 10.0,
		?string $hostName = 'api.staging.studio.siteground.ai',
		?string $signServerUrl = null,
		int $token_valid_seconds = 3600,
		string $algorithm = 'ES384'
	) {
		$this->apikeysPath = rtrim($apikeysPath, '/');
		$this->clientName = $clientName;
		$this->timeout = $timeout;
		$this->serviceName = $serviceName;

		$this->verifyCertificate = true;

		if ( Helper::is_staging_environment() ) {
			$hostname = 'api.staging.studio.siteground.ai';
		} else {
			$hostname = 'api.studio.siteground.ai';
		}
		$this->signServerUrl = $signServerUrl ?? "https://{$hostname}/{$signService}/v1/sign/token";
		$this->token_valid_seconds = $token_valid_seconds;
		$this->algorithm = $algorithm;
	}

	private function findLatestClientKey(): ?array
	{


		$keyData = get_option('sg_ai_studio_client_key');
		if (!$keyData) {

			return null;
		}

		if (!isset($keyData['key_name']) || !isset($keyData['key_priv'])) {

			return null;
		}

		$keyName = $keyData['key_name'];
		$privateKey = $keyData['key_priv'];

		$clientKeyData = [
			'name' => $keyName,
			'private_key' => $privateKey,
			'service' => $this->serviceName,
			'client' => $this->clientName,
			'client_key' => $keyName,
		];


		return $clientKeyData;
	}

	private function findPublicKeys(): ?array
	{
		$keyData = get_option('sg_ai_studio_service_key');
		if (!$keyData) {

			return null;
		}
		return $keyData;
	}

	private function generate_token( array $key_data, int $retries = 5, ?array $extra_payload = null ): string
	{
		$private_key = $key_data['private_key'];
		$service = $key_data['service'];
		$client = $key_data['client'];
		$client_key = $key_data['client_key'];

		$claims = new Claims( time(), $this->token_valid_seconds, $client );
		$payload = $claims->toArray();
		$payload['_u'] = preg_replace( '(^https?://)', '', get_site_url() );

		if ( is_array( $extra_payload ) ) {
			$payload = array_merge( $payload, $extra_payload );
		}

		$token         = JWT::encode( $payload, $private_key, $this->algorithm );
		$token_request = new TokenRequest( $token, $service, $client, $client_key );

		return $this->retry_with_backoff(
			function () use ( $token_request ) {
				return $this->make_token_request( $token_request );
			},
			$retries
		);
	}

	private function make_token_request( TokenRequest $tokenRequest ): string {
		$client = new Client(
			[
			'timeout' => $this->timeout,
			'verify' => $this->verifyCertificate,
			]
		);

		try {
			$response = $client->post($this->signServerUrl, [
				'json' => $tokenRequest->toArray(),
				'headers' => $this->getDefaultHeaders(),
			]);


			if ($response->getStatusCode() >= 400) {
				$errorMsg = "Sign server error: " . $response->getStatusCode();
				try {
					$errorData = json_decode($response->getBody()->getContents(), true);

					if (isset($errorData['message'])) {
						$errorMsg = $errorData['message'];
					}
				} catch (\Exception $e) {
					$responseText = $response->getBody()->getContents();
					if (!empty($responseText)) {

						$errorMsg = $responseText;
					}
				}
				throw new SignApiAuthException($errorMsg, $response->getStatusCode());
			}

			$responseData = json_decode($response->getBody()->getContents(), true);


			if (!isset($responseData['data']['token'])) {
				throw new SignApiAuthException("No token returned from sign server");
			}

			$signedToken = $responseData['data']['token'];

			return $signedToken;

		} catch (GuzzleException $e) {
			throw new SignApiAuthException("HTTP request failed: " . esc_html( $e->getMessage() ));
		}
	}

	public function get_auth_token( int $retries = 5 ): string {
		$current_time = time();
		if ( $this->token && $this->token_expiry && $current_time < ( $this->token_expiry - 300 ) ) {

			return $this->token;
		}

		$key_data = $this->findLatestClientKey();

		if ( ! $key_data ) {

			throw new SignApiAuthException( 'No API key found for client ' . esc_html( $this->clientName ) );
		}

		$token              = $this->generate_token( $key_data, $retries );
		$this->token        = $token;
		$this->token_expiry = $current_time + $this->token_valid_seconds;

		return $token;
	}

	private function retry_with_backoff(
		callable $operation,
		int $retries = 5,
		float $base_delay = 1.0,
		float $max_delay = 60.0,
		float $back_off_multiplier = 1.5
	) {
		$last_exception = null;

		for ( $attempt = 0; $attempt <= $retries; $attempt++ ) {
			try {
				return $operation();
			} catch ( \Exception $e ) {
				$last_exception = $e;
				if ( $attempt >= $retries ) {
					break;
				}

				$delay = min( $base_delay * pow( $back_off_multiplier, $attempt ), $max_delay );
				$jitter = wp_rand( 0, (int) ( $delay * 0.1 * 1000000 ) ) / 1000000;
				usleep( (int) ( ( $delay + $jitter ) * 1000000 ) );
			}
		}

		throw $last_exception;
	}

	private function getDefaultHeaders(): array
	{
		return [
			'Content-Type' => 'application/json',
			'User-Agent' => $this->clientName,
		];
	}

	private function getServicePublicKeys(): array
	{
		$serviceKeys = get_option('sg_ai_studio_service_key');
		if (!$serviceKeys || !is_array($serviceKeys)) {
			return [];
		}

		return $serviceKeys;
	}

	private function verify_token_with_keys( string $token ): ?array {
		$service_key = $this->getServicePublicKeys();
		$algorithms = array( 'RS512', 'ES384' );

		if ( ! isset( $service_key['key_pub'] ) ) {
			return null;
		}

		foreach ( $algorithms as $algorithm ) {
			try {
				$decoded_data = JWT::decode( $token, new Key( trim( $service_key['key_pub'] ), $algorithm ) );
				$decoded_data = (array) $decoded_data;

				// Validate timestamps like Python implementation.
				$cts  = $decoded_data['cts'] ?? $decoded_data['created'] ?? null;
				$vsec = $decoded_data['vsec'] ?? $decoded_data['exp'] ?? null;

				if ( $cts === null || $vsec === null ) {
					$cts  = $cts ?? time();
					$vsec = $vsec ?? 3600;
				}

				// Adjust vsec if it's a unix timestamp.
				if ( $vsec > $cts ) {
					$vsec = $vsec - $cts;
				}

				$now = time();
				if ( ( $cts + $vsec ) < $now ) {
					continue; // Token expired, try next key.
				}

				return $decoded_data;
			} catch ( \Exception $e ) {
				// Continue to next key/algorithm combination.
				continue;
			}
		}

		return null;
	}

	public function decode( string $token ): array {
		// Try Avalon-style JWT first (service keys).
		$decoded_data = $this->verify_token_with_keys( $token );
		if ( null !== $decoded_data ) {
			return $decoded_data;
		}

		// Fallback to original implementation for backwards compatibility.
		$public_key = $this->findPublicKeys();
		if ( $public_key === null ) {
			throw new SignApiAuthException( 'No public key found for client ' . esc_html( $this->clientName ) . ' and token verification failed for both Avalon and Google OAuth formats' );
		}

		$decoded_data = null;
		try {
			$key_value = trim( $public_key['key_pub'] );

			$decoded_data = JWT::decode( $token, new Key( $key_value, $this->algorithm ) );
			$decoded_data = (array) $decoded_data;
		} catch ( \Exception $e ) {
			throw new SignApiAuthException( 'Token decode failed: ' . esc_html( $e->getMessage() ) );
		}

		if ( ! isset( $decoded_data['cts'] ) || ! isset( $decoded_data['vsec'] ) ) {
			$error_message = 'Missing timestamps: cts(' . esc_html( $decoded_data['cts'] ) . ') or vsec(' . esc_html( $decoded_data['vsec'] ) . ') is missing';
			throw new SignApiAuthException( esc_html( $error_message ) );
		}

		$now = time();
		if ( ( $decoded_data['cts'] + $decoded_data['vsec'] ) < $now ) {
			$error_message = 'Wrong timestamps: cts ' . esc_html( $decoded_data['cts'] ) . ' vsec ' . esc_html( $decoded_data['vsec'] ) . ' time ' . $now;
			throw new SignApiAuthException( esc_html( $error_message ) );
		}

		return $decoded_data;
	}

	public function getUserIdFromToken(string $authorizationHeader): string
	{

		if (empty($authorizationHeader)) {
			throw new SignApiAuthException("Authorization header missing");
		}

		$tokenParts = explode(' ', $authorizationHeader, 2);
		if (count($tokenParts) !== 2 || strtolower($tokenParts[0]) !== 'bearer') {
			throw new SignApiAuthException("Invalid authorization header format");
		}

		$token = $tokenParts[1];

		try {
			$payload = $this->decode($token);

			// Try to get client_id (for Avalon JWT) or user_id (for Google OAuth)
			$userId = $payload['client_id'] ?? $payload['user_id'] ?? null;
			if (!$userId) {
				throw new SignApiAuthException("Missing client_id or user_id in token payload");
			}

			return (string) $userId;
		} catch (\Exception $e) {
			throw new SignApiAuthException("Token validation failed: " . esc_html( $e->getMessage() ));
		}
	}
}