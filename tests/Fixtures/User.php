<?php

declare(strict_types=1);

namespace LaravelFunLab\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use LaravelFunLab\Traits\Awardable;
use LaravelFunLab\Traits\HasProfile;

/**
 * Test User Model
 *
 * A simple user model fixture for testing the Awardable trait
 * and award engine functionality.
 */
class User extends Model
{
    use Awardable;
    use HasProfile;

    protected $fillable = ['name', 'email'];

    protected $table = 'users';
}
