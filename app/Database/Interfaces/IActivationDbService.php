<?php

namespace App\Database\Interfaces;

use App\Database\Models\Activation;
use App\Http\DTOs\ActivationDTO;

interface IActivationDbService
{
    public function createActivation(ActivationDTO $activationDTO): Activation;

    public function deleteActivation(string $licenseKey, string $website): void;

    public function selectLatestActivationByLicenseKey(string $licenseKey): ?Activation;
}
