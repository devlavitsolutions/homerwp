<?php

namespace App\Database\Interfaces;

use App\Database\Models\Activation;

interface IActivationDbService
{
    public function selectLatestActivationByLicenseKey(string $licenseKey): ?Activation;
    public function createActivation(string $licenseKey): Activation;
    public function deleteActivation(): void;
}
