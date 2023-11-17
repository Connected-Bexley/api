<?php

declare(strict_types=1);

namespace App\Search;

use App\Support\Coordinate;

class SearchCriteriaQuery
{
    /**
     * @var string|null
     */
    protected $query;

    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string[]|null
     */
    protected $categories;

    /**
     * @var string[]|null
     */
    protected $personas;

    /**
     * @var string|null
     */
    protected $waitTime;

    /**
     * @var bool|null
     */
    protected $isFree;

    /**
     * @var bool|null
     */
    protected $isVirtual;

    /**
     * @var bool|null
     */
    protected $hasWheelchairAccess;

    /**
     * @var bool|null
     */
    protected $hasInductionLoop;

    /**
     * @var bool|null
     */
    protected $hasAccessibleToilet;

    /**
     * @var string|null
     */
    protected $startsAfter;

    /**
     * @var string|null
     */
    protected $endsBefore;

    /**
     * @var string[]|null
     */
    protected $eligibilities;

    /**
     * @var \App\Support\Coordinate|null
     */
    protected $location;

    /**
     * @var int|null
     */
    protected $distance;

    /**
     * @var string
     */
    protected $order;

    public function hasQuery(): bool
    {
        return $this->query !== null;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): void
    {
        $this->query = $query;
    }

    public function hasType(): bool
    {
        return $this->type !== null;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function hasCategories(): bool
    {
        return $this->categories !== null;
    }

    /**
     * @return string[]|null
     */
    public function getCategories(): ?array
    {
        return $this->categories;
    }

    /**
     * @param string[]|null $categories
     */
    public function setCategories(?array $categories): void
    {
        $this->categories = $categories;
    }

    public function hasPersonas(): bool
    {
        return $this->personas !== null;
    }

    /**
     * @return string[]|null
     */
    public function getPersonas(): ?array
    {
        return $this->personas;
    }

    /**
     * @param string[]|null $personas
     */
    public function setPersonas(?array $personas): void
    {
        $this->personas = $personas;
    }

    public function hasWaitTime(): bool
    {
        return $this->waitTime !== null;
    }

    public function getWaitTime(): ?string
    {
        return $this->waitTime;
    }

    public function setWaitTime(?string $waitTime): void
    {
        $this->waitTime = $waitTime;
    }

    public function hasIsFree(): bool
    {
        return $this->isFree !== null;
    }

    public function getIsFree(): ?bool
    {
        return $this->isFree;
    }

    public function setIsFree(?bool $isFree): void
    {
        $this->isFree = $isFree;
    }

    public function hasIsVirtual(): bool
    {
        return $this->isVirtual !== null;
    }

    public function getIsVirtual(): ?bool
    {
        return $this->isVirtual;
    }

    public function setIsVirtual(?bool $isVirtual): void
    {
        $this->isVirtual = $isVirtual;
    }

    public function hasHasWheelchairAccess(): bool
    {
        return $this->hasWheelchairAccess !== null;
    }

    public function getHasWheelchairAccess(): ?bool
    {
        return $this->hasWheelchairAccess;
    }

    public function setHasWheelchairAccess(?bool $hasWheelchairAccess): void
    {
        $this->hasWheelchairAccess = $hasWheelchairAccess;
    }

    public function hasHasInductionLoop(): bool
    {
        return $this->hasInductionLoop !== null;
    }

    public function getHasInductionLoop(): ?bool
    {
        return $this->hasInductionLoop;
    }

    public function setHasInductionLoop(?bool $hasInductionLoop): void
    {
        $this->hasInductionLoop = $hasInductionLoop;
    }

    public function hasHasAccessibleToilet(): bool
    {
        return $this->hasAccessibleToilet !== null;
    }

    public function getHasAccessibleToilet(): ?bool
    {
        return $this->hasAccessibleToilet;
    }

    public function setHasAccessibleToilet(?bool $hasAccessibleToilet): void
    {
        $this->hasAccessibleToilet = $hasAccessibleToilet;
    }

    public function hasStartsAfter(): bool
    {
        return $this->startsAfter !== null;
    }

    public function getStartsAfter(): ?string
    {
        return $this->startsAfter;
    }

    public function setStartsAfter(?string $startsAfter): void
    {
        $this->startsAfter = $startsAfter;
    }

    public function hasEndsBefore(): bool
    {
        return $this->endsBefore !== null;
    }

    public function getEndsBefore(): ?string
    {
        return $this->endsBefore;
    }

    public function setEndsBefore(?string $endsBefore): void
    {
        $this->endsBefore = $endsBefore;
    }

    public function hasLocation(): bool
    {
        return $this->location !== null;
    }

    public function getLocation(): ?Coordinate
    {
        return $this->location;
    }

    public function setLocation(?Coordinate $location): void
    {
        $this->location = $location;
    }

    public function hasDistance(): bool
    {
        return $this->distance !== null;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): void
    {
        $this->distance = $distance;
    }

    public function hasEligibilities(): bool
    {
        return $this->eligibilities !== null;
    }

    /**
     * @return string[]|null
     */
    public function getEligibilities(): ?array
    {
        return $this->eligibilities;
    }

    /**
     * @param string[]|null $eligibilities
     */
    public function setEligibilities(?array $eligibilities): void
    {
        $this->eligibilities = $eligibilities;
    }

    public function hasOrder(): bool
    {
        return $this->order !== null;
    }

    public function getOrder(): string
    {
        return $this->order;
    }

    public function setOrder(string $order): void
    {
        $this->order = $order;
    }
}
