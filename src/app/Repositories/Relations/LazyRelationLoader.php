<?php

declare(strict_types=1);

namespace App\Repositories\Relations;

use App\Models\External\AbstractExternalModel;
use Illuminate\Support\Collection;

class LazyRelationLoader
{
    public function __construct(
        private LazyRelationStrategy $strategy
    ) {
    }

    /**
     * @param string $relationName
     * @param Collection<int, AbstractExternalModel> $searchResult
     * @param LazyRelationPicker $picker
     *
     * @return Collection<int, AbstractExternalModel>
     */
    public function loadRelated(string $relationName, Collection $searchResult, LazyRelationPicker $picker): Collection
    {
        return $this->strategy->loadRelated($relationName, $searchResult, $picker);
    }
}
