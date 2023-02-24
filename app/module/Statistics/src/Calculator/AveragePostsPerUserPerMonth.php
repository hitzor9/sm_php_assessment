<?php

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

class AveragePostsPerUserPerMonth extends AbstractCalculator
{
    protected const UNITS = 'posts per month';

    private array $authorIds = [];
    private array $authorIdToNameMap = [];

    protected function checkPost(SocialPostTo $postTo): bool
    {
        //posts without authors and dates would be ignored
        return parent::checkPost($postTo) && null !== $postTo->getAuthorId() && null !== $postTo->getDate();
    }

    protected function doAccumulate(SocialPostTo $postTo): void
    {
        if (isset($this->authorIds[$postTo->getAuthorId()])) {
            $this->authorIds[$postTo->getAuthorId()] += 1;
        } else {
            $this->authorIds[$postTo->getAuthorId()] = 1;
        }

        if (null !== $postTo->getAuthorName()) {
            $this->authorIdToNameMap[$postTo->getAuthorId()] = $postTo->getAuthorName();
        }
    }

    protected function doCalculate(): StatisticsTo
    {
        $result = (new StatisticsTo())->setName($this->parameters->getStatName());

        if (empty($this->authorIds)) {
            return $result;
        }

        $monthInRange = $this->getNumberOfMonthsInRange();

        $stats = new StatisticsTo();

        foreach ($this->authorIds as $splitAuthorId => $total) {
            $splitName = ($this->authorIdToNameMap[$splitAuthorId] ?? ''). '['.$splitAuthorId.']';

            $stats->addChild((new StatisticsTo())
                ->setName($this->parameters->getStatName())
                ->setSplitPeriod($splitName)
                ->setValue(round($total / $monthInRange, 2))
                ->setUnits(self::UNITS));
        }

        return $stats;
    }

    /**
     * For now, we take calculate statistics by formula | number of posts by author / number of month in range
     * When our range starts with something like 2023-01-31 we count January 2023 as separate month for calculation
     * I don't think its fair enough, because we skip posts with date like 2023-01-15, but that case handles outside
    */
    private function getNumberOfMonthsInRange(): int
    {
        $monthsMap = [];
        $currentDate = clone $this->parameters->getStartDate();
        $interval = new \DateInterval('P1M');

        //case when startDate > endDate handled by post filtering (list of posts will be empty)
        while ($currentDate < $this->parameters->getEndDate()) {
            $monthsMap[$currentDate->format('M Y')] = true;
            $currentDate->add($interval);
        }

        $endDateMonth = $this->parameters->getEndDate()->format('M Y');

        if (!isset($monthsMap[$endDateMonth])) {
            //handling the case like start date = 2022-02-03 and end date 2022-03-02
            //assume in that case we should count it as 2 calendar month - February 2022 and March 2022
            $monthsMap[$endDateMonth] = true;
        }

        return count($monthsMap);
    }
}
