<?php

declare(strict_types=1);

arch()->preset()->php();

arch('source code uses strict types')
    ->expect('rias\ssg')
    ->toUseStrictTypes();

arch('source code does not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('events extend yii base Event')
    ->expect('rias\ssg\events')
    ->toExtend('yii\base\Event');

arch('models extend craft base Model')
    ->expect('rias\ssg\models')
    ->toExtend('craft\base\Model');
