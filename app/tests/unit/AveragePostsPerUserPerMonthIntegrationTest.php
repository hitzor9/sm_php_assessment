<?php

declare(strict_types=1);

namespace Tests\unit;

use PHPUnit\Framework\TestCase;
use SocialPost\Client\SocialClientInterface;
use SocialPost\Driver\FictionalDriver;
use SocialPost\Dto\FetchParamsTo;
use SocialPost\Hydrator\FictionalPostHydrator;
use SocialPost\Service\SocialPostService;
use Statistics\Calculator\AveragePostsPerUserPerMonth;
use Statistics\Dto\ParamsTo;
use Statistics\Enum\StatsEnum;

class AveragePostsPerUserPerMonthIntegrationTest extends TestCase
{
    /**
     * @dataProvider responseDataProvider
    */
    public function test_calculate_should_success(string $sampleFileName, array $results): void
    {
        $socialClient = $this->getMockBuilder(SocialClientInterface::class)->getMock();
        $socialClient
            ->expects($this->once())
            ->method('get')
            ->willReturn(file_get_contents(__DIR__ . '/../data/' . $sampleFileName));

        $socialClient
            ->expects($this->once())
            ->method('authRequest')
            ->willReturn(file_get_contents(__DIR__ . '/../data/auth-token-response.json'));

        $posts = (new SocialPostService(new FictionalDriver($socialClient), new FictionalPostHydrator()))->fetchPosts(new FetchParamsTo(1));

        $calculator = (new AveragePostsPerUserPerMonth())
            ->setParameters((new ParamsTo())
                ->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                ->setStartDate((new \DateTime('2018-08-01T00:00:00+00:00')))
                ->setEndDate((new \DateTime('2018-08-31T00:00:00+00:00')))
            );

        foreach ($posts as $post) {
            $calculator->accumulateData($post);
        }

        $stats = $calculator->calculate();

        $this->assertEquals(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH, $stats->getName());
        $this->assertEquals('posts per month', $stats->getUnits());
        $this->assertNull($stats->getSplitPeriod());

        if (empty($results)) {
            $this->assertNull($stats->getValue());
            $this->assertEmpty($stats->getChildren());
        } else {
            $children = $stats->getChildren();

            $this->assertNotEmpty($children);

            foreach ($results as $index => $resultItem) {
                $this->assertEquals('posts per month', $stats->getUnits());
                $this->assertEquals($children[$index]->getSplitPeriod(), $resultItem['split']);
                $this->assertEquals($children[$index]->getValue(), $resultItem['value']);
            }
        }
    }

    /**
     * Since the all calculation logic encapsulated into calculator class and tested with unit tests it's enough
     * to test with just 1 basic case to ensure that it works correct
     * If we would add some logic that affect code out outside calculator (like throwing an exception) we can cover it with integration tests as well
    */
    public function responseDataProvider(): array
    {
        return [
            [
                'social-posts-response.json',
                [
                    ['split' => 'Regenia Boice[user_13]',   'value' => 1],
                    ['split' => 'Isidro Schuett[user_16]',  'value' => 1],
                    ['split' => 'Lael Vassel[user_0]',      'value' => 1],
                    ['split' => 'Woodrow Lindholm[user_14]','value' => 1]
                ]
            ],
        ];
    }
}
