<?php

namespace App\Exceptions\Appointment;

use Aptive\Component\Http\Exceptions\AbstractHttpException;
use Aptive\Component\Http\HttpStatus;
use Throwable;

class CannotResolveAppointmentSubscriptionException extends AbstractHttpException
{
    public const STATUS_CODE = HttpStatus::UNPROCESSABLE_ENTITY;

    /**
     * @param string $message
     * @param array<string, string> $headers
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        array $headers = [],
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $headers, $previous);

        $this->message = !empty($message) ? $message : __('exceptions.cannot_resolve_appointment_subscription');
    }
}
