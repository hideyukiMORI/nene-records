<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

final readonly class GetAccessStatsByDateUseCase implements GetAccessStatsByDateUseCaseInterface
{
    public function __construct(
        private AccessLogRepositoryInterface $accessLogs,
    ) {
    }

    public function execute(GetAccessStatsByDateInput $input): GetAccessStatsByDateOutput
    {
        $items = $this->accessLogs->aggregateByDate($input->from, $input->to);

        return new GetAccessStatsByDateOutput(
            from: $input->from->format('Y-m-d'),
            to: $input->to->format('Y-m-d'),
            items: $items,
        );
    }
}
