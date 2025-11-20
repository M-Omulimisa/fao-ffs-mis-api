<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class PesapalException extends Exception
{
    protected $pesapalCode;
    protected $pesapalDetails;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $pesapalCode = null,
        ?array $pesapalDetails = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->pesapalCode = $pesapalCode;
        $this->pesapalDetails = $pesapalDetails;
    }

    /**
     * Get Pesapal-specific error code
     */
    public function getPesapalCode(): ?string
    {
        return $this->pesapalCode;
    }

    /**
     * Get Pesapal error details
     */
    public function getPesapalDetails(): ?array
    {
        return $this->pesapalDetails;
    }

    /**
     * Create exception from API response
     */
    public static function fromApiResponse(array $response, string $context = ''): self
    {
        $message = $response['error']['message'] ?? $response['message'] ?? 'Unknown Pesapal API error';
        $pesapalCode = $response['error']['code'] ?? $response['error_type'] ?? null;
        $code = $response['status'] ?? 500;

        if ($context) {
            $message = $context . ': ' . $message;
        }

        return new self($message, $code, null, $pesapalCode, $response);
    }

    /**
     * Create authentication exception
     */
    public static function authenticationFailed(string $details = ''): self
    {
        $message = 'Pesapal authentication failed';
        if ($details) {
            $message .= ': ' . $details;
        }

        return new self($message, 401, null, 'AUTH_FAILED');
    }

    /**
     * Create network exception
     */
    public static function networkError(string $details = '', ?Throwable $previous = null): self
    {
        $message = 'Pesapal network error';
        if ($details) {
            $message .= ': ' . $details;
        }

        return new self($message, 503, $previous, 'NETWORK_ERROR');
    }

    /**
     * Create validation exception
     */
    public static function validationError(array $errors): self
    {
        $message = 'Pesapal validation error: ' . implode(', ', $errors);
        return new self($message, 400, null, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Create timeout exception
     */
    public static function timeout(string $operation = ''): self
    {
        $message = 'Pesapal request timeout';
        if ($operation) {
            $message .= ' during ' . $operation;
        }

        return new self($message, 408, null, 'TIMEOUT');
    }

    /**
     * Create configuration exception
     */
    public static function configurationError(string $details): self
    {
        return new self('Pesapal configuration error: ' . $details, 500, null, 'CONFIG_ERROR');
    }

    /**
     * Convert to array for logging
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'pesapal_code' => $this->getPesapalCode(),
            'pesapal_details' => $this->getPesapalDetails(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        switch ($this->pesapalCode) {
            case 'AUTH_FAILED':
                return 'Payment service authentication failed. Please try again later.';
            case 'NETWORK_ERROR':
                return 'Payment service is temporarily unavailable. Please try again.';
            case 'VALIDATION_ERROR':
                return 'Invalid payment information provided. Please check your details.';
            case 'TIMEOUT':
                return 'Payment request timed out. Please try again.';
            case 'CONFIG_ERROR':
                return 'Payment service configuration error. Please contact support.';
            default:
                return 'Payment processing failed. Please try again or contact support.';
        }
    }
}
