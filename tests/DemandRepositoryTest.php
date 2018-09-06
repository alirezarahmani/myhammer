<?php

use MyHammer\Domain\Model\ValueObject\Address;
use PHPUnit\Framework\TestCase;
use MyHammer\Domain\Model\Entity\DemandEntity;

class DemandRepositoryTest extends TestCase
{
    private $payLoad = [
        'title' => 'this is dummy',
        'category_id' =>  '804040',
        'zip_code' => '10115',
        'city' => 'Berlin',
        'execute_time' => 'immediately',
        'description' => 'dummy text'

    ];

    public function setUp()
    {
        parent::setUp();
        putenv('VENDOR_DIR=../vendor/');
        \Loader\MyHammer::create();
    }

    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    /** @test */
    public function shouldNotThrowAnyException()
    {
        $doubleDemandEntity = Mockery::mock(DemandEntity::class);
        $doubleDemandEntity->allows()->setTitle(
            $this->payLoad['title']
        )->andReturns($doubleDemandEntity);
        $doubleDemandEntity->allows()->setCategoryId(
            $this->payLoad['category_id']
        )->andReturns($doubleDemandEntity);
        $doubleDemandEntity->allows()->setAddress(
            Mockery::any(
                new Address($this->payLoad['city'], $this->payLoad['zip_code'])
            )
        )->andReturns($doubleDemandEntity);
        $doubleDemandEntity->allows()->setExecuteTime(
            $this->payLoad['execute_time']
        )->andReturns($doubleDemandEntity);
        $doubleDemandEntity->allows()->setDescription(
            $this->payLoad['description']
        )->andReturns($doubleDemandEntity);
        $doubleDemandEntity->allows()->setUserId(
            1
        )->andReturns($doubleDemandEntity);
        $doubleDemandEntity->allows()->getCreatedAt(
        )->andReturns(new DateTime());
        $doubleDemandEntity->allows()->setUpdatedAt(
            Mockery::any(new DateTime())
        )->andReturns($doubleDemandEntity);
        $doubleDemandEntity->allows()->flush()->andReturns(true);
        $repo = new \MyHammer\Infrastructure\Repositories\DemandRepository();
        $repo->update($doubleDemandEntity, $this->payLoad);
        $this->assertTrue(true);
    }
}
