<?php

namespace App\Http\Contracts;

interface IContentInterface
{
    public function getAssistantResponse($keywords);
}
