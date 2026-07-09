<?php

namespace Muvinime\Controllers;

use Muvinime\Services\EpisodeService;

class EpisodeController
{
    private EpisodeService $episodeService;

    public function __construct(EpisodeService $episodeService)
    {
        $this->episodeService = $episodeService;
    }

    public function post(array $data): array
    {
        return $this->episodeService->upload($data);
    }

    public function getEpisodeByTitle(string $title): ?object
    {
        return $this->episodeService->getEpisodeByTitle($title);
    }
}
