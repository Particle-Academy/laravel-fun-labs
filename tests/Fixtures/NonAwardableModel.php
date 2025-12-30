<?php

declare(strict_types=1);

namespace LaravelFunLab\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Non-Awardable Model Fixture
 *
 * A model that does NOT use the Awardable trait, for testing
 * validation of award operations on incompatible models.
 */
class NonAwardableModel extends Model
{
    protected $fillable = ['name'];

    protected $table = 'users';
}
