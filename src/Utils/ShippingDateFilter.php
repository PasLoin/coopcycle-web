<?php

namespace AppBundle\Utils;

use AppBundle\DataType\TsRange;
use AppBundle\Entity\Sylius\OrderTimeline;
use AppBundle\Entity\Vendor;
use AppBundle\OpeningHours\SpatieOpeningHoursRegistry;
use AppBundle\Sylius\Order\OrderInterface;
use Carbon\Carbon;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spatie\OpeningHours\OpeningHours;

class ShippingDateFilter
{
    private $orderTimelineCalculator;
    private $openingHoursCache = [];

    public function __construct(
        OrderTimelineCalculator $orderTimelineCalculator,
        LoggerInterface $logger = null)
    {
        $this->orderTimelineCalculator = $orderTimelineCalculator;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param OrderInterface $order
     * @param TsRange $range
     *
     * @return bool
     */
    public function accept(OrderInterface $order, TsRange $range, \DateTime $now = null): bool
    {
        if (null === $now) {
            $now = Carbon::now();
        }

        $dropoff = $range->getUpper();

        // Obviously, we can't ship in the past
        if ($dropoff <= $now) {

            $this->logger->info(sprintf('ShippingDateFilter::accept() - date "%s" is in the past',
                $dropoff->format(\DateTime::ATOM))
            );

            return false;
        }

        $vendor = $order->getVendor();
        $fulfillmentMethod = $order->getFulfillmentMethod();

        $openingHours = $this->getOpeningHours($vendor->getOpeningHours($fulfillmentMethod));

        if (!$openingHours->isOpenAt($range->getLower()) || !$openingHours->isOpenAt($range->getUpper())) {

            $this->logger->info(sprintf('ShippingDateFilter::accept() - range "%s" does not match with opening hours',
                sprintf('%s/%s', $range->getLower()->format(\DateTime::ATOM), $range->getUpper()->format(\DateTime::ATOM))
            ));

            return false;
        }

        $timeline = $this->orderTimelineCalculator->calculate($order, $range);
        $preparation = $timeline->getPreparationExpectedAt();

        if ($preparation <= $now) {

            $this->logger->info(sprintf('ShippingDateFilter::accept() - preparation time "%s" is in the past',
                $preparation->format(\DateTime::ATOM))
            );

            return false;
        }

        if ($vendor->hasClosingRuleFor($preparation, $now)) {

            $this->logger->info(sprintf('ShippingDateFilter::accept() - closed at "%s"',
                $preparation->format(\DateTime::ATOM))
            );

            return false;
        }

        $diffInDays = Carbon::instance($now)->diffInDays(Carbon::instance($dropoff));

        if ($diffInDays >= 7) {

            $this->logger->info(sprintf('ShippingDateFilter::accept() - date "%s" is more than 7 days in the future',
                $dropoff->format(\DateTime::ATOM))
            );

            return false;
        }

        return true;
    }

    private function getOpeningHours(array $openingHours, Collection $closingRules = null)
    {
        return SpatieOpeningHoursRegistry::get(
            $openingHours,
            $closingRules
        );
    }

    private function isOpen(array $openingHours, \DateTime $date, Collection $closingRules = null): bool
    {
        $oh = SpatieOpeningHoursRegistry::get(
            $openingHours,
            $closingRules
        );

        return $oh->isOpenAt($date);
    }
}
