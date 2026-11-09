<?php

namespace JundFauz\EloquentKompact\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use JundFauz\EloquentKompact\Cast\UuidCast;
use JundFauz\EloquentKompact\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidCastTest extends TestCase
{
    private const string Uuid = '6f1c6a3e-1b2c-4d5e-8f90-0a1b2c3d4e5f';

    #[Test]
    public function it_gets_a_uuid_from_a_string(): void
    {
        $model = new UuidCastTestModel;
        $model->setRawAttributes(['uuid' => self::Uuid]);

        $this->assertInstanceOf(UuidInterface::class, $model->uuid);
        $this->assertSame(self::Uuid, $model->uuid->toString());
    }

    #[Test]
    public function it_gets_null_from_an_empty_value(): void
    {
        $model = new UuidCastTestModel;

        $model->setRawAttributes(['uuid' => null]);
        $this->assertNull($model->uuid);

        $model->setRawAttributes(['uuid' => '']);
        $this->assertNull($model->uuid);
    }

    #[Test]
    public function it_sets_a_uuid_as_a_string(): void
    {
        $model = new UuidCastTestModel;
        $model->uuid = Uuid::fromString(self::Uuid);

        $this->assertSame(self::Uuid, $model->getAttributes()['uuid']);
    }

    #[Test]
    public function it_sets_another_value_as_it_is(): void
    {
        $model = new UuidCastTestModel;
        $model->uuid = self::Uuid;

        $this->assertSame(self::Uuid, $model->getAttributes()['uuid']);
    }
}

class UuidCastTestModel extends Model
{
    protected $casts = ['uuid' => UuidCast::class];
}
