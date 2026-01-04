<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Activity\ActivitiesEnricher;
use App\Domain\Activity\Activity;
use App\Domain\Activity\ActivityTotals;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class IntroTextWidget implements Widget
{
    public function __construct(
        private ActivitiesEnricher $activitiesEnricher,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        $allActivities = $this->activitiesEnricher->getEnrichedActivities();
        $cutoffDate = SerializableDateTime::fromDateTimeImmutable($now->modify('-5 years'));
        $recentActivities = $allActivities->filter(
            fn (Activity $activity): bool => $activity->getStartDate()->isAfterOrOn($cutoffDate)
        );
        $fullActivityTotals = ActivityTotals::getInstance(
            activities: $allActivities,
            now: $now,
            translator: $this->translator,
        );
        $recentActivityTotals = ActivityTotals::getInstance(
            activities: $recentActivities,
            now: $now,
            translator: $this->translator,
        );

        return $this->twig->load('html/dashboard/widget/widget--intro-text.html.twig')->render([
            'introFull' => $fullActivityTotals,
            'introRecent' => $recentActivityTotals,
        ]);
    }
}
