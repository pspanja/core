<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\UserPreference\Gateway;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\UserPreference\Gateway
 */
class DoctrineDatabaseTest extends TestCase
{
    const EXISTING_USER_PREFERENCE_ID = 1;
    const EXISTING_USER_PREFERENCE_DATA = [
        'id' => 1,
        'user_id' => 14,
        'name' => 'timezone',
        'value' => 'America/New_York',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/user_preferences.php'
        );
    }

    public function testInsert()
    {
        $id = $this->getGateway()->setUserPreference(new UserPreferenceSetStruct([
            'userId' => 14,
            'name' => 'setting_3',
            'value' => 'value_3',
        ]));

        $data = $this->loadUserPreference($id);

        $this->assertEquals([
            'id' => $id,
            'user_id' => '14',
            'name' => 'setting_3',
            'value' => 'value_3',
        ], $data);
    }

    public function testUpdateUserPreference()
    {
        $userPreference = new UserPreferenceSetStruct([
            'userId' => 14,
            'name' => 'timezone',
            'value' => 'Europe/Warsaw',
        ]);

        $this->getGateway()->setUserPreference($userPreference);

        $this->assertEquals([
            'id' => (string) self::EXISTING_USER_PREFERENCE_ID,
            'user_id' => '14',
            'name' => 'timezone',
            'value' => 'Europe/Warsaw',
        ], $this->loadUserPreference(self::EXISTING_USER_PREFERENCE_ID));
    }

    public function testCountUserPreferences()
    {
        $this->assertEquals(3, $this->getGateway()->countUserPreferences(
            self::EXISTING_USER_PREFERENCE_DATA['user_id']
        ));
    }

    public function testLoadUserPreferences()
    {
        $userId = 14;
        $offset = 1;
        $limit = 2;

        $results = $this->getGateway()->loadUserPreferences($userId, $offset, $limit);

        $this->assertEquals([
            [
                'id' => '2',
                'user_id' => '14',
                'name' => 'setting_1',
                'value' => 'value_1',
            ],
            [
                'id' => '3',
                'user_id' => '14',
                'name' => 'setting_2',
                'value' => 'value_2',
            ],
        ], $results);
    }

    /**
     * Return a ready to test DoctrineStorage gateway.
     *
     * @return \Ibexa\Core\Persistence\Legacy\UserPreference\Gateway
     */
    protected function getGateway(): Gateway
    {
        return new DoctrineDatabase(
            $this->getDatabaseConnection()
        );
    }

    /**
     * @param int $id
     *
     * @return array
     */
    private function loadUserPreference(int $id): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('id', 'user_id', 'name', 'value')
            ->from('ezpreferences', 'p')
            ->where(
                $queryBuilder->expr()->eq(
                    'p.id',
                    $queryBuilder->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );
        $result = $queryBuilder->execute()->fetchAll(FetchMode::ASSOCIATIVE);

        return reset($result);
    }
}

class_alias(DoctrineDatabaseTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\UserPreference\Gateway\DoctrineDatabaseTest');