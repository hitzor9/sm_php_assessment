<?php

declare(strict_types=1);

namespace Tests\unit;

use PHPUnit\Framework\TestCase;
use SocialPost\Dto\SocialPostTo;
use Statistics\Calculator\AveragePostsPerUserPerMonth;
use Statistics\Dto\ParamsTo;
use Statistics\Enum\StatsEnum;

class AveragePostsPerUserPerMonthUnitTest extends TestCase
{
    /**
     * @param SocialPostTo[] $posts
     * @dataProvider responseDataProvider
    */
    public function test_calculate_should_success(ParamsTo $params, array $posts, ?array $results): void
    {
        $calculator = (new AveragePostsPerUserPerMonth())->setParameters($params);

        foreach ($posts as $post) {
            $calculator->accumulateData($post);
        }

        $value = $calculator->calculate();

        if (empty($results)) {
            $this->assertNull($value->getValue());
            $this->assertEmpty($value->getChildren());
        } else {
            $children = $value->getChildren();

            $this->assertNotEmpty($children);

            foreach ($results as $index => $result) {
                $this->assertEquals($result['value'], $children[$index]->getValue());
                $this->assertEquals($result['split'], $children[$index]->getSplitPeriod());
            }
        }
    }

    public function responseDataProvider(): array
    {
        return [
            'stats_for_month' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-30')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('1')->setDate(new \DateTime('2023-01-03')),
                    (new SocialPostTo())->setId('3')->setAuthorId('1')->setDate(new \DateTime('2023-01-04')),
                    (new SocialPostTo())->setId('4')->setAuthorId('2')->setDate(new \DateTime('2023-01-05')),
                ],
                [
                    ['split' => '[1]', 'value' => 3],
                    ['split' => '[2]', 'value' => 1]
                ]
            ],
            'stats_for_month_without_posts' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-30')),
                [],
                []
            ],
            'stats_for_multiple_months' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-02-28')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('1')->setDate(new \DateTime('2023-01-03')),
                    (new SocialPostTo())->setId('3')->setAuthorId('1')->setDate(new \DateTime('2023-01-04')),
                    (new SocialPostTo())->setId('4')->setAuthorId('2')->setDate(new \DateTime('2023-01-05')),
                ],
                [
                    ['split' => '[1]', 'value' => 1.5],
                    ['split' => '[2]', 'value' => 0.5]
                ]
            ],
            'stats_for_skewed_months' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-15')) #now we calculate statistics based on calendar months, so that range would be equals 2023-01-01 - 2023-03-31
                    ->setEndDate(new \DateTime('2023-03-15')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-02-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('1')->setDate(new \DateTime('2023-02-03')),
                    (new SocialPostTo())->setId('3')->setAuthorId('1')->setDate(new \DateTime('2023-02-04')),
                    (new SocialPostTo())->setId('4')->setAuthorId('2')->setDate(new \DateTime('2023-02-05')),
                ],
                [
                    ['split' => '[1]', 'value' => 1.0],
                    ['split' => '[2]', 'value' => 0.33]
                ]
            ],
            'stats_for_single_author' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-31')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('1')->setDate(new \DateTime('2023-01-03')),
                    (new SocialPostTo())->setId('3')->setAuthorId('1')->setDate(new \DateTime('2023-01-04')),
                    (new SocialPostTo())->setId('4')->setAuthorId('1')->setDate(new \DateTime('2023-01-05')),
                ],
                [
                    ['split' => '[1]', 'value' => 4.0],
                ]
            ],
            'stats_for_unknown_authors' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-31')),
                [
                    (new SocialPostTo())->setId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setDate(new \DateTime('2023-01-03')),
                    (new SocialPostTo())->setId('3')->setDate(new \DateTime('2023-01-04')),
                    (new SocialPostTo())->setId('4')->setDate(new \DateTime('2023-01-05')),
                ],
                []
            ],
            'stats_for_both_unknown_and_known_authors' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-31')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setDate(new \DateTime('2023-01-03')),
                    (new SocialPostTo())->setId('3')->setDate(new \DateTime('2023-01-04')),
                    (new SocialPostTo())->setId('4')->setDate(new \DateTime('2023-01-05')),
                ],
                [
                    ['split' => '[1]', 'value' => 1.0],
                ]
            ],
            'stats_for_posts_with_unknown_date' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-31')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1'),
                    (new SocialPostTo())->setId('2')->setAuthorId('2'),
                    (new SocialPostTo())->setId('3')->setAuthorId('3'),
                    (new SocialPostTo())->setId('4')->setAuthorId('4'),
                ],
                []
            ],
            'stats_for_posts_with_known_and_unknown_dates' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-31')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('2'),
                    (new SocialPostTo())->setId('3')->setAuthorId('3'),
                    (new SocialPostTo())->setId('4')->setAuthorId('4')->setDate(new \DateTime('2023-01-02')),
                ],
                [
                    ['split' => '[1]', 'value' => 1.0],
                    ['split' => '[4]', 'value' => 1.0],
                ]
            ],
            'stats_for_posts_with_unknown_dates_for_only_single_author' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-31')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('2')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('3')->setAuthorId('3'), #that post would be ignored because of date, so authorId=3 will skip total calculation
                    (new SocialPostTo())->setId('4')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                ],
                [
                    ['split' => '[1]', 'value' => 2.0],
                    ['split' => '[2]', 'value' => 1.0],
                ]
            ],
            'stats_with_inverted_parameters' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2022-01-31')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('2')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('3')->setAuthorId('3'), #that post would be ignored because of date, so authorId=3 will skip total calculation
                    (new SocialPostTo())->setId('4')->setAuthorId('1')->setDate(new \DateTime('2023-01-02')),
                ],
                []
            ],
            'stats_with_author_with_names' => [
                (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
                    ->setStartDate(new \DateTime('2023-01-01'))
                    ->setEndDate(new \DateTime('2023-01-30')),
                [
                    (new SocialPostTo())->setId('1')->setAuthorId('1')->setAuthorName('Foo')->setDate(new \DateTime('2023-01-02')),
                    (new SocialPostTo())->setId('2')->setAuthorId('1')->setAuthorName('Bar')->setDate(new \DateTime('2023-01-03')),
                    (new SocialPostTo())->setId('3')->setAuthorId('2')->setAuthorName('John')->setDate(new \DateTime('2023-01-04')),
                    (new SocialPostTo())->setId('4')->setAuthorId('3')->setAuthorName('Alice')->setDate(new \DateTime('2023-01-05')),
                ],
                [
                    ['split' => 'Bar[1]', 'value' => 2],
                    ['split' => 'John[2]', 'value' => 1],
                    ['split' => 'Alice[3]', 'value' => 1]
                ]
            ],
        ];
    }
}
