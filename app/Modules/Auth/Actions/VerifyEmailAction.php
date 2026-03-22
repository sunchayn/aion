<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DataTransferObjects\VerifyEmailData;
use App\Modules\Auth\Exceptions\InvalidEmailVerificationLinkException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\Dispatcher;

/** @final */
class VerifyEmailAction
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    /**
     * @throws InvalidEmailVerificationLinkException
     */
    public function execute(VerifyEmailData $data): void
    {
        if (! hash_equals($data->hash, sha1((string) $data->user->getEmailForVerification()))) {
            throw InvalidEmailVerificationLinkException::becauseOfInvalidHash();
        }

        if ($data->user->hasVerifiedEmail()) {
            return;
        }

        $data->user->markEmailAsVerified();

        $this->dispatcher->dispatch(
            new Verified($data->user),
        );
    }
}
